<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics;

use Google_Service_Analytics_Goal;
use Piwik\API\Request;
use Piwik\Archive\ArchiveInvalidator;
use Piwik\ArchiveProcessor\Parameters;
use Piwik\Common;
use Piwik\Concurrency\Lock;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveWriter;
use Piwik\Date;
use Piwik\Db;
use Piwik\Option;
use Piwik\Period\Factory;
use Piwik\Piwik;
use Piwik\Plugin\Manager;
use Piwik\Plugin\ReportsProvider;
use Piwik\Plugins\Goals\API;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\DailyRateLimitReached;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleAnalyticsQueryService;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleCustomDimensionMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleGoalMapper;
use Piwik\Plugins\GoogleAnalyticsImporter\Google\GoogleQueryObjectFactory;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\EndDate;
use Piwik\Plugins\GoogleAnalyticsImporter\Input\MaxEndDateReached;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Plugins\Goals\API as GoalsAPI;
use Piwik\Plugins\CustomDimensions\API as CustomDimensionsAPI;
use Piwik\Plugins\TagManager\TagManager;
use Piwik\Plugins\WebsiteMeasurable\Type;
use Piwik\Segment;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;
use Piwik\Site;
use Psr\Log\LoggerInterface;

class Importer
{
    const IS_IMPORTED_FROM_GA_NUMERIC = 'GoogleAnalyticsImporter_isImportedFromGa';

    /**
     * @var ReportsProvider
     */
    private $reportsProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array|null
     */
    private $recordImporters;

    /**
     * @var IdMapper
     */
    private $idMapper;

    /**
     * @var int
     */
    private $queryCount = 0;

    /**
     * @var ImportStatus
     */
    private $importStatus;

    /**
     * @var Lock
     */
    private $currentLock = null;

    /**
     * @var string
     */
    private $noDataMessageRemoved = false;

    /**
     * @var ArchiveInvalidator
     */
    private $invalidator;

    /**
     * @var EndDate
     */
    private $endDate;

    /**
     * Whether this is the main import date range or for a reimport range.
     * @var bool
     */
    private $isMainImport = true;

    public function __construct(ReportsProvider $reportsProvider,
                                LoggerInterface $logger,
                                IdMapper $idMapper, ImportStatus $importStatus, ArchiveInvalidator $invalidator, EndDate $endDate)
    {
        $this->reportsProvider = $reportsProvider;
        $this->logger = $logger;
        $this->idMapper = $idMapper;
        $this->importStatus = $importStatus;
        $this->invalidator = $invalidator;
        $this->endDate = $endDate;
    }

    public function setIsMainImport($isMainImport)
    {
        $this->isMainImport = $isMainImport;
    }

	protected function set_ending_date() {
		global $wpdb;
		$db_settings = new \WpMatomo\Db\Settings();
		$prefix_table = $db_settings->prefix_table_name( 'log_visit' );
		$sql = <<<SQL
SELECT min(visit_last_action_time) from $prefix_table
SQL;
		$row = $wpdb->get_row($sql);


	}

    public function import($id_site)
    {
        $date = null;
		$this->set_ending_date();

        try {
            $this->currentLock = $lock;
            $this->noDataMessageRemoved = false;
            $this->queryCount = 0;

            $endPlusOne = $end->addDay(1);

            if ($start->getTimestamp() >= $endPlusOne->getTimestamp()) {
                throw new \InvalidArgumentException("Invalid date range, start date is later than end date: {$start},{$end}");
            }

            $recordImporters = $this->getRecordImporters($idSite, $viewId);

            $site = new Site($idSite);
            for ($date = $start; $date->getTimestamp() < $endPlusOne->getTimestamp(); $date = $date->addDay(1)) {
                $this->logger->info("Importing data for GA View {viewId} for date {date}...", [
                    'viewId' => $viewId,
                    'date' => $date->toString(),
                ]);

                try {
                    $this->importDay($site, $date, $recordImporters, $segment);
                } finally {
                    // force delete all tables in case they aren't all freed
                    \Piwik\DataTable\Manager::getInstance()->deleteAll();
                }

                $this->importStatus->dayImportFinished($idSite, $date, $this->isMainImport);
            }

            $this->importStatus->finishImportIfNothingLeft($idSite);

            unset($recordImporters);
        } catch (DailyRateLimitReached $ex) {
            $this->importStatus->rateLimitReached($idSite);
            $this->logger->info($ex->getMessage());
            return true;
        } catch (MaxEndDateReached $ex) {
            $this->logger->info('Max end date reached. This occurs in Matomo for Wordpress installs when the importer tries to import days on or after the day Matomo for Wordpress installed.');

            if (!empty($date)) {
                $this->importStatus->dayImportFinished($idSite, $date, $this->isMainImport);
            }

            $this->importStatus->finishedImport($idSite);

            return true;
        } catch (\Exception $ex) {
            $this->onError($idSite, $ex, $date);
            return true;
        }

        return false;
    }

