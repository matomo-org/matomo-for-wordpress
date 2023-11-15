<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\ScheduledReports;

use Exception;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Context;
use Piwik\Date;
use Piwik\Db;
use Piwik\Development;
use Piwik\Filesystem;
use Piwik\Http;
use Piwik\Log;
use Piwik\NoAccessException;
use Piwik\Period;
use Piwik\Piwik;
use Piwik\Plugins\ImageGraph\ImageGraph;
use Piwik\Plugins\LanguagesManager\LanguagesManager;
use Piwik\Plugins\SegmentEditor\API as APISegmentEditor;
use Piwik\Plugins\SitesManager\API as SitesManagerApi;
use Piwik\ReportRenderer;
use Piwik\Scheduler\RetryableException;
use Piwik\Scheduler\Schedule\Schedule;
use Piwik\Site;
use Piwik\Translation\Translator;
use Piwik\Log\LoggerInterface;

/**
 * The ScheduledReports API lets you manage Scheduled Email reports, as well as generate, download or email any existing report.
 *
 * "generateReport" will generate the requested report (for a specific date range, website and in the requested language).
 * "sendReport" will send the report by email to the recipients specified for this report.
 *
 * You can also get the list of all existing reports via "getReports", create new reports via "addReport",
 * or manage existing reports with "updateReport" and "deleteReport".
 * See also the documentation about <a href='http://matomo.org/docs/email-reports/' rel='noreferrer' target='_blank'>Scheduled Email reports</a> in Matomo.
 *
 * @method static \Piwik\Plugins\ScheduledReports\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    const VALIDATE_PARAMETERS_EVENT = 'ScheduledReports.validateReportParameters';
    const GET_REPORT_PARAMETERS_EVENT = 'ScheduledReports.getReportParameters';
    const GET_REPORT_METADATA_EVENT = 'ScheduledReports.getReportMetadata';
    const GET_REPORT_TYPES_EVENT = 'ScheduledReports.getReportTypes';
    const GET_REPORT_FORMATS_EVENT = 'ScheduledReports.getReportFormats';
    const GET_RENDERER_INSTANCE_EVENT = 'ScheduledReports.getRendererInstance';
    const PROCESS_REPORTS_EVENT = 'ScheduledReports.processReports';
    const GET_REPORT_RECIPIENTS_EVENT = 'ScheduledReports.getReportRecipients';
    const ALLOW_MULTIPLE_REPORTS_EVENT = 'ScheduledReports.allowMultipleReports';
    const SEND_REPORT_EVENT = 'ScheduledReports.sendReport';

    const OUTPUT_DOWNLOAD = 1;
    const OUTPUT_SAVE_ON_DISK = 2;
    const OUTPUT_INLINE = 3;
    const OUTPUT_RETURN = 4;

    private $enableSaveReportOnDisk = false;

    // static cache storing reports
    public static $cache = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Creates a new report and schedules it.
     *
     * @param int $idSite
     * @param string $description Report description
     * @param string $period Schedule frequency: day, week or month
     * @param int $hour Hour (0-23) when the report should be sent
     * @param string $reportType 'email' or any other format provided via the ScheduledReports.getReportTypes hook
     * @param string $reportFormat 'pdf', 'html' or any other format provided via the ScheduledReports.getReportFormats hook
     * @param array $reports array of reports
     * @param array $parameters array of parameters
     * @param bool|int $idSegment Segment Identifier
     * @param string $evolutionPeriodFor If set to 'each', the evolution graphs cover each day within the period. If set to 'prev',
     *                                   evolution graphs cover the previous N periods.
     * @param int|null $evolutionPeriodN The previous N periods to query in evolution graphs if $evolutionPeriodFor is 'each'.
     * @param string $periodParam the period for the report, eg 'day', 'week', 'month', 'year'.
     *
     * @return int idReport generated
     */
    public function addReport(
        $idSite,
        $description,
        $period,
        $hour,
        $reportType,
        $reportFormat,
        $reports,
        $parameters,
        $idSegment = false,
        $evolutionPeriodFor = 'prev',
        $evolutionPeriodN = null,
        $periodParam = null
    ) {
        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasViewAccess($idSite);

        $currentUser = Piwik::getCurrentUserLogin();
        self::ensureLanguageSetForUser($currentUser);

        self::validateCommonReportAttributes($period, $hour, $description, $idSegment, $reportType, $reportFormat, $evolutionPeriodFor, $evolutionPeriodN);

        if (null !== $periodParam) {
            self::validatePeriodParam($periodParam);
        }

        // report parameters validations
        $parameters = self::validateReportParameters($reportType, $parameters);

        // validation of requested reports
        $reports = self::validateRequestedReports($idSite, $reportType, $reports);

        $idReport = $this->getModel()->createReport([
             'idsite'      => $idSite,
             'login'       => $currentUser,
             'description' => $description,
             'idsegment'   => $idSegment,
             'period'      => $period,
             'period_param' => $periodParam,
             'hour'        => $hour,
             'type'        => $reportType,
             'format'      => $reportFormat,
             'parameters'  => $parameters,
             'reports'     => $reports,
             'ts_created'  => Date::now()->getDatetime(),
             'deleted'     => 0,
             'evolution_graph_within_period' => $evolutionPeriodFor == 'each',
             'evolution_graph_period_n' => $evolutionPeriodN ?: ImageGraph::getDefaultGraphEvolutionLastPeriods(),
        ]);

        return $idReport;
    }

    private static function ensureLanguageSetForUser($currentUser)
    {
        $lang = Request::processRequest('LanguagesManager.getLanguageForUser', [
            'login' => $currentUser,
        ]);

        if (empty($lang)) {
            Request::processRequest('LanguagesManager.setLanguageForUser', [
                'login' => $currentUser,
                'languageCode' => LanguagesManager::getLanguageCodeForCurrentUser(),
            ]);
        }
    }

    /**
     * Updates an existing report.
     *
     * @see addReport()
     */
    public function updateReport(
        $idReport,
        $idSite,
        $description,
        $period,
        $hour,
        $reportType,
        $reportFormat,
        $reports,
        $parameters,
        $idSegment = false,
        $evolutionPeriodFor = 'prev',
        $evolutionPeriodN = null,
        $periodParam = null
    ) {
        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasViewAccess($idSite);

        $scheduledReports = $this->getReports($idSite, $periodSearch = false, $idReport);
        $report   = reset($scheduledReports);
        $idReport = $report['idreport'];

        $currentUser = Piwik::getCurrentUserLogin();
        self::ensureLanguageSetForUser($currentUser);

        self::validateCommonReportAttributes($period, $hour, $description, $idSegment, $reportType, $reportFormat, $evolutionPeriodFor, $evolutionPeriodN);

        if (null !== $periodParam) {
            self::validatePeriodParam($periodParam);
        }

        // report parameters validations
        $parameters = self::validateReportParameters($reportType, $parameters);

        // validation of requested reports
        $reports = self::validateRequestedReports($idSite, $reportType, $reports);

        $this->getModel()->updateReport($idReport, [
            'description' => $description,
            'idsegment'   => $idSegment,
            'period'      => $period,
            'period_param' => $periodParam,
            'hour'        => $hour,
            'type'        => $reportType,
            'format'      => $reportFormat,
            'parameters'  => $parameters,
            'reports'     => $reports,
            'evolution_graph_within_period' => $evolutionPeriodFor == 'each',
            'evolution_graph_period_n' => $evolutionPeriodN ?: ImageGraph::getDefaultGraphEvolutionLastPeriods(),
        ]);

        self::$cache = [];
    }

    /**
     * Deletes a specific report
     *
     * @param int $idReport
     */
    public function deleteReport($idReport)
    {
        $APIScheduledReports = $this->getReports($idSite = false, $periodSearch = false, $idReport);
        $report = reset($APIScheduledReports);
        Piwik::checkUserHasSuperUserAccessOrIsTheUser($report['login']);

        $this->getModel()->updateReport($idReport, [
            'deleted' => 1,
        ]);

        self::$cache = [];
    }

    /**
     * Returns the list of reports matching the passed parameters
     *
     * @param bool|int $idSite If specified, will filter reports that belong to a specific idsite
     * @param bool|string $period If specified, will filter reports that are scheduled for this period (day,week,month)
     * @param bool|int $idReport If specified, will filter the report that has the given idReport
     * @param bool $ifSuperUserReturnOnlySuperUserReports
     * @param bool|int $idSegment If specified, will filter the report that has the given idSegment
     * @throws Exception
     * @return array
     */
    public function getReports($idSite = false, $period = false, $idReport = false, $ifSuperUserReturnOnlySuperUserReports = false, $idSegment = false)
    {
        Piwik::checkUserHasSomeViewAccess();

        $cacheKey = (int)$idSite . '.' . (string)$period . '.' . (int)$idReport . '.' . (int)$ifSuperUserReturnOnlySuperUserReports;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $sqlWhere = '';
        $bind = [];

        // Super user gets all reports back, other users only their own
        if (
            !Piwik::hasUserSuperUserAccess()
            || $ifSuperUserReturnOnlySuperUserReports
        ) {
            $sqlWhere .= "AND login = ?";
            $bind[] = Piwik::getCurrentUserLogin();
        }

        if (!empty($period)) {
            $this->validateReportPeriod($period);
            $sqlWhere .= " AND period = ? ";
            $bind[] = $period;
        }
        if (!empty($idSite)) {
            Piwik::checkUserHasViewAccess($idSite);
            $sqlWhere .= " AND " . Common::prefixTable('site') . ".idsite = ?";
            $bind[] = $idSite;
        }
        if (!empty($idReport)) {
            $sqlWhere .= " AND idreport = ?";
            $bind[] = $idReport;
        }
        if (!empty($idSegment)) {
            $sqlWhere .= " AND idsegment = ?";
            $bind[] = $idSegment;
        }

        // Joining with the site table to work around pre-1.3 where reports could still be linked to a deleted site
        $reports = Db::fetchAll("SELECT report.*
								FROM " . Common::prefixTable('report') . " AS `report`
									JOIN " . Common::prefixTable('site') . "
									USING (idsite)
								WHERE deleted = 0
									$sqlWhere", $bind);
        // When a specific report was requested and not found, throw an error
        if (
            $idReport !== false
            && empty($reports)
        ) {
            throw new Exception("Requested report couldn't be found.");
        }

        foreach ($reports as &$report) {
            // decode report parameters
            $report['parameters'] = json_decode($report['parameters'], true);

            // decode report list
            $report['reports'] = json_decode($report['reports'], true);

            if (
                !empty($report['parameters']['additionalEmails'])
                && is_array($report['parameters']['additionalEmails'])
            ) {
                $report['parameters']['additionalEmails'] = array_values($report['parameters']['additionalEmails']);
            }

            if (empty($report['evolution_graph_period_n'])) {
                $report['evolution_graph_period_n'] = ImageGraph::getDefaultGraphEvolutionLastPeriods();
            }

            // default the period param to use to the email schedule
            if (empty($report['period_param'])) {
                $periodParam = $report['period'] == Schedule::PERIOD_NEVER ? Schedule::PERIOD_DAY : $report['period'];
                $report['period_param'] = $periodParam;
            }
        }

        // static cache
        self::$cache[$cacheKey] = $reports;

        return $reports;
    }

    /**
     * Generates a report file.
     *
     * @param int $idReport ID of the report to generate.
     * @param string $date YYYY-MM-DD
     * @param bool|false|string $language If not passed, will use default language.
     * @param bool|false|int $outputType 1 = download report, 3 = output report in browser, 4 = return report content to caller, defaults to download
     * @param bool|false|string $period If not specified, will default to the report's period set when creating the report
     * @param bool|false|string $reportFormat 'pdf', 'html' or any other format provided via the ScheduledReports.getReportFormats hook
     * @param bool|false|array $parameters array of parameters
     * @return array|void
     */
    public function generateReport(
        $idReport,
        $date,
        $language = false,
        $outputType = false,
        $period = false,
        $reportFormat = false,
        $parameters = false
    ) {
        Piwik::checkUserIsNotAnonymous();

        if (!$this->enableSaveReportOnDisk && $outputType == self::OUTPUT_SAVE_ON_DISK) {
            $outputType = self::OUTPUT_DOWNLOAD;
        }

        /** @var Translator $translator */
        $translator = StaticContainer::get('Piwik\Translation\Translator');

        // load specified language
        if (empty($language)) {
            $language = $translator->getDefaultLanguage();
        }

        $translator->setCurrentLanguage($language);

        $reports = $this->getReports($idSite = false, $_period = false, $idReport);
        $report = reset($reports);

        $idSite = $report['idsite'];
        $login  = $report['login'];
        $reportType = $report['type'];

        $this->checkUserHasViewPermission($login, $idSite);

        // override report period
        if (empty($period)) {
            $period = $report['period_param'];
        }

        $this->checkSinglePeriod($period, $date);

        // override report format
        if (!empty($reportFormat)) {
            self::validateReportFormat($reportType, $reportFormat);
            $report['format'] = $reportFormat;
        } else {
            $reportFormat = $report['format'];
        }

        // override and/or validate report parameters
        $report['parameters'] = json_decode(
            self::validateReportParameters($reportType, empty($parameters) ? $report['parameters'] : $parameters),
            true
        );

        $originalShowEvolutionWithinSelectedPeriod = Config::getInstance()->General['graphs_show_evolution_within_selected_period'];
        $originalDefaultEvolutionGraphLastPeriodsAmount = Config::getInstance()->General['graphs_default_evolution_graph_last_days_amount'];
        try {
            Config::setSetting('General', 'graphs_show_evolution_within_selected_period', (bool)$report['evolution_graph_within_period']);
            Config::setSetting('General', 'graphs_default_evolution_graph_last_days_amount', $report['evolution_graph_period_n']);

            // available reports
            $availableReportMetadata = \Piwik\Plugins\API\API::getInstance()->getReportMetadata($idSite);

            // we need to lookup which reports metadata are registered in this report
            $reportMetadata = [];
            foreach ($availableReportMetadata as $metadata) {
                if (in_array($metadata['uniqueId'], $report['reports'])) {
                    $reportMetadata[] = $metadata;
                }
            }

            // the report will be rendered with the first 23 rows and will aggregate other rows in a summary row
            // 23 rows table fits in one portrait page
            $initialFilterTruncate = Common::getRequestVar('filter_truncate', false);
            $_GET['filter_truncate'] = Config::getInstance()->General['scheduled_reports_truncate'];

            $prettyDate = null;
            $processedReports = [];
            $segment = self::getSegment($report['idsegment']);
            foreach ($reportMetadata as $action) {
                $apiModule = $action['module'];
                $apiAction = $action['action'];
                $apiParameters = [];
                if (isset($action['parameters'])) {
                    $apiParameters = $action['parameters'];
                }

                $mustRestoreGET = false;

                // all Websites dashboard should not be truncated in the report
                if ($apiModule == 'MultiSites') {
                    $mustRestoreGET = $_GET;
                    $_GET['enhanced'] = true;

                    if ($apiAction == 'getAll') {
                        $_GET['filter_truncate'] = false;
                        $_GET['filter_limit'] = -1; // show all websites in all websites report

                        // when a view/admin user created a report, workaround the fact that "Super User"
                        // is enforced in Scheduled tasks, and ensure Multisites.getAll only return the websites that this user can access
                        $userLogin = $report['login'];
                        if (
                            !empty($userLogin)
                            && !Piwik::hasTheUserSuperUserAccess($userLogin)
                        ) {
                            $_GET['_restrictSitesToLogin'] = $userLogin;
                        }
                    }
                }

                $params = [
                    'idSite' => $idSite,
                    'period' => $period,
                    'date' => $date,
                    'apiModule' => $apiModule,
                    'apiAction' => $apiAction,
                    'apiParameters' => $apiParameters,
                    'flat' => 1,
                    'idGoal' => false,
                    'language' => $language,
                    'serialize' => 0,
                    'format' => 'original'
                ];

                if ($segment != null) {
                    $params['segment'] = urlencode($segment['definition']);
                } else {
                    $params['segment'] = false;
                }

                try {
                    $processedReport = Request::processRequest('API.getProcessedReport', $params);
                } catch (\Exception $ex) {
                    // NOTE: can't use warning or error because the log message will appear in the UI as a notification
                    $this->logger->info("Error getting '?{report}' when generating scheduled report: {exception}", [
                        'report' => Http::buildQuery($params),
                        'exception' => $ex->getMessage(),
                    ]);

                    $this->logger->debug($ex);

                    continue;
                }

                $processedReport['segment'] = $segment;

                // TODO add static method getPrettyDate($period, $date) in Period
                $prettyDate = $processedReport['prettyDate'];

                if ($mustRestoreGET) {
                    $_GET = $mustRestoreGET;
                }

                $processedReports[] = $processedReport;
            }
        } finally {
            Config::setSetting('General', 'graphs_show_evolution_within_selected_period', $originalShowEvolutionWithinSelectedPeriod);
            Config::setSetting('General', 'graphs_default_evolution_graph_last_days_amount', $originalDefaultEvolutionGraphLastPeriodsAmount);

            // restore filter truncate parameter value
            if ($initialFilterTruncate !== false) {
                $_GET['filter_truncate'] = $initialFilterTruncate;
            }
        }

        /**
         * Triggered when generating the content of scheduled reports.
         *
         * This event can be used to modify the report data or report metadata of one or more reports
         * in a scheduled report, before the scheduled report is rendered and delivered.
         *
         * TODO: list data available in $report or make it a new class that can be documented (same for
         *       all other events that use a $report)
         *
         * @param array &$processedReports The list of processed reports in the scheduled
         *                                 report. Entries includes report data and metadata for each report.
         * @param string $reportType A string ID describing how the scheduled report will be sent, eg,
         *                           `'sms'` or `'email'`.
         * @param string $outputType The output format of the report, eg, `'html'`, `'pdf'`, etc.
         * @param array $report An array describing the scheduled report that is being
         *                      generated.
         */
        Piwik::postEvent(
            self::PROCESS_REPORTS_EVENT,
            [&$processedReports, $reportType, $outputType, $report]
        );

        $reportRenderer = null;

        /**
         * Triggered when obtaining a renderer instance based on the scheduled report output format.
         *
         * Plugins that provide new scheduled report output formats should use this event to
         * handle their new report formats.
         *
         * @param ReportRenderer &$reportRenderer This variable should be set to an instance that
         *                                        extends {@link \Piwik\ReportRenderer} by one of the event
         *                                        subscribers.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         * @param string $outputType The output format of the report, eg, `'html'`, `'pdf'`, etc.
         * @param array $report An array describing the scheduled report that is being
         *                      generated.
         */
        Piwik::postEvent(
            self::GET_RENDERER_INSTANCE_EVENT,
            [&$reportRenderer, $reportType, $outputType, $report]
        );

        if (is_null($reportRenderer)) {
            throw new Exception("A report renderer was not supplied in the event " . self::GET_RENDERER_INSTANCE_EVENT);
        }

        // init report renderer
        $reportRenderer->setIdSite($idSite);
        $reportRenderer->setLocale($language);
        $reportRenderer->setReport($report);

        // render report
        $description = str_replace(["\r", "\n"], ' ', Common::unsanitizeInputValue($report['description']));

        [$reportSubject, $reportTitle] = self::getReportSubjectAndReportTitle(Common::unsanitizeInputValue(Site::getNameFor($idSite)), $report['reports']);

        // if reporting for a segment, use the segment's name in the title
        if (is_array($segment) && strlen($segment['name'])) {
            $reportTitle .= " - " . $segment['name'];
        }
        $filename = "$reportTitle - $prettyDate - $description";

        $reportRenderer->renderFrontPage($reportTitle, $prettyDate, $description, $reportMetadata, $segment);
        array_walk($processedReports, [$reportRenderer, 'renderReport']);

        switch ($outputType) {
            case self::OUTPUT_SAVE_ON_DISK:
                // only used for SendReport

                $outputFilename = strtoupper($reportFormat) . ' ' . ucfirst($reportType) . ' Report - ' . $idReport . '.' . $date . '.' . $idSite . '.' . $language;
                $outputFilename .= ' - ' . Common::getRandomString(40, 'abcdefghijklmnoprstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVXYZ_');
                $outputFilename = $reportRenderer->sendToDisk($outputFilename);

                $additionalFiles = $this->getAttachments($reportRenderer, $report, $processedReports, $prettyDate);

                return [
                    $outputFilename,
                    $prettyDate,
                    $reportSubject,
                    $reportTitle,
                    $additionalFiles,
                ];

                break;

            case self::OUTPUT_INLINE:
                $reportRenderer->sendToBrowserInline($filename);
                break;

            case self::OUTPUT_RETURN:
                return $reportRenderer->getRenderedReport();
                break;

            default:
            case self::OUTPUT_DOWNLOAD:
                $reportRenderer->sendToBrowserDownload($filename);
                break;
        }
    }

    public function sendReport($idReport, $period = false, $date = false, $force = false)
    {
        Piwik::checkUserIsNotAnonymous();

        $reports = $this->getReports($idSite = false, false, $idReport);
        $report = reset($reports);

        if (!empty($period)) {
            self::validatePeriodParam($period);
            $report['period_param'] = $period;
        }

        if (empty($date)) {
            $date = Date::now()->subPeriod(1, $report['period'])->toString();
        }

        Context::changeIdSite($report['idsite'], function () use ($report, $idReport, $period, $date, $force) {

            $language = \Piwik\Plugins\LanguagesManager\API::getInstance()->getLanguageForUser($report['login']);

            // generate report
            $this->enableSaveReportOnDisk = true;
            try {
                [$outputFilename, $prettyDate, $reportSubject, $reportTitle, $additionalFiles] =
                    $this->generateReport(
                        $idReport,
                        $date,
                        $language,
                        self::OUTPUT_SAVE_ON_DISK,
                        $report['period_param']
                    );
            } catch (NoAccessException $e) {
                // This might occur if for some reason a report exists where the user does no longer have access to the
                // configured site. Normally those reports should be automatically deleted.
                Log::info("Skipping report as user does no longer have access to configured site");
                return;
            } catch (\Throwable $e) {
                $this->enableSaveReportOnDisk = false;
                throw new RetryableException($e->getMessage());
            }

            $this->enableSaveReportOnDisk = false;

            if (!file_exists($outputFilename)) {
                throw new Exception("The report file wasn't found in $outputFilename");
            }

            $contents = file_get_contents($outputFilename);

            if (empty($contents)) {
                Log::warning("Scheduled report file '%s' exists but is empty!", $outputFilename);
            }

            $reportType = $report['type'];

            /**
             * Triggered when sending scheduled reports.
             *
             * Plugins that provide new scheduled report transport mediums should use this event to
             * send the scheduled report.
             *
             * @param string $reportType A string ID describing how the report is sent, eg,
             *                           `'sms'` or `'email'`.
             * @param array $report An array describing the scheduled report that is being
             *                      generated.
             * @param string $contents The contents of the scheduled report that was generated
             *                         and now should be sent.
             * @param string $filename The path to the file where the scheduled report has
             *                         been saved.
             * @param string $prettyDate A prettified date string for the data within the
             *                           scheduled report.
             * @param string $reportSubject A string describing what's in the scheduled
             *                              report.
             * @param string $reportTitle The scheduled report's given title (given by a Matomo user).
             * @param array $additionalFiles The list of additional files that should be
             *                               sent with this report.
             * @param \Piwik\Period $period The period for which the report has been generated.
             * @param boolean $force A report can only be sent once per period. Setting this to true
             *                       will force to send the report even if it has already been sent.
             */
            Piwik::postEvent(
                self::SEND_REPORT_EVENT,
                [
                    &$reportType,
                    $report,
                    $contents,
                    $filename = basename($outputFilename),
                    $prettyDate,
                    $reportSubject,
                    $reportTitle,
                    $additionalFiles,
                    \Piwik\Period\Factory::build($report['period_param'], $date),
                    $force
                ]
            );

            // Update flag in DB
            $now = Date::now()->getDatetime();
            $this->getModel()->updateReport($report['idreport'], ['ts_last_sent' => $now]);

            if (!Development::isEnabled()) {
                @chmod($outputFilename, 0600);
                Filesystem::deleteFileIfExists($outputFilename);
            }
        });
    }

    private function getModel()
    {
        return new Model();
    }

    private static function getReportSubjectAndReportTitle($websiteName, $reports)
    {
        // if the only report is "All websites", we don't display the site name
        $reportTitle = $websiteName;
        $reportSubject = $websiteName;
        if (
            count($reports) == 1
            && $reports[0] == 'MultiSites_getAll'
        ) {
            $reportSubject = Piwik::translate('General_MultiSitesSummary');
            $reportTitle = $reportSubject;
        }

        return [$reportSubject, $reportTitle];
    }

    private static function validateReportParameters($reportType, $parameters)
    {
        // get list of valid parameters
        $availableParameters = [];

        /**
         * Triggered when gathering the available parameters for a scheduled report type.
         *
         * Plugins that provide their own scheduled report transport mediums should use this
         * event to list the available report parameters for their transport medium.
         *
         * @param array $availableParameters The list of available parameters for this report type.
         *                                   This is an array that maps parameter IDs with a boolean
         *                                   that indicates whether the parameter is mandatory or not.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         */
        Piwik::postEvent(self::GET_REPORT_PARAMETERS_EVENT, [&$availableParameters, $reportType]);

        // unset invalid parameters
        $availableParameterKeys = array_keys($availableParameters);
        foreach ($parameters as $key => $value) {
            if (!in_array($key, $availableParameterKeys)) {
                unset($parameters[$key]);
            }
        }

        // test that all required parameters are provided
        foreach ($availableParameters as $parameter => $mandatory) {
            if ($mandatory && !isset($parameters[$parameter])) {
                throw new Exception('Missing parameter : ' . $parameter);
            }
        }

        /**
         * Triggered when validating the parameters for a scheduled report.
         *
         * Plugins that provide their own scheduled reports backend should use this
         * event to validate the custom parameters defined with {@link ScheduledReports::getReportParameters()}.
         *
         * @param array $parameters The list of parameters for the scheduled report.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         */
        Piwik::postEvent(self::VALIDATE_PARAMETERS_EVENT, [&$parameters, $reportType]);

        return json_encode($parameters);
    }

    private static function validateAndTruncateDescription(&$description)
    {
        $description = substr($description, 0, 250);
    }

    private static function validateRequestedReports($idSite, $reportType, $requestedReports)
    {
        if (!self::allowMultipleReports($reportType)) {
            //sms can only contain one report, we silently discard all but the first
            $requestedReports = array_slice($requestedReports, 0, 1);
        }

        // retrieve available reports
        $availableReportMetadata = self::getReportMetadata($idSite, $reportType);

        $availableReportIds = [];
        foreach ($availableReportMetadata as $reportMetadata) {
            $availableReportIds[] = $reportMetadata['uniqueId'];
        }

        foreach ($requestedReports as $report) {
            if (!in_array($report, $availableReportIds)) {
                throw new Exception("Report $report is unknown or not available for report type '$reportType'.");
            }
        }

        return json_encode($requestedReports);
    }

    private static function validateCommonReportAttributes(
        $period,
        $hour,
        &$description,
        &$idSegment,
        $reportType,
        $reportFormat,
        $evolutionPeriodFor,
        $evolutionPeriodN
    ) {
        self::validateReportPeriod($period);
        self::validateReportHour($hour);
        self::validateAndTruncateDescription($description);
        self::validateIdSegment($idSegment);
        self::validateReportType($reportType);
        self::validateReportFormat($reportType, $reportFormat);
        self::validateEvolutionPeriod($evolutionPeriodFor, $evolutionPeriodN);
    }

    private static function validateReportPeriod($period)
    {
        $availablePeriods = ['day', 'week', 'month', 'never'];
        if (!in_array($period, $availablePeriods)) {
            throw new Exception('Period schedule must be one of the following: ' . implode(', ', $availablePeriods) . ' (got ' . $period . ')');
        }
    }

    private static function validatePeriodParam($period)
    {
        $periodValidator = new Period\PeriodValidator();
        $allowedPeriods = array_flip($periodValidator->getPeriodsAllowedForAPI());
        unset($allowedPeriods['range']);

        if (!array_key_exists($period, $allowedPeriods)) {
            throw new Exception('Report period must be one of the following: ' . implode(', ', array_keys($allowedPeriods)) . ' (got ' . $period . ')');
        }
    }

    private static function validateReportHour($hour)
    {
        if (!is_numeric($hour) || $hour < 0 || $hour > 23) {
            throw new Exception('Invalid hour schedule. Should be anything from 0 to 23 inclusive.');
        }
    }

    private static function validateIdSegment(&$idSegment)
    {
        if (empty($idSegment) || (is_numeric($idSegment) && $idSegment == 0)) {
            $idSegment = null;
        } elseif (!is_numeric($idSegment)) {
            throw new Exception('Invalid segment identifier. Should be an integer.');
        } elseif (self::getSegment($idSegment) == null) {
            throw new Exception('Segment with id ' . $idSegment . ' does not exist or SegmentEditor is not activated.');
        }
    }

    private static function validateReportType($reportType)
    {
        $reportTypes = array_keys(self::getReportTypes());

        if (!in_array($reportType, $reportTypes)) {
            throw new Exception(
                'Report type \'' . $reportType . '\' not valid. Try one of the following ' . implode(', ', $reportTypes)
            );
        }
    }

    private static function validateReportFormat($reportType, $reportFormat)
    {
        $reportFormats = array_keys(self::getReportFormats($reportType));

        if (!in_array($reportFormat, $reportFormats)) {
            throw new Exception(
                Piwik::translate(
                    'General_ExceptionInvalidReportRendererFormat',
                    [$reportFormat, implode(', ', $reportFormats)]
                )
            );
        }
    }

    private static function validateEvolutionPeriod($evolutionPeriodFor, $evolutionPeriodN)
    {
        if ($evolutionPeriodFor !== 'prev' && $evolutionPeriodFor !== 'each') {
            throw new \Exception('Invalid evolutionPeriodFor value, can only be "prev" or "each" (got ' . $evolutionPeriodFor . ').');
        }

        if ($evolutionPeriodFor === 'each' && !empty($evolutionPeriodN)) {
            throw new \Exception('The evolutionPeriodN param has no effect when evolutionPeriodFor is "each".');
        }

        if (
            !empty($evolutionPeriodN)
            && (!is_numeric($evolutionPeriodN) || (int)$evolutionPeriodN < 0)
        ) {
            throw new \Exception('Evolution period amount must be a positive number (got ' . $evolutionPeriodN . ').');
        }
    }

    /**
     * @ignore
     */
    public static function getReportMetadata($idSite, $reportType)
    {
        $availableReportMetadata = [];

        /**
         * TODO: change this event so it returns a list of API methods instead of report metadata arrays.
         * Triggered when gathering the list of Matomo reports that can be used with a certain
         * transport medium.
         *
         * Plugins that provide their own transport mediums should use this
         * event to list the Matomo reports that their backend supports.
         *
         * @param array &$availableReportMetadata An array containing report metadata for each supported
         *                                        report.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         * @param int $idSite The ID of the site we're getting available reports for.
         */
        Piwik::postEvent(
            self::GET_REPORT_METADATA_EVENT,
            [&$availableReportMetadata, $reportType, $idSite]
        );

        return $availableReportMetadata;
    }

    /**
     * @ignore
     */
    public static function allowMultipleReports($reportType)
    {
        $allowMultipleReports = null;

        /**
         * Triggered when we're determining if a scheduled report transport medium can
         * handle sending multiple Matomo reports in one scheduled report or not.
         *
         * Plugins that provide their own transport mediums should use this
         * event to specify whether their backend can send more than one Matomo report
         * at a time.
         *
         * @param bool &$allowMultipleReports Whether the backend type can handle multiple
         *                                    Matomo reports or not.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         */
        Piwik::postEvent(
            self::ALLOW_MULTIPLE_REPORTS_EVENT,
            [&$allowMultipleReports, $reportType]
        );
        return $allowMultipleReports;
    }

    /**
     * @ignore
     */
    public static function getReportTypes()
    {
        $reportTypes = [];

        /**
         * Triggered when gathering all available transport mediums.
         *
         * Plugins that provide their own transport mediums should use this
         * event to make their medium available.
         *
         * @param array &$reportTypes An array mapping transport medium IDs with the paths to those
         *                            mediums' icons. Add your new backend's ID to this array.
         */
        Piwik::postEvent(self::GET_REPORT_TYPES_EVENT, [&$reportTypes]);

        return $reportTypes;
    }

    /**
     * @ignore
     */
    public static function getReportFormats($reportType)
    {
        $reportFormats = [];

        /**
         * Triggered when gathering all available scheduled report formats.
         *
         * Plugins that provide their own scheduled report format should use
         * this event to make their format available.
         *
         * @param array &$reportFormats An array mapping string IDs for each available
         *                              scheduled report format with icon paths for those
         *                              formats. Add your new format's ID to this array.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         */
        Piwik::postEvent(
            self::GET_REPORT_FORMATS_EVENT,
            [&$reportFormats, $reportType]
        );

        return $reportFormats;
    }

    /**
     * @ignore
     */
    public static function getReportRecipients($report)
    {
        $recipients = [];

        /**
         * Triggered when getting the list of recipients of a scheduled report.
         *
         * Plugins that provide their own scheduled report transport medium should use this event
         * to extract the list of recipients their backend's specific scheduled report
         * format.
         *
         * @param array &$recipients An array of strings describing each of the scheduled
         *                           reports recipients. Can be, for example, a list of email
         *                           addresses or phone numbers or whatever else your plugin
         *                           uses.
         * @param string $reportType A string ID describing how the report is sent, eg,
         *                           `'sms'` or `'email'`.
         * @param array $report An array describing the scheduled report that is being
         *                      generated.
         */
        Piwik::postEvent(self::GET_REPORT_RECIPIENTS_EVENT, [&$recipients, $report['type'], $report]);

        return $recipients;
    }

    /**
     * @ignore
     */
    public static function getSegment($idSegment)
    {
        if (self::isSegmentEditorActivated() && !empty($idSegment)) {
            $segment = APISegmentEditor::getInstance()->get($idSegment);

            if ($segment) {
                return $segment;
            }
        }

        return null;
    }

    /**
     * @ignore
     */
    public static function isSegmentEditorActivated()
    {
        return \Piwik\Plugin\Manager::getInstance()->isPluginActivated('SegmentEditor');
    }

    private function getAttachments($reportRenderer, $report, $processedReports, $prettyDate)
    {
        return $reportRenderer->getAttachments($report, $processedReports, $prettyDate);
    }

    private function checkUserHasViewPermission($login, $idSite)
    {
        if (empty($idSite)) {
            return;
        }

        $idSitesUserHasAccess = SitesManagerApi::getInstance()->getSitesIdWithAtLeastViewAccess($login);

        if (
            empty($idSitesUserHasAccess)
            || !in_array($idSite, $idSitesUserHasAccess)
        ) {
            throw new NoAccessException(Piwik::translate('General_ExceptionPrivilege', ["'view'"]));
        }
    }

    private function checkSinglePeriod($period, $date)
    {
        if (Period::isMultiplePeriod($date, $period)) {
            throw new Http\BadRequestException("This API method does not support multiple periods.");
        }
    }
}
