<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics;

use DI\NotFoundException;
use Piwik\CliMulti\CliPhp;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Plugins\GoogleAnalyticsImporter\Commands\ImportReports;
use Piwik\Plugins\GoogleAnalyticsImporter\Logger\LogToSingleFileProcessor;
use Piwik\SettingsServer;
use Piwik\Site;
use Psr\Log\LoggerInterface;

class Tasks extends \Piwik\Plugin\Tasks
{
    const DATE_FINISHED_ENV_VAR = 'MATOMO_GOOGLE_IMPORT_END_DATE_TO_ARCHIVE';
    const SECONDS_IN_YEAR = 31557600; // 60 * 60 * 24 * 365.25

    public function schedule()
    {
        $this->hourly('resumeScheduledImports');

        // we also run the archive command immediately after an import. the task is a safety net in case
        // that doesn't work for some reason.
        $this->daily('archiveImportedReports');
    }

    public function resumeScheduledImports()
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        foreach ($statuses as $status) {
            if (empty($status['idSite'])
                || !is_numeric($status['idSite'])
                || empty($status['status'])
            ) {
                $logger->info("Found broken import status entry.");
                continue;
            }

            if ($status['status'] == ImportStatus::STATUS_FINISHED) {
                continue;
            }

            if (ImportStatus::isImportRunning($status)) {
                continue;
            }

            if ($status['status'] == ImportStatus::STATUS_ERRORED) {
                $logger->info('Google Analytics import into site with ID = {idSite} encountered an unexpected error last time, attempting to resume.', [
                    'idSite' => $status['idSite'],
                ]);
            } else {
                $logger->info('Resuming import into site with ID = {idSite}.', [
                    'idSite' => $status['idSite'],
                ]);

                $importStatus->resumeImport($status['idSite']);
            }

            self::startImport($status);
        }

