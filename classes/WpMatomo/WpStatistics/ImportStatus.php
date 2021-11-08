<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics;


use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Exception\UnexpectedWebsiteFoundException;
use Piwik\Option;
use Piwik\Date;
use Piwik\Period\Factory;
use Piwik\Piwik;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Site;

// TODO: maybe make an import status entity class
class ImportStatus
{
    const OPTION_NAME_PREFIX = 'GoogleAnalyticsImporter.importStatus_';
    const IMPORTED_DATE_RANGE_PREFIX = 'GoogleAnalyticsImporter.importedDateRange_';

    const STATUS_STARTED = 'started';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';
    const STATUS_ERRORED = 'errored';
    const STATUS_RATE_LIMITED = 'rate_limited';
    const STATUS_KILLED = 'killed';

    public static function isImportRunning($status)
    {
        $idSite = $status['idSite'];

        $lock = ImportReports::makeLock();
        if ($lock->acquireLock($idSite, $ttl = 3)) {
            $lock->unlock();
            return false;
        } else {
            return true;
        }
    }

    public function startingImport($propertyId, $accountId, $viewId, $idSite, $extraCustomDimensions = [])
    {
        try {
            $status = $this->getImportStatus($idSite);
            if ($status['status'] != self::STATUS_FINISHED) {
                throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_CancelExistingImportFirst', [$idSite]));
            }
        } catch (\Exception $ex) {
            // ignore
        }

        $now = Date::getNowTimestamp();
        $status = [
            'status' => self::STATUS_STARTED,
            'idSite' => $idSite,
            'ga' => [
                'property' => $propertyId,
                'account' => $accountId,
                'view' => $viewId,
            ],
            'last_date_imported' => null,
            'main_import_progress' => null,
            'import_start_time' => $now,
            'import_end_time' => null,
            'last_job_start_time' => $now,
            'last_day_archived' => null,
            'import_range_start' => null,
            'import_range_end' => null,
            'extra_custom_dimensions' => $extraCustomDimensions,
            'days_finished_since_rate_limit' => 0,
            'reimport_ranges' => [],
        ];

        $this->saveStatus($status);

        return $status;
    }

    public function getImportedDateRange($idSite)
    {
        $optionName = self::IMPORTED_DATE_RANGE_PREFIX . $idSite;
        $existingValue = Option::get($optionName);

        $dates = ['', ''];
        if (!empty($existingValue)) {
            $dates = explode(',', $existingValue);
        }
        return $dates;
    }

    public function dayImportFinished($idSite, Date $date, $isMainImport = true)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ONGOING;

        if (empty($status['last_date_imported'])
            || !Date::factory($status['last_date_imported'])->isLater($date)
        ) {
            $status['last_date_imported'] = $date->toString();

            $this->setImportedDateRange($idSite, $startDate = null, $date);

            if ($isMainImport) {
                $status['main_import_progress'] = $date->toString();
            }
        }

        if (isset($status['days_finished_since_rate_limit'])
            && is_int($status['days_finished_since_rate_limit'])
        ) {
            $status['days_finished_since_rate_limit'] += 1;
        }

