<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\PrivacyManager;

use HTML_QuickForm2_DataSource_Array;
use Piwik\Common;
use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\DataTable\DataTableInterface;
use Piwik\Date;
use Piwik\Db;
use Piwik\Metrics;
use Piwik\Option;
use Piwik\Period;
use Piwik\Period\Range;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\Goals\Archiver;
use Piwik\Plugins\Installation\FormDefaultSettings;
use Piwik\Site;
use Piwik\Tracker\Cache;
use Piwik\Tracker\GoalManager;
use Piwik\View;

/**
 * Specifically include this for Tracker API (which does not use autoloader)
 */
require_once PIWIK_INCLUDE_PATH . '/plugins/PrivacyManager/DoNotTrackHeaderChecker.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/PrivacyManager/IPAnonymizer.php';

/**
 */
class PrivacyManager extends Plugin
{
    const OPTION_LAST_DELETE_PIWIK_LOGS = "lastDelete_piwik_logs";
    const OPTION_LAST_DELETE_UNUSED_LOG_ACTIONS = "lastDelete_piwik_unused_log_actions";
    const OPTION_LAST_DELETE_PIWIK_REPORTS = 'lastDelete_piwik_reports';
    const OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL = "lastDelete_piwik_logs_initial";
    const OPTION_USERID_SALT = 'useridsalt';


    // options for data purging feature array[configName => configSection]
    public static $purgeDataOptions = array(
        'delete_logs_enable'                   => 'Deletelogs',
        'delete_logs_schedule_lowest_interval' => 'Deletelogs',
        'delete_logs_older_than'               => 'Deletelogs',
        'delete_logs_max_rows_per_query'       => 'Deletelogs',
        'delete_logs_unused_actions_schedule_lowest_interval' => 'Deletelogs',
        'enable_auto_database_size_estimate'   => 'Deletelogs',
        'enable_database_size_estimate'        => 'Deletelogs',
        'delete_reports_enable'                => 'Deletereports',
        'delete_reports_older_than'            => 'Deletereports',
        'delete_reports_keep_basic_metrics'    => 'Deletereports',
        'delete_reports_keep_day_reports'      => 'Deletereports',
        'delete_reports_keep_week_reports'     => 'Deletereports',
        'delete_reports_keep_month_reports'    => 'Deletereports',
        'delete_reports_keep_year_reports'     => 'Deletereports',
        'delete_reports_keep_range_reports'    => 'Deletereports',
        'delete_reports_keep_segment_reports'  => 'Deletereports',
    );