        $logger->info('Done scheduling imports.');
    }

    public function archiveImportedReports()
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        $importStatus = StaticContainer::get(ImportStatus::class);
        $statuses = $importStatus->getAllImportStatuses();

        foreach ($statuses as $status) {
            $this->startArchive($status);
        }

        $logger->info('Done running archive commands.');
    }

    public static function startImport($status)
    {
        if (ImportStatus::isImportRunning($status)) {
            return;
        }

        $logToSingleFile = StaticContainer::get('GoogleAnalyticsImporter.logToSingleFile');

        $idSite = $status['idSite'];
        $isVerboseLoggingEnabled = !empty($status['is_verbose_logging_enabled']);

        $hostname = Config::getHostname();

        $importLogFile = self::getImportLogFile($idSite, $hostname, $logToSingleFile);
        if (!is_writable($importLogFile)
            && !is_writable(dirname($importLogFile))
        ) {
            $importLogFile = '/dev/null';
        }

        $cliPhp = new CliPhp();
        $phpBinary = $cliPhp->findPhpBinary() ?: 'php';

        $pathToConsole = '/console';
        if (defined('PIWIK_TEST_MODE')) {
            $pathToConsole = '/tests/PHPUnit/proxy/console';
        }

        $nohup = self::getNohupCommandIfPresent();

        $command = "$nohup $phpBinary " . PIWIK_INCLUDE_PATH . $pathToConsole . ' ';
        if (!empty($hostname)) {
            $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
        }
        $command .= 'googleanalyticsimporter:import-reports --idsite=' . (int)$idSite;
        if ($isVerboseLoggingEnabled) {
            $command .= ' -vvv';
        }

        if ($logToSingleFile || !$isVerboseLoggingEnabled) {
            $command .= ' >> ';
        } else {
            $command .= ' > ';
        }

        $command .= $importLogFile . ' 2>&1 &';

        $logger = StaticContainer::get(LoggerInterface::class);
        $logger->debug("Import command: {command}", ['command' => $command]);

        static::exec($shouldUsePassthru = false, $command);
    }

    public static function startArchive(array $status, $wait = false, $lastDayArchived = null, $checkIsRunning = true)
    {
        $logger = StaticContainer::get(LoggerInterface::class);

        if (empty($status['idSite'])
            || !is_numeric($status['idSite'])
            || empty($status['status'])
        ) {
            $logger->info("Found broken import status entry.");
            return;
        }

        if (empty($status['last_date_imported'])) {
            $logger->info("Import for site ID = {$status['idSite']} has not imported any data yet, skipping archive job.");
            return;
        }

        if ($checkIsRunning && ImportStatus::isImportRunning($status)) {
            $logger->info("Import is currently running for site ID = {$status['idSite']}, not starting archiving right now.");
            return;
        }

        try {
            $lastDateImported = Date::factory($status['last_date_imported']);
        } catch (\Exception $ex) {
            $logger->info("Found broken import status entry: invalid last imported date '{$status['last_date_imported']}' for site ID = {$status['idSite']}");
            return;
        }

        if (empty($lastDayArchived)) {
            try {
                $lastDayArchived = empty($status['last_day_archived']) ? null : Date::factory($status['last_day_archived']);
            } catch (\Exception $ex) {
                $logger->info("Found broken import status entry: invalid last day archived date '{$status['last_day_archived']}' for site ID = {$status['idSite']}");
                return;
            }
        }

        if (!empty($lastDayArchived)
            && $lastDateImported->isEarlier($lastDayArchived)
        ) {
            $logger->info("Last archived date ({$lastDayArchived->toString()}) is earlier than last import date ({$lastDateImported->toString()}, no need to archive for site ID = {$status['idSite']}");
            return;
        }

        $idSite = (int) $status['idSite'];

        if (empty($lastDayArchived)) {
            if (!empty($status['import_range_start'])) {
                $lastDayArchived = Date::factory($status['import_range_start']);
            } else {
                $lastDayArchived = Date::factory(Site::getCreationDateFor($idSite));
            }
        }

        $hostname = Config::getHostname();

        $logToSingleFile = StaticContainer::get('GoogleAnalyticsImporter.logToSingleFile');

        $archiveLogFile = self::getArchiveLogFile($idSite, $hostname, $logToSingleFile);
        if (!is_writable($archiveLogFile)
            && !is_writable(dirname($archiveLogFile))
        ) {
            $archiveLogFile = '/dev/null';
        }

        $dateRange = $lastDayArchived->toString() . ',' . $lastDateImported->toString();

        $pathToConsole = '/console';
        if (defined('PIWIK_TEST_MODE')) {
            $pathToConsole = '/tests/PHPUnit/proxy/console';
        }

        $cliPhp = new CliPhp();
        $phpBinary = $cliPhp->findPhpBinary() ?: 'php';

        $nohup = self::getNohupCommandIfPresent();

        $command = self::DATE_FINISHED_ENV_VAR . '=' . $lastDateImported->toString();
        if (StaticContainer::get('GoogleAnalyticsImporter.logToSingleFile')) {
            $command .= ' MATOMO_GA_IMPORTER_LOG_TO_SINGLE_FILE=' . $idSite;
        }
        $command .= " $nohup $phpBinary " . PIWIK_INCLUDE_PATH . $pathToConsole . ' ';
        if (!empty($hostname)) {
            $command .= '--matomo-domain=' . escapeshellarg($hostname) . ' ';
        }
        $command .= 'core:archive --disable-scheduled-tasks --force-idsites=' . $idSite . ' --force-periods=week,month,year --force-date-range=' . $dateRange;

        if (!$wait) {
            if ($logToSingleFile) {
                $command .= ' >> ';
            } else {
                $command .= ' > ';
            }
            $command .= $archiveLogFile . ' 2>&1 &';
        }

        $logger->debug("Archive command for imported site: {command}", ['command' => $command]);

        static::exec($shouldUsePassthru = $wait, $command);
    }

    public static function exec($shouldUsePassthru, $command)
    {
        if ($shouldUsePassthru) {
            passthru($command);
        } else {
            exec($command);
        }
    }

    public static function getArchiveLogFile($idSite, $hostname, $logToSingleFile)
    {
        if ($logToSingleFile) {
            return StaticContainer::get('path.tmp') . '/logs/gaimport.log';
        }
        return StaticContainer::get('path.tmp') . '/logs/gaimportlog.archive.' . $idSite . '.' . $hostname . '.log';
    }

    public static function getImportLogFile($idSite, $hostname, $logToSingleFile)
    {
        if ($logToSingleFile) {
            return StaticContainer::get('path.tmp') . '/logs/gaimport.log';
        }
        return StaticContainer::get('path.tmp') . '/logs/gaimportlog.' . $idSite . '.' . $hostname . '.log';
    }

    private static function sanitizeArg($gaDimension)
    {
        return preg_replace('/[^a-zA-Z0-9:_-]]/', '', $gaDimension);
    }

    private static function getNohupCommandIfPresent()
    {
        try {
            $useNohup = StaticContainer::get('GoogleAnalyticsImporter.useNohup');
            if (!$useNohup) {
                return '';
            }
        } catch (NotFoundException $ex) {
            // ignore
        }

        if (SettingsServer::isWindows()) {
            return '';
        }

        return 'nohup';
    }
}