    /**
     * For use in RecordImporters that need to archive data for segments.
     * @var RecordImporter[] $recordImporters
     */
    public function importDay(Site $site, Date $date, $recordImporters, $segment, $plugin = null)
    {
        $maxEndDate = $this->endDate->getMaxEndDate();
        if ($maxEndDate && $maxEndDate->isEarlier($date)) {
            throw new MaxEndDateReached();
        }

        $archiveWriter = $this->makeArchiveWriter($site, $date, $segment, $plugin);
        $archiveWriter->initNewArchive();

        $recordInserter = new RecordInserter($archiveWriter);

        foreach ($recordImporters as $plugin => $recordImporter) {
            if (!$recordImporter->supportsSite()) {
                continue;
            }

            $this->logger->debug("Importing data for the {plugin} plugin.", [
                'plugin' => $plugin,
            ]);

            $recordImporter->setRecordInserter($recordInserter);

            $recordImporter->importRecords($date);

            // since we recorded some data, at some time, remove the no data message
            if (!$this->noDataMessageRemoved) {
                $this->removeNoDataMessage($site->getId());
                $this->noDataMessageRemoved = true;
            }

            $this->currentLock->reexpireLock();
        }

        $archiveWriter->insertRecord(self::IS_IMPORTED_FROM_GA_NUMERIC, 1);
        $archiveWriter->finalizeArchive();

        $this->invalidator->markArchivesAsInvalidated([$site->getId()], [$date], 'week', new Segment($segment, [$site->getId()]),
            false, false, null, $ignorePurgeLogDataDate = true);

        Common::destroy($archiveWriter);
    }

    private function makeArchiveWriter(Site $site, Date $date, $segment = '', $plugin = null)
    {
        $period = Factory::build('day', $date);
        $segment = new Segment($segment, [$site->getId()]);

        $params = new Parameters($site, $period, $segment);
        if (!empty($plugin)) {
            $params->setRequestedPlugin($plugin);
        }
        return new ArchiveWriter($params);
    }

    /**
     * @param $idSite
     * @param $viewId
     * @return RecordImporter[]
     * @throws \DI\NotFoundException
     */
    private function getRecordImporters($idSite, $viewId)
    {
        if (empty($this->recordImporters)) {
            $recordImporters = StaticContainer::get('GoogleAnalyticsImporter.recordImporters');

            $this->recordImporters = [];
            foreach ($recordImporters as $index => $recordImporterClass) {
                if (!defined($recordImporterClass . '::PLUGIN_NAME')) {
                    throw new \Exception("The $recordImporterClass record importer is missing the PLUGIN_NAME constant.");
                }

                $pluginName = $recordImporterClass::PLUGIN_NAME;
                if ($this->isPluginUnavailable($pluginName)) {
                    continue;
                }

                $this->recordImporters[$pluginName] = $recordImporterClass;
            }
        }

        $quotaUser = defined('PIWIK_TEST_MODE') ? 'test' : SettingsPiwik::getPiwikUrl();

        $instances = [];
        foreach ($this->recordImporters as $pluginName => $className) {
            $instances[$pluginName] = new $className($idSite, $this->logger);
        }
        return $instances;
    }

    private function logNoGoalIdFoundException($goal)
    {
        $this->logger->warning("No GA goal ID found mapped for '{$goal['name']}' [idgoal = {$goal['idgoal']}]");
    }

    public function getQueryCount()
    {
        return $this->queryCount;
    }

    private function removeNoDataMessage($idSite)
    {
        $hadTrafficKey = 'SitesManagerHadTrafficInPast_' . (int) $idSite;
        Option::set($hadTrafficKey, 1);
    }

    private function isPluginUnavailable($pluginName)
    {
        return !Manager::getInstance()->isPluginActivated($pluginName)
            || !Manager::getInstance()->isPluginLoaded($pluginName)
            || !Manager::getInstance()->isPluginInFilesystem($pluginName);
    }

   private function isGaAuthroizationError(\Exception $ex)
    {
        if ($ex->getCode() != 403) {
            return false;
        }

        $messageContent = @json_decode($ex->getMessage(), true);
        if (isset($messageContent['error']['message'])
            && stristr($messageContent['error']['message'], 'Request had insufficient authentication scopes')
        ) {
            return true;
        }

        return false;
    }

    private function onError($idSite, \Exception $ex, Date $date = null)
    {
        $this->logger->info("Unexpected Error: {ex}", ['ex' => $ex]);

        if ($this->isGaAuthroizationError($ex)) {
            $this->importStatus->erroredImport($idSite, Piwik::translate('GoogleAnalyticsImporter_InsufficientScopes'));
        } else {
            $dateStr = isset($date) ? $date->toString() : '(unknown)';
            $this->importStatus->erroredImport($idSite, "Error on day $dateStr, " . $ex->getMessage());
        }
    }
}