    private $dntChecker = null;
    private $ipAnonymizer = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->dntChecker = new DoNotTrackHeaderChecker();
        $this->ipAnonymizer = new IPAnonymizer();
    }

    public function install()
    {
        StaticContainer::get('Piwik\Plugins\PrivacyManager\Model\LogDataAnonymizations')->install();
    }

    public function uninstall()
    {
        StaticContainer::get('Piwik\Plugins\PrivacyManager\Model\LogDataAnonymizations')->install();
    }

    /**
     * Returns true if it is likely that the data for this report has been purged and if the
     * user should be told about that.
     *
     * In order for this function to return true, the following must also be true:
     * - The data table for this report must either be empty or not have been fetched.
     * - The period of this report is not a multiple period.
     * - The date of this report must be older than the delete_reports_older_than config option.
     * @param  DataTableInterface $dataTable
     * @return bool
     */
    public static function hasReportBeenPurged($dataTable)
    {
        $strPeriod = Common::getRequestVar('period', false);
        $strDate   = Common::getRequestVar('date', false);

        if (false !== $strPeriod
            && false !== $strDate
            && (is_null($dataTable)
                || (!empty($dataTable) && $dataTable->getRowsCount() == 0))
        ) {
            $reportDate = self::getReportDate($strPeriod, $strDate);

            if (empty($reportDate)) {
                return false;
            }

            $reportYear = $reportDate->toString('Y');
            $reportMonth = $reportDate->toString('m');

            if (static::shouldReportBePurged($reportYear, $reportMonth)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param DataTable $dataTable
     * @param int|null $logsOlderThan If set, it is assumed that log deletion is enabled with the given amount of days
     * @return bool|void
     */
    public static function haveLogsBeenPurged($dataTable, $logsOlderThan = null)
    {
        if (!empty($dataTable) && $dataTable->getRowsCount() != 0) {
            return false;
        }

        if ($logsOlderThan === null) {
            $settings = PrivacyManager::getPurgeDataSettings();

            if ($settings['delete_logs_enable'] == 0) {
                return false;
            }

            $logsOlderThan = $settings['delete_logs_older_than'];
        }

        $logsOlderThan = (int) $logsOlderThan;

        $strPeriod = Common::getRequestVar('period', false);
        $strDate   = Common::getRequestVar('date', false);

        if (false === $strPeriod || false === $strDate) {
            return false;
        }

        $logsOlderThan = Date::now()->subDay(1 + $logsOlderThan);
        $reportDate = self::getReportDate($strPeriod, $strDate);

        if (empty($reportDate)) {
            return false;
        }

        return $reportDate->isEarlier($logsOlderThan);
    }

    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getJavaScriptFiles'         => 'getJsFiles',
            'AssetManager.getStylesheetFiles'         => 'getStylesheetFiles',
            'Tracker.setTrackerCacheGeneral'          => 'setTrackerCacheGeneral',
            'Tracker.isExcludedVisit'                 => array($this->dntChecker, 'checkHeaderInTracker'),
            'Tracker.setVisitorIp'                    => array($this->ipAnonymizer, 'setVisitorIpAddress'),
            'Installation.defaultSettingsForm.init'   => 'installationFormInit',
            'Installation.defaultSettingsForm.submit' => 'installationFormSubmit',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Template.pageFooter' => 'renderPrivacyPolicyLinks',
        );
    }

    public function isTrackerPlugin()
    {
        return true;
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'CoreAdminHome_SettingsSaveSuccess';
        $translationKeys[] = 'CoreAdminHome_OptOutExplanation';
        $translationKeys[] = 'CoreAdminHome_OptOutExplanationIntro';
    }

    public function setTrackerCacheGeneral(&$cacheContent)
    {
        $config       = new Config();
        $cacheContent = $config->setTrackerCacheGeneral($cacheContent);
        $cacheContent[self::OPTION_USERID_SALT] = self::getUserIdSalt();

        $purgeSettings = PrivacyManager::getPurgeDataSettings();
        $cacheContent['delete_logs_enable'] = $purgeSettings['delete_logs_enable'];
        $cacheContent['delete_logs_schedule_lowest_interval'] = $purgeSettings['delete_logs_schedule_lowest_interval'];
        $cacheContent['delete_logs_older_than'] = $purgeSettings['delete_logs_older_than'];
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/PrivacyManager/angularjs/report-deletion.model.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/schedule-report-deletion/schedule-report-deletion.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/anonymize-ip/anonymize-ip.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/do-not-track-preference/do-not-track-preference.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/delete-old-logs/delete-old-logs.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/delete-old-reports/delete-old-reports.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/opt-out-customizer/opt-out-customizer.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/opt-out-customizer/opt-out-customizer.directive.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/manage-gdpr/managegdpr.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/manage-gdpr/managegdpr.directive.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/anonymize-log-data/anonymize-log-data.controller.js";
        $jsFiles[] = "plugins/PrivacyManager/angularjs/anonymize-log-data/anonymize-log-data.directive.js";
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/PrivacyManager/angularjs/opt-out-customizer/opt-out-customizer.directive.less";
        $stylesheets[] = "plugins/PrivacyManager/angularjs/manage-gdpr/managegdpr.directive.less";
        $stylesheets[] = "plugins/PrivacyManager/stylesheets/gdprOverview.less";
        $stylesheets[] = "plugins/PrivacyManager/angularjs/anonymize-log-data/anonymize-log-data.directive.less";
        $stylesheets[] = "plugins/PrivacyManager/stylesheets/footerLinks.less";
    }

    /**
     * Customize the Installation "default settings" form.
     *
     * @param FormDefaultSettings $form
     */
    public function installationFormInit(FormDefaultSettings $form)
    {
        $form->addElement('checkbox', 'do_not_track', null,
            array(
                'content' => '<div class="form-help">' . Piwik::translate('PrivacyManager_DoNotTrack_EnabledMoreInfo') . '</div> &nbsp;&nbsp;' . Piwik::translate('PrivacyManager_DoNotTrack_Enable')
            ));
        $form->addElement('checkbox', 'anonymise_ip', null,
            array(
                'content' => '<div class="form-help">' . Piwik::translate('PrivacyManager_AnonymizeIpExtendedHelp', array('213.34.51.91', '213.34.0.0')) . '</div> &nbsp;&nbsp;' . Piwik::translate('PrivacyManager_AnonymizeIpInlineHelp')
            ));

        // default values
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array(array(
            'do_not_track' => $this->dntChecker->isActive(),
            'anonymise_ip' => IPAnonymizer::isActive(),
        )));
    }

    /**
     * Process the submit on the Installation "default settings" form.
     *
     * @param FormDefaultSettings $form
     */
    public function installationFormSubmit(FormDefaultSettings $form)
    {
        $doNotTrack = (bool) $form->getSubmitValue('do_not_track');
        $dntChecker = new DoNotTrackHeaderChecker();
        if ($doNotTrack) {
            $dntChecker->activate();
        } else {
            $dntChecker->deactivate();
        }

        $anonymiseIp = (bool) $form->getSubmitValue('anonymise_ip');
        if ($anonymiseIp) {
            IPAnonymizer::activate();
        } else {
            IPAnonymizer::deactivate();
        }
    }

    /**
     * Returns the settings for the data purging feature.
     *
     * @return array
     */
    public static function getPurgeDataSettings()
    {
        $settings = array();

        // load settings from ini config
        $config = PiwikConfig::getInstance();
        foreach (self::$purgeDataOptions as $configKey => $configSection) {
            $values = $config->$configSection;
            $settings[$configKey] = $values[$configKey];
        }

        if (!Controller::isDataPurgeSettingsEnabled()) {
            return $settings;
        }

        // load the settings for the data purging settings
        foreach (self::$purgeDataOptions as $configName => $configSection) {
            $value = Option::get($configName);
            if ($value !== false) {
                $settings[$configName] = $value;
            }
        }

        return $settings;
    }

    /**
     * Saves the supplied data purging settings.
     *
     * @param array $settings The settings to save.
     */
    public static function savePurgeDataSettings($settings)
    {
        foreach (self::$purgeDataOptions as $configName => $configSection) {
            if (isset($settings[$configName])) {
                Option::set($configName, $settings[$configName]);
            }
        }

        Cache::deleteTrackerCache();
    }

    /**
     * Deletes old archived data (reports & metrics).
     *
     * Archive tables are not optimized after, as that is handled by a separate scheduled task
     * in CoreAdminHome. This is a scheduled task and will only execute every N days. The number
     * of days is determined by the delete_logs_schedule_lowest_interval config option.
     *
     * If delete_reports_enable is set to 1, old archive data is deleted. The following
     * config options can tweak this behavior:
     * - delete_reports_older_than: The number of months after which archive data is considered
     *                              old. The current month is not considered when applying this
     *                              value.
     * - delete_reports_keep_basic_metrics: If set to 1, keeps certain metric data. Right now,
     *                                      all metric data is kept.
     * - delete_reports_keep_day_reports: If set to 1, keeps old daily reports.
     * - delete_reports_keep_week_reports: If set to 1, keeps old weekly reports.
     * - delete_reports_keep_month_reports: If set to 1, keeps old monthly reports.
     * - delete_reports_keep_year_reports: If set to 1, keeps old yearly reports.
     */
    public function deleteReportData()
    {
        $settings = self::getPurgeDataSettings();

        // Make sure, data deletion is enabled
        if ($settings['delete_reports_enable'] == 0) {
            return false;
        }

        // make sure purging should run at this time (unless this is a forced purge)
        if (!$this->shouldPurgeData($settings, self::OPTION_LAST_DELETE_PIWIK_REPORTS, 'delete_logs_schedule_lowest_interval')) {
            return false;
        }

        // set last run time
        Option::set(self::OPTION_LAST_DELETE_PIWIK_REPORTS, Date::factory('today')->getTimestamp());

        ReportsPurger::make($settings, self::getAllMetricsToKeep())->purgeData();
        return true;
    }

    /**
     * Deletes old raw data based on the options set in the Deletelogs config
     * section. This is a scheduled task and will only execute every N days. The number
     * of days is determined by the delete_logs_schedule_lowest_interval config option.
     *
     * If delete_logs_enable is set to 1, old data in the log_visit, log_conversion,
     * log_conversion_item and log_link_visit_action tables is deleted. The following
     * options can tweak this behavior:
     * - delete_logs_older_than: The number of days after which raw data is considered old.
     *
     * @ToDo: return number of Rows deleted in last run; Display age of "oldest" row to help the user setting
     *        the day offset;
     */
    public function deleteLogData()
    {
        $settings = self::getPurgeDataSettings();

        // Make sure, data deletion is enabled
        if ($settings['delete_logs_enable'] == 0) {
            return false;
        }

        // make sure purging should run at this time
        if (!$this->shouldPurgeData($settings, self::OPTION_LAST_DELETE_PIWIK_LOGS, 'delete_logs_schedule_lowest_interval')) {
            return false;
        }

        /*
         * Tell the DB that log deletion has run BEFORE deletion is executed;
         * If deletion / table optimization exceeds execution time, other tasks maybe prevented of being executed
         * every time, when the schedule is triggered.
         */
        $lastDeleteDate = Date::factory("today")->getTimestamp();
        Option::set(self::OPTION_LAST_DELETE_PIWIK_LOGS, $lastDeleteDate);

        $shouldDeleteUnusedLogActions = $this->shouldPurgeData($settings, self::OPTION_LAST_DELETE_UNUSED_LOG_ACTIONS, 'delete_logs_unused_actions_schedule_lowest_interval');
        if ($shouldDeleteUnusedLogActions) {
            Option::set(self::OPTION_LAST_DELETE_UNUSED_LOG_ACTIONS, $lastDeleteDate);
        }

        // execute the purge
        /** @var LogDataPurger $logDataPurger */
        $logDataPurger = StaticContainer::get('Piwik\Plugins\PrivacyManager\LogDataPurger');
        $logDataPurger->purgeData($settings['delete_logs_older_than'], $shouldDeleteUnusedLogActions);

        return true;
    }

    /**
     * Returns an array describing what data would be purged if both raw data & report
     * purging is invoked.
     *
     * The returned array maps table names with the number of rows that will be deleted.
     * If the table name is mapped with -1, the table will be dropped.
     *
     * @param array $settings The config options to use in the estimate. If null, the real
     *                        options are used.
     * @return array
     */
    public static function getPurgeEstimate($settings = null)
    {
        if (is_null($settings)) {
            $settings = self::getPurgeDataSettings();
        }

        $result = array();

        if ($settings['delete_logs_enable']) {
            /** @var LogDataPurger $logDataPurger */
            $logDataPurger = StaticContainer::get('Piwik\Plugins\PrivacyManager\LogDataPurger');
            $result = array_merge($result, $logDataPurger->getPurgeEstimate($settings['delete_logs_older_than']));
        }

        if ($settings['delete_reports_enable']) {
            $reportsPurger = ReportsPurger::make($settings, self::getAllMetricsToKeep());
            $result = array_merge($result, $reportsPurger->getPurgeEstimate());
        }

        return $result;
    }

    private static function getReportDate($strPeriod, $strDate)
    {
        // if range, only look at the first date
        if ($strPeriod == 'range') {

            $idSite = Common::getRequestVar('idSite', '');

            if (intval($idSite) != 0) {
                $site     = new Site($idSite);
                $timezone = $site->getTimezone();
            } else {
                $timezone = 'UTC';
            }

            $period     = new Range('range', $strDate, $timezone);
            $reportDate = $period->getDateStart();

        } elseif (Period::isMultiplePeriod($strDate, $strPeriod)) {

            // if a multiple period, this function is irrelevant
            return false;

        }  else {
            // otherwise, use the date as given
            $reportDate = Date::factory($strDate);
        }

        return $reportDate;
    }

    /**
     * Returns true if a report with the given year & month should be purged or not.
     *
     * If reportsOlderThan is set to null or not supplied, this function will check if
     * a report should be purged, based on existing configuration. In this case, if
     * delete_reports_enable is set to 0, this function will return false.
     *
     * @param int $reportDateYear The year of the report in question.
     * @param int $reportDateMonth The month of the report in question.
     * @param int|Date $reportsOlderThan If an int, the number of months a report must be older than
     *                                         in order to be purged. If a date, the date a report must be
     *                                         older than in order to be purged.
     * @return bool
     */
    public static function shouldReportBePurged($reportDateYear, $reportDateMonth, $reportsOlderThan = null)
    {
        // if no 'older than' value/date was supplied, use existing config
        if (is_null($reportsOlderThan)) {
            // if report deletion is not enabled, the report shouldn't be purged
            $settings = self::getPurgeDataSettings();
            if ($settings['delete_reports_enable'] == 0) {
                return false;
            }

            $reportsOlderThan = $settings['delete_reports_older_than'];
        }

        // if a integer was supplied, assume it is the number of months a report must be older than
        if (!($reportsOlderThan instanceof Date)) {
            $reportsOlderThan = Date::factory('today')->subMonth(1 + $reportsOlderThan);
        }

        return ReportsPurger::shouldReportBePurged(
            $reportDateYear, $reportDateMonth, $reportsOlderThan);
    }

    /**
     * Returns the general metrics to keep when the 'delete_reports_keep_basic_metrics'
     * config is set to 1.
     */
    private static function getMetricsToKeep()
    {
        return array('nb_uniq_visitors', 'nb_visits', 'nb_users', 'nb_actions', 'max_actions',
                     'sum_visit_length', 'bounce_count', 'nb_visits_converted', 'nb_conversions',
                     'revenue', 'quantity', 'price', 'orders');
    }

    /**
     * Returns the goal metrics to keep when the 'delete_reports_keep_basic_metrics'
     * config is set to 1.
     */
    private static function getGoalMetricsToKeep()
    {
        // keep all goal metrics
        return array_values(Metrics::$mappingFromIdToNameGoal);
    }

    /**
     * Returns the names of metrics that should be kept when purging as they appear in
     * archive tables.
     */
    public static function getAllMetricsToKeep()
    {
        $metricsToKeep = self::getMetricsToKeep();

        // convert goal metric names to correct archive names
        if (Common::isGoalPluginEnabled()) {
            $goalMetricsToKeep = self::getGoalMetricsToKeep();

            $maxGoalId = self::getMaxGoalId();

            // for each goal metric, there's a different name for each goal, including the overview,
            // the order report & cart report
            foreach ($goalMetricsToKeep as $metric) {
                for ($i = 1; $i <= $maxGoalId; ++$i) // maxGoalId can be 0
                {
                    $metricsToKeep[] = Archiver::getRecordName($metric, $i);
                }

                $metricsToKeep[] = Archiver::getRecordName($metric);
                $metricsToKeep[] = Archiver::getRecordName($metric, GoalManager::IDGOAL_ORDER);
                $metricsToKeep[] = Archiver::getRecordName($metric, GoalManager::IDGOAL_CART);
            }
        }

        return $metricsToKeep;
    }

    /**
     * Returns true if one of the purge data tasks should run now, false if it shouldn't.
     */
    private function shouldPurgeData($settings, $lastRanOption, $setting)
    {
        // Log deletion may not run until it is once rescheduled (initial run). This is the
        // only way to guarantee the calculated next scheduled deletion time.
        $initialDelete = Option::get(self::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL);
        if (empty($initialDelete)) {
            Option::set(self::OPTION_LAST_DELETE_PIWIK_LOGS_INITIAL, 1);
            return false;
        }

        // Make sure, log purging is allowed to run now
        $lastDelete = Option::get($lastRanOption);
        $deleteIntervalDays = $settings[$setting];
        $deleteIntervalSeconds = $this->getDeleteIntervalInSeconds($deleteIntervalDays);

        if ($lastDelete === false ||
            $lastDelete === '' ||
            ((int)$lastDelete + $deleteIntervalSeconds) <= time()
        ) {
            return true;
        } else // not time to run data purge
        {
            return false;
        }
    }

    function getDeleteIntervalInSeconds($deleteInterval)
    {
        return (int)$deleteInterval * 24 * 60 * 60;
    }

    private static function getMaxGoalId()
    {
        return Db::fetchOne("SELECT MAX(idgoal) FROM " . Common::prefixTable('goal'));
    }

    /**
     * Returns a unique salt used for pseudonimisation of user id only
     *
     * @return string
     */
    public static function getUserIdSalt()
    {
        $salt = Option::get(self::OPTION_USERID_SALT);
        if (empty($salt)) {
            $salt = Common::getRandomString($len = 40, $alphabet = "abcdefghijklmnoprstuvwxyzABCDEFGHIJKLMNOPRSTUVWXYZ0123456789_-$");
            Option::set(self::OPTION_USERID_SALT, $salt, 1);
        }
        return $salt;
    }

    public function renderPrivacyPolicyLinks(&$out)
    {
        $settings = new SystemSettings();

        if (!$this->shouldRenderFooterLinks($settings)) {
            return;
        }

        $privacyPolicyUrl     = $settings->privacyPolicyUrl->getValue();
        $termsAndConditionUrl = $settings->termsAndConditionUrl->getValue();

        if (empty($privacyPolicyUrl) && empty($termsAndConditionUrl)) {
            return;
        }

        $view = new View('@PrivacyManager/footerLinks.twig');
        $view->privacyPolicyUrl  = $privacyPolicyUrl;
        $view->termsAndCondition = $termsAndConditionUrl;
        $out .= $view->render();
    }

    private function shouldRenderFooterLinks(SystemSettings $settings)
    {
        if (Piwik::isUserIsAnonymous()) {
            return true;
        }

        $module = Common::getRequestVar('module', false);
        if ($module == 'Widgetize') {
            return (bool)$settings->showInEmbeddedWidgets->getValue();
        }

        return false;
    }
}
