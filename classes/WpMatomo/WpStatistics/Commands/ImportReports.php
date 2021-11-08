<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics\Commands;

require_once __DIR__ . '/../../../../app/LegacyAutoloader.php';
require_once __DIR__ . '/../../../../app/vendor/autoload.php';

use Monolog\Logger;
use Piwik\Concurrency\Lock;
use Piwik\Concurrency\LockBackend\MySqlLockBackend;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugin\Manager;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\Authorization;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportConfiguration;
use Piwik\Plugins\GoogleAnalyticsImporter\Importer;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportLock;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportStatus;
use Piwik\Plugins\GoogleAnalyticsImporter\ImportWasCancelledException;
use Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor;
use Piwik\Plugins\GoogleAnalyticsImporter\Tasks;
use Piwik\Plugins\WebsiteMeasurable\Type;
use Piwik\Site;
use Piwik\Timer;

// TODO: make sure same version of google api client is used in this & SearchEngineKeywordsPerformance
// (may have to add test in target plugin)
// TODO: support importing segments
class ImportReports extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('wpstatistics:import-reports');
        $this->setDescription('Import reports from wpstatistics into Matomo sites.');
	}

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            return $this->executeImpl($input, $output);
        } catch (ImportWasCancelledException $ex) {
            $output->writeln("Import was cancelled, aborting.");
        }
    }

    public function execute($siteId = null)
    {

        $type = $isMobileApp ? \Piwik\Plugins\MobileAppMeasurable\Type::ID : Type::ID;

        $idSite = $this->getIdSite($input);
        LogToSingleFileProcessor::handleLogToSingleFileInCliCommand($idSite);

        /** @var ImportStatus $importStatus */
        $importStatus = StaticContainer::get(ImportStatus::class);


        if (empty($idSite)) {
            $viewId = $this->getViewId($input, $output, $service);
        }

        LogToSingleFileProcessor::handleLogToSingleFileInCliCommand($idSite);

        /** @var ImportConfiguration $importerConfiguration */
        $importerConfiguration = StaticContainer::get(ImportConfiguration::class);
        $this->setImportRunConfiguration($importerConfiguration, $input);

        /** @var Importer $importer */
        $importer = StaticContainer::get(Importer::class);

        $lock = null;

        $createdSiteInCommand = false;
        if (empty($idSite)
            && !empty($property)
            && !empty($account)
        ) {
            try {
                $idSite = $importer->makeSite($account, $property, $viewId, $timezone, $type, $extraCustomDimensions);
            } catch (\Google_Exception $ex) {
                if ($isAccountDeduced) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "<comment>NOTE: We tried to deduce your GA account ID from the property ID above, it's possible your account ID differs. If this is the case specify it manually using --account=... and try again.</comment>");
                }
                throw $ex;
            }

            $createdSiteInCommand = true;
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Created new site with ID = $idSite.");
        } else {
            $status = $importStatus->getImportStatus($idSite);
            if (empty($status)) {
                throw new \Exception("There is no ongoing import for site with ID = {$idSite}. Please start a new import.");
            }

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                throw new \Exception("The import for site with ID = {$idSite} has finished. Please start a new import.");
            }

            if ($status['status'] == ImportStatus::STATUS_ERRORED) {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Import for site with ID = $idSite has errored, will attempt to resume.");
            } else {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Resuming import into existing site $idSite.");
            }
        }

        $lock = self::makeLock();
        $success = $lock->acquireLock($idSite);
        if (empty($success)) {
            $n = ceil(ImportLock::getLockTtlConfig(StaticContainer::get(Config::class)) / 60);
            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "<error>An import is currently in progress. (If the other import has failed, you should be able to try again in about $n minutes.)</error>");
            return;
        }

        $timer = new Timer();

        try {
            $importStatus->resumeImport($idSite);

            $dates = $this->getDatesToImport($input);
            if (empty($dates) && empty($status['import_end_time'])) {
                if (!empty($status['import_range_start'])) {
                    $startDate = Date::factory($status['import_range_start']);
                } else {
                    $startDate = Date::factory(Site::getCreationDateFor($idSite));
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "No dates specified with --dates, importing data from when the GA site was created to today: {$startDate}");
                }

                if (!empty($status['import_range_end'])) {
                    $endDate = Date::factory($status['import_range_end']);
                } else {
                    $endDate = Date::factory('yesterday'); // we don't want to import today since it's not complete yet
                }

                $dates = [$startDate, $endDate];
            } elseif ($createdSiteInCommand
                && !empty($dates)
            ) {
                $importStatus->setImportDateRange($idSite, $dates[0], $dates[1]);
            }

            $abort = $importer->importEntities($idSite, $account, $property, $viewId);
            if ($abort) {
                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Failed to import property entities, aborting.");
                return;
            }

            $dateRangesToReImport = empty($status['reimport_ranges']) ? [] : $status['reimport_ranges'];
            $dateRangesToReImport = array_map(function ($d) {
                return [Date::factory($d[0]), Date::factory($d[1])];
            }, $dateRangesToReImport);

            $dateRangesToImport = $dateRangesToReImport;

            // the range can be invalid if a job is finished, since we'll be at the end date
            if (!empty($dates) &&
                !$dates[1]->isEarlier($dates[0])
            ) {
                $dateRangesToImport = array_merge($dateRangesToReImport, [[$dates[0], $dates[1]]]);
            }

            $dateRangesText = array_map(function ($d) { return $d[0] . ',' . $d[1]; }, $dateRangesToImport);
            $dateRangesText = implode(', ', $dateRangesText);

            $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Importing the following date ranges in order: " . $dateRangesText);

            // NOTE: date ranges to reimport are handled first, then we go back to the main import (which could be
            // continuous)
            foreach (array_values($dateRangesToImport) as $index => $datesToImport) {
                $status = $importStatus->getImportStatus($idSite); // can change in the meantime, so we refetch

                if (!is_array($datesToImport)
                    || count($datesToImport) != 2
                ) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Found broken entry in date ranges to import (entry #$index) with improper type, skipping.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                $isMainImport = !empty($dates) && empty($status['import_end_time']) && $index == count($dateRangesToImport) - 1; // last is always the main import, if one exists

                list($startDate, $endDate) = $datesToImport;

                if ($isMainImport) {
                    $lastDateImported = !empty($status['main_import_progress']) ? $status['main_import_progress'] : null;
                    $lastDateImported = $lastDateImported ?: (!empty($status['last_date_imported']) ? $status['last_date_imported'] : null);
                } else {
                    $lastDateImported = !empty($status['last_date_imported']) ? $status['last_date_imported'] : null;
                }

                if (!empty($lastDateImported)
                    && Date::factory($lastDateImported)->addDay(1)->isLater($startDate)
                ) {
                    $startDate = Date::factory($lastDateImported)->addDay(1);
                }

                if (!$this->isValidDate($startDate)
                    || !$this->isValidDate($endDate)
                ) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Found broken entry in date ranges to import (entry #$index) with invalid date strings, skipping.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                if ($endDate->isEarlier($startDate)) {
                    $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "(Entry #$index) is finished, moving on.");
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                    continue;
                }

                $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Importing reports for date range {$startDate} - {$endDate} from GA view $viewId.");

                try {
                    $importer->setIsMainImport($isMainImport);
                    $aborted = $importer->import($idSite, $viewId, $startDate, $endDate, $lock);
                    if ($aborted) {
                        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Error encountered, aborting.");
                        break;
                    }
                } finally {
                    // doing it in the finally since we can get rate limited, which will result in an exception thrown
                    if (!$skipArchiving) {
                        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Running archiving for newly imported data...");
                        $status = $importStatus->getImportStatus($idSite);
                        Tasks::startArchive($status, $wait = true, $startDate, $checkImportIsRunning = false);
                    }
                }

                $isReimportEntry = $index < count($dateRangesToReImport);
                if ($isReimportEntry) {
                    $importStatus->removeReImportEntry($idSite, $datesToImport);
                }
            }
        } finally {
            $importStatus->finishImportIfNothingLeft($idSite);

            $lock->unlock();
        }

        $queryCount = $importer->getQueryCount();
        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "Done in $timer. [$queryCount API requests made to GA]");
    }

    private function getViewId(InputInterface $input, OutputInterface $output, \Google_Service_Analytics $service)
    {
        $viewId = $input->getOption('view');
        if (!empty($viewId)) {
            return $viewId;
        }

        $propertyId = $input->getOption('property');
        $accountId = $input->getOption('account');
        if (empty($propertyId)
            && empty($accountId)
        ) {
            throw new \Exception("Either a single --view or both --property and --account must be supplied.");
        }

        $profiles = $service->management_profiles->listManagementProfiles($accountId, $propertyId);

        /** @var \Google_Service_Analytics_Profile[] $profiles */
        $profiles = $profiles->getItems();

        $profile = reset($profiles);
        $profileId = $profile->id;

        $output->writeln(LogToSingleFileProcessor::$cliOutputPrefix . "No view ID supplied, using first profile in the supplied account/property: " . $profileId);

        return $profileId;
    }

    private function getDatesToImport(InputInterface $input)
    {
        $dates = $input->getOption('dates');
        if (empty($dates)) {
            return null;
        }

        $dates = explode(',', $dates);

        if (count($dates) != 2) {
            $this->invalidDatesOption();
        }

        return [
            $this->parseDate($dates[0]),
            $this->parseDate($dates[1]),
        ];
    }

    private function invalidDatesOption()
    {
        throw new \Exception("Invalid value for the dates option supplied, must be a comma separated value with two "
            . "dates, eg, 2014-02-03,2015-02-03");
    }

    private function parseDate($date)
    {
        try {
            return Date::factory($date);
        } catch (\Exception $ex) {
            return $this->invalidDatesOption();
        }
    }

    private function getIdSite(InputInterface $input)
    {
        $idSite = $input->getOption('idsite');
        if (!empty($idSite)) {
            if (!is_numeric($idSite)) {
                throw new \Exception("Invalid --idsite value provided, must be an integer.");
            }

            try {
                new Site($idSite);
            } catch (\Exception $ex) {
                throw new \Exception("Site ID $idSite does not exist.");
            }
        }
        return $idSite;
    }

    private function setImportRunConfiguration(ImportConfiguration $importerConfiguration, InputInterface $input)
    {
        $cvarCount = (int) $input->getOption('cvar-count');
        $importerConfiguration->setNumCustomVariables($cvarCount);
    }

    public static function makeLock()
    {
        return new ImportLock(StaticContainer::get(Config::class));
    }

    private function isValidDate($date)
    {
        try {
            Date::factory($date);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }
}