        $this->saveStatus($status);
    }

    public function setImportDateRange($idSite, Date $startDate = null, Date $endDate = null)
    {
        $status = $this->getImportStatus($idSite);
        $status['import_range_start'] = $startDate ? $startDate->toString() : '';
        $status['import_range_end'] = $endDate ? $endDate->toString() : '';

        if (!empty($status['import_range_start'])
            && !empty($status['import_range_end'])
            && Date::factory($status['import_range_start'])->isLater(Date::factory($status['import_range_end']))
        ) {
            throw new \Exception("The start date cannot be past the end date.");
        }

        if ($status['status'] == self::STATUS_FINISHED) {
            $status['status'] = self::STATUS_ONGOING;
        }

        $status['import_end_time'] = null;

        $this->saveStatus($status);
    }

    public function setIsVerboseLoggingEnabled($idSite, $isVerboseLoggingEnabled)
    {
        $status = $this->getImportStatus($idSite);
        $status['is_verbose_logging_enabled'] = $isVerboseLoggingEnabled;
        $this->saveStatus($status);
    }

    public function resumeImport($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ONGOING;
        $status['last_job_start_time'] = Date::getNowTimestamp();
        $status['days_finished_since_rate_limit'] = 0;
        $this->saveStatus($status);
    }

    public function importArchiveFinished($idSite, Date $date)
    {
        $status = $this->getImportStatus($idSite);
        $status['last_day_archived'] = $date->toString();
        $this->saveStatus($status);
    }

    public function getImportStatus($idSite)
    {
        $optionName = $this->getOptionName($idSite);
        Option::clearCachedOption($optionName);
        $data = Option::get($optionName);
        if (empty($data)) {
            throw new ImportWasCancelledException();
        }
        $data = json_decode($data, true);
        return $data;
    }

    public function finishedImport($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_FINISHED;
        $status['import_end_time'] = Date::getNowTimestamp();
        $this->saveStatus($status);
    }

    public function erroredImport($idSite, $errorMessage)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_ERRORED;
        $status['error'] = $errorMessage;
        $this->saveStatus($status);
    }

    public function rateLimitReached($idSite)
    {
        $status = $this->getImportStatus($idSite);
        $status['status'] = self::STATUS_RATE_LIMITED;
        $this->saveStatus($status);
    }

    public function getAllImportStatuses($checkKilledStatus = false)
    {
        $optionValues = Option::getLike(self::OPTION_NAME_PREFIX . '%');

        $result = [];
        foreach ($optionValues as $optionValue) {
            $status = json_decode($optionValue, true);
            $status = $this->enrichStatus($status, $checkKilledStatus);
            $result[] = $status;
        }

        usort($result, function (&$lhs, $rhs) {
            $lhsIdSite = (int)($lhs['idSite'] ?? 0);
            $rhsIdSite = (int)($rhs['idSite'] ?? 0);

            if ($lhsIdSite < $rhsIdSite) {
                return -1;
            } else if ($lhsIdSite > $rhsIdSite) {
                return 1;
            } else {
                return 0;
            }
        });

        return $result;
    }

    public function deleteStatus($idSite)
    {
        $optionName = $this->getOptionName($idSite);
        Option::delete($optionName);

        $hostname = Config::getHostname();

        $logToSingleFile = StaticContainer::get('GoogleAnalyticsImporter.logToSingleFile');

        $importLogFile = Tasks::getImportLogFile($idSite, $hostname, $logToSingleFile);
        @unlink($importLogFile);

        $archiveLogFile = Tasks::getImportLogFile($idSite, $hostname, $logToSingleFile);
        @unlink($archiveLogFile);
    }

    /**
     * public for tests
     * @ignore
     */
    public function saveStatus($status)
    {
        $optionName = $this->getOptionName($status['idSite']);
        Option::set($optionName, json_encode($status));
    }

    private function getOptionName($idSite)
    {
        return self::OPTION_NAME_PREFIX . $idSite;
    }

    private function enrichStatus($status, $checkKilledStatus)
    {
        if (isset($status['idSite'])) {
            try {
                $status['site'] = new Site($status['idSite']);
            } catch (UnexpectedWebsiteFoundException $ex) {
                $status['site'] = null;
            }
        }

        if (isset($status['import_start_time'])) {
            $status['import_start_time'] = $this->getDatetime($status['import_start_time']);
        }

        if (isset($status['import_end_time'])) {
            $status['import_end_time'] = $this->getDatetime($status['import_end_time']);
        }

        if (isset($status['last_job_start_time'])) {
            $status['last_job_start_time'] = $this->getDatetime($status['last_job_start_time']);
        }

        if (!empty($status['import_range_start'])) {
            $status['import_range_start'] = $this->getDateString($status['import_range_start']);
        }

        if (!empty($status['import_range_end'])) {
            $status['import_range_end'] = $this->getDateString($status['import_range_end']);

            $status['estimated_days_left_to_finish'] = self::getEstimatedDaysLeftToFinish($status);
        }

        if (!empty($status['ga'])) {
            $status['gaInfoPretty'] = 'Property: ' . $status['ga']['property'] . "\nAccount: " . $status['ga']['account']
                . "\nView: " . $status['ga']['view'];
        }

        if ($checkKilledStatus
            && ($status['status'] == self::STATUS_ONGOING
                || $status['status'] == self::STATUS_STARTED)
            && !self::isImportRunning($status)
            // check last job start time is over 5 minutes ago
            && (empty($status['last_job_start_time'])
                || Date::factory($status['last_job_start_time'])->getTimestamp() < Date::now()->getTimestamp() - 300)
        ) {
            $status['status'] = self::STATUS_KILLED;
        }

        return $status;
    }

    public static function getEstimatedDaysLeftToFinish($status)
    {
        try {
            if (!empty($status['main_import_progress'])
                && !empty($status['import_range_end'])
            ) {
                $lastDateImported = Date::factory($status['main_import_progress']);
                $importEndDate = Date::factory($status['import_range_end']);

                $importStartTime = Date::factory($status['import_start_time']);

                if (isset($status['import_range_start'])) {
                    $importRangeStart = Date::factory($status['import_range_start']);
                } else {
                    $importRangeStart = Date::factory(Site::getCreationDateFor($status['idSite']));
                }

                $daysRunning = floor((Date::now()->getTimestamp() - $importStartTime->getTimestamp()) / 86400);
                if ($daysRunning == 0) {
                    return null;
                }

                $totalDaysLeft = floor(($importEndDate->getTimestamp() - $lastDateImported->getTimestamp()) / 86400);
                $totalDaysImported = floor(($lastDateImported->getTimestamp() - $importRangeStart->getTimestamp()) / 86400);

                $rateOfImport = $totalDaysImported / $daysRunning;
                if ($rateOfImport <= 0) {
                    return lcfirst(Piwik::translate('General_Unknown'));
                }

                $totalTimeLeftInDays = ceil($totalDaysLeft / $rateOfImport);

                return max(0, $totalTimeLeftInDays);
            } else {
                return lcfirst(Piwik::translate('General_Unknown'));
            }
        } catch (\Exception $ex) {
            return lcfirst(Piwik::translate('General_Unknown'));
        }
    }

    public function setImportedDateRange($idSite, Date $startDate = null, Date $endDate = null)
    {
        $optionName = self::IMPORTED_DATE_RANGE_PREFIX . $idSite;
        $dates = $this->getImportedDateRange($idSite);

        if (!empty($startDate)
            && (empty($dates[0]) || $startDate->isEarlier(Date::factory($dates[0])))
        ) {
            $dates[0] = $startDate->toString();
        } else if (empty($dates[0])
            && !empty($endDate)
        ) {
            $dates[0] = $endDate->toString();
        }

        if (!empty($endDate)
            && (empty($dates[1]) || $endDate->isLater(Date::factory($dates[1])))
        ) {
            $dates[1] = $endDate->toString();
        }

        $value = implode(',', $dates);

        Option::set($optionName, $value);
    }

    private function getDatetime($str)
    {
        try {
            return Date::factory($str)->getDatetime();
        } catch (\Exception $ex) {
            return $str;
        }
    }

    private function getDateString($str)
    {
        try {
            return Date::factory($str)->toString();
        } catch (\Exception $ex) {
            return $str;
        }
    }

    public function reImportDateRange($idSite, Date $startDate, Date $endDate)
    {
        if ($endDate->isEarlier($startDate)) {
            throw new \Exception(Piwik::translate('GoogleAnalyticsImporter_InvalidDateRange'));
        }

        $status = $this->getImportStatus($idSite);

        // if we're currently reimporting, then we're using last_date_imported, so don't overwrite it
        if (empty($status['reimport_ranges'])) {
            $status['last_date_imported'] = null;
        }

        $status['reimport_ranges'][] = [$startDate->toString(), $endDate->toString()];

        if ($status['status'] == self::STATUS_FINISHED) {
            $status['status'] = self::STATUS_ONGOING;
        }

        $this->saveStatus($status);
    }

    // TODO: we don't ever need to remove an entry that isn't the first one, this should be
    //       shiftReImportEntryIfEquals(...)
    public function removeReImportEntry($idSite, $datesToImport)
    {
        $status = $this->getImportStatus($idSite);
        if (!isset($status['reimport_ranges'])) {
            $status['reimport_ranges'] = [];
            $this->saveStatus($status);
            return;
        }

        if (empty($status['reimport_ranges'])) {
            return;
        }

        $status['reimport_ranges'] = array_filter($status['reimport_ranges'], function ($s) use ($datesToImport) {
            if (!is_array($s)
                || count($s) != 2
            ) {
                return false;
            }
            return $s[0] != $datesToImport[0] || $s[1] != $datesToImport[1];
        });
        $status['reimport_ranges'] = array_values($status['reimport_ranges']);

        if (!empty($status['reimport_ranges'])) { // we're done w/ one range, so if there are more, reset last_date_imported
            $status['last_date_imported'] = null;
        }

        $this->saveStatus($status);
    }

    public function isInImportedDateRange($period, $date, $idSite = null) // TODO: cache the result of this
    {
        $range = $this->getImportedSiteImportDateRange($idSite);
        if (empty($range)) {
            return false;
        }

        list($startDate, $endDate) = $range;

        $periodObj = Factory::build($period, $date);
        if ($startDate->isLater($periodObj->getDateEnd())
            || $endDate->isEarlier($periodObj->getDateStart())
        ) {
            return false;
        }

        return true;
    }

    public function getImportedSiteImportDateRange($idSite = null)
    {
        $idSite = $idSite ?: Common::getRequestVar('idSite', false);
        if (empty($idSite)) {
            return null;
        }

        try {
            $status = $this->getImportStatus($idSite);
        } catch (\Exception $ex) {
            $status = [];
        }

        $lastDateImported = isset($status['last_date_imported']) ? $status['last_date_imported'] : null;
        $mainImportProgress = isset($status['main_import_progress']) ? $status['main_import_progress'] : null;

        $importedDateRange = $this->getImportedDateRange($idSite);
        if (empty($importedDateRange)
            || empty($importedDateRange[0])
            || empty($importedDateRange[1])
        ) {
            return null;
        }

        $startDate = Date::factory($importedDateRange[0] ?: Site::getCreationDateFor($idSite));
        $endDate = Date::factory($importedDateRange[1] ?: $mainImportProgress ?: $lastDateImported ?: $startDate);

        return [$startDate, $endDate];
    }

    public function finishImportIfNothingLeft($idSite)
    {
        $status = $this->getImportStatus($idSite);

        $mainImportProgress = null;
        if (!empty($status['main_import_progress'])) {
            $mainImportProgress = $status['main_import_progress'];
        } else if (!empty($status['last_date_imported'])) {
            $mainImportProgress = $status['last_date_imported'];
        }

        if (!empty($status['import_range_end'])
            && !empty($mainImportProgress)
            && ($mainImportProgress == $status['import_range_end']
                || Date::factory($mainImportProgress)->isLater(Date::factory($status['import_range_end'])))
            && empty($status['reimport_ranges'])
        ) {
            $this->finishedImport($idSite);
        }
    }
}