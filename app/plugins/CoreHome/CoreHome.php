<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreHome;

use Piwik\Archive\ArchiveInvalidator;
use Piwik\Columns\ComputedMetricFactory;
use Piwik\Columns\MetricsList;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DbHelper;
use Piwik\IP;
use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\ComputedMetric;
use Piwik\Plugin\ThemeStyles;
use Piwik\SettingsServer;
use Piwik\Tracker\Model as TrackerModel;

/**
 *
 */
class CoreHome extends \Piwik\Plugin
{
    /**
     * Defines a widget container layout that will display all widgets within a container inside a "tab" menu
     * where on the left side a link is shown for each widget and on the right side the selected widget.
     * @api
     */
    const WIDGET_CONTAINER_LAYOUT_BY_DIMENSION = 'ByDimension';

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'AssetManager.getStylesheetFiles'        => 'getStylesheetFiles',
            'AssetManager.getJavaScriptFiles'        => 'getJsFiles',
            'AssetManager.filterMergedJavaScripts'   => 'filterMergedJavaScripts',
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Metric.addComputedMetrics'              => 'addComputedMetrics',
            'Request.initAuthenticationObject' => ['function' => 'checkAllowedIpsOnAuthentication', 'before' => true],
            'AssetManager.addStylesheets' => 'addStylesheets',
            'Request.dispatchCoreAndPluginUpdatesScreen' => ['function' => 'checkAllowedIpsOnAuthentication', 'before' => true],
            'Tracker.setTrackerCacheGeneral' => 'setTrackerCacheGeneral',
        );
    }

    public function isTrackerPlugin()
    {
        return true;
    }

    public function setTrackerCacheGeneral(&$cacheGeneral)
    {
        /** @var ArchiveInvalidator $archiveInvalidator */
        $archiveInvalidator = StaticContainer::get(ArchiveInvalidator::class);
        $cacheGeneral[ArchiveInvalidator::TRACKER_CACHE_KEY] = $archiveInvalidator->getAllRememberToInvalidateArchivedReportsLater();

        $hasIndex = DbHelper::tableHasIndex(Common::prefixTable('log_visit'), 'index_idsite_idvisitor');
        $cacheGeneral[TrackerModel::CACHE_KEY_INDEX_IDSITE_IDVISITOR] = $hasIndex;
    }

    public function addStylesheets(&$mergedContent)
    {
        $themeStyles = ThemeStyles::get();
        $mergedContent = $themeStyles->toLessCode() . "\n" . $mergedContent;
    }

    public function checkAllowedIpsOnAuthentication()
    {
        if (SettingsServer::isTrackerApiRequest()) {
            // authenticated tracking requests should always work
            return;
        }

        $isApi = Piwik::getModule() === 'API' && (Piwik::getAction() == '' || Piwik::getAction() == 'index');

        if ($isApi) {
            // will be checked in API itself to make sure we return an API response in the proper format.
            return;
        }

        $list = new LoginAllowlist();
        if ($list->shouldCheckAllowlist()) {
            $ip = IP::getIpFromHeader();
            $list->checkIsAllowed($ip);
        }
    }

    public function addComputedMetrics(MetricsList $list, ComputedMetricFactory $computedMetricFactory)
    {
        $metrics = $list->getMetrics();
        foreach ($metrics as $metric) {
            if ($metric instanceof ArchivedMetric && $metric->getDimension()) {
                $metricName = $metric->getName();
                if ($metric->getDbTableName() === 'log_visit'
                    && $metricName !== 'nb_uniq_visitors'
                    && $metricName !== 'nb_visits'
                    && strpos($metricName, ArchivedMetric::AGGREGATION_SUM_PREFIX) === 0) {
                    $metric = $computedMetricFactory->createComputedMetric($metric->getName(), 'nb_visits', ComputedMetric::AGGREGATION_AVG);
                    $list->addMetric($metric);
                }
            }
        }
    }

    public function filterMergedJavaScripts(&$mergedContent)
    {
        $mergedContent = preg_replace('/(sourceMappingURL=(.*?).map)/', '', $mergedContent);
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "node_modules/jquery-ui-dist/jquery-ui.min.css";
        $stylesheets[] = "node_modules/jquery-ui-dist/jquery-ui.theme.min.css";
        $stylesheets[] = "node_modules/materialize-css/dist/css/materialize.min.css";
        $stylesheets[] = "plugins/Morpheus/stylesheets/base/bootstrap.css";
        $stylesheets[] = "plugins/Morpheus/stylesheets/base/icons.css";
        $stylesheets[] = "plugins/Morpheus/stylesheets/base.less";
        $stylesheets[] = "plugins/Morpheus/stylesheets/main.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/coreHome.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/dataTable.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/cloud.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/jquery.ui.autocomplete.css";
        $stylesheets[] = "plugins/CoreHome/stylesheets/sparklineColors.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/promo.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/color_manager.css";
        $stylesheets[] = "plugins/CoreHome/stylesheets/sparklineColors.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/notification.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/zen-mode.less";
        $stylesheets[] = "plugins/CoreHome/stylesheets/layout.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/enrichedheadline/enrichedheadline.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/notification/notification.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/quick-access/quick-access.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/selector/selector.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/reporting-page/reportingpage.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/report-export/reportexport.popover.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/widget-bydimension-container/widget-bydimension-container.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/progressbar/progressbar.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/date-range-picker/date-range-picker.component.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/period-date-picker/period-date-picker.component.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/period-selector/period-selector.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/multipairfield/multipairfield.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/dropdown-menu/dropdown-menu.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/sparkline/sparkline.component.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/field-array/field-array.directive.less";
        $stylesheets[] = "plugins/CoreHome/angularjs/comparisons/comparisons.component.less";
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "node_modules/jquery/dist/jquery.min.js";
        $jsFiles[] = "node_modules/jquery-ui-dist/jquery-ui.min.js";
        $jsFiles[] = "node_modules/materialize-css/dist/js/materialize.min.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/materialize-bc.js";
        $jsFiles[] = "node_modules/jquery.browser/dist/jquery.browser.min.js";
        $jsFiles[] = "node_modules/jquery.scrollto/jquery.scrollTo.min.js";
        $jsFiles[] = "node_modules/sprintf-js/dist/sprintf.min.js";
        $jsFiles[] = "node_modules/mousetrap/mousetrap.min.js";
        $jsFiles[] = 'node_modules/angular/angular.min.js';
        $jsFiles[] = "node_modules/angular-sanitize/angular-sanitize.min.js";
        $jsFiles[] = "node_modules/angular-animate/angular-animate.min.js";
        $jsFiles[] = "node_modules/angular-cookies/angular-cookies.min.js";
        $jsFiles[] = "node_modules/ng-dialog/js/ngDialog.min.js";
        $jsFiles[] = "plugins/Morpheus/javascripts/piwikHelper.js";
        $jsFiles[] = "plugins/Morpheus/javascripts/ajaxHelper.js";
        $jsFiles[] = "plugins/Morpheus/javascripts/layout.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/require.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/uiControl.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/dataTable.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/dataTable_rowactions.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/popover.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/broadcast.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/calendar.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/sparkline.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/corehome.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/top_controls.js";
        $jsFiles[] = "libs/jqplot/jqplot-custom.min.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/color_manager.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/notification.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/numberFormatter.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/zen-mode.js";
        $jsFiles[] = "plugins/CoreHome/javascripts/noreferrer.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/piwikApp.config.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/service.module.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/global-ajax-queue.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/piwik.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/piwik-api.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/piwik-url.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/report-metadata-model.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/reporting-pages-model.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/services/periods.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/filter.module.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/translate.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/startfrom.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/evolution.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/length.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/trim.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/pretty-url.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/escape.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/htmldecode.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/urldecode.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/filters/ucfirst.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/directive.module.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/attributes.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/field-condition.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/show-sensitive-data.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/autocomplete-matched.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/focus-anywhere-but-here.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/ignore-click.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/onenter.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/focusif.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/dialog.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/translate.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/dropdown-button.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/select-on-focus.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/side-nav.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/common/directives/string-to-number.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/piwikApp.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/anchorLinkFix.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/http404check.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/history/history.service.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/activity-indicator/activityindicator.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/progressbar/progressbar.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/alert/alert.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/sparkline/sparkline.component.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/siteselector/siteselector-model.service.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/siteselector/siteselector.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/siteselector/siteselector.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/menudropdown/menudropdown.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/enrichedheadline/enrichedheadline.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/content-intro/content-intro.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/content-block/content-block.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/notification/notification.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/notification/notification.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/notification/notification.service.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/ajax-form/ajax-form.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/ajax-form/ajax-form.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/widget-loader/widgetloader.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/widget-bydimension-container/widget-bydimension-container.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/widget-container/widgetcontainer.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/widget/widget.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/popover-handler/popover-handler.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/report-export/reportexport.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-page/reportingpage.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-page/reportingpage-model.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-page/reportingpage.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-menu/reportingmenu.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-menu/reportingmenu-model.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/reporting-menu/reportingmenu.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/quick-access/quick-access.controller.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/quick-access/quick-access.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/selector/selector.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/content-table/content-table.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/date-picker/date-picker.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/date-range-picker/date-range-picker.component.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/period-date-picker/period-date-picker.component.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/period-selector/period-selector.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/period-selector/period-selector.controller.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/multipairfield/multipairfield.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/multipairfield/multipairfield.controller.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/dropdown-menu/dropdown-menu.directive.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/field-array/field-array.directive.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/field-array/field-array.controller.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/comparisons/comparisons.service.js";
        $jsFiles[] = "plugins/CoreHome/angularjs/comparisons/comparisons.component.js";

        $jsFiles[] = "plugins/CoreHome/angularjs/iecheck.js";

        // we have to load these CoreAdminHome files here. If we loaded them in CoreAdminHome,
        // there would be JS errors as CoreAdminHome is loaded first. Meaning it is loaded before
        // any angular JS file is loaded etc.
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/smtp/mail-smtp.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/branding/branding.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/trackingcode/jstrackingcode.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/trackingcode/imagetrackingcode.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/archiving/archiving.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/trackingfailures/trackingfailures.controller.js";
        $jsFiles[] = "plugins/CoreAdminHome/angularjs/trackingfailures/trackingfailures.directive.js";

        // we have to load these CorePluginsAdmin files here. If we loaded them in CorePluginsAdmin,
        // there would be JS errors as CorePluginsAdmin is loaded first. Meaning it is loaded before
        // any angular JS file is loaded etc.
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/plugin-settings/plugin-settings.controller.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/plugin-settings/plugin-settings.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/form/form.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/form-field/form-field.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/field/field.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/save-button/save-button.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/plugins/plugin-filter.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/plugins/plugin-management.directive.js";
        $jsFiles[] = "plugins/CorePluginsAdmin/angularjs/plugins/plugin-upload.directive.js";
        $jsFiles[] = "node_modules/iframe-resizer/js/iframeResizer.min.js";
        $jsFiles[] = "node_modules/iframe-resizer/js/iframeResizer.contentWindow.min.js";
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'General_Export';
        $translationKeys[] = 'General_InvalidDateRange';
        $translationKeys[] = 'General_Loading';
        $translationKeys[] = 'General_Show';
        $translationKeys[] = 'General_Remove';
        $translationKeys[] = 'General_Hide';
        $translationKeys[] = 'General_Save';
        $translationKeys[] = 'General_Website';
        $translationKeys[] = 'General_Pagination';
        $translationKeys[] = 'General_RowsToDisplay';
        $translationKeys[] = 'Intl_Year_Short';
        $translationKeys[] = 'General_MultiSitesSummary';
        $translationKeys[] = 'General_SearchNoResults';
        $translationKeys[] = 'CoreHome_ChooseX';
        $translationKeys[] = 'CoreHome_ClickToSeeFullInformation';
        $translationKeys[] = 'CoreHome_YouAreUsingTheLatestVersion';
        $translationKeys[] = 'CoreHome_IncludeRowsWithLowPopulation';
        $translationKeys[] = 'CoreHome_ExcludeRowsWithLowPopulation';
        $translationKeys[] = 'CoreHome_DataTableIncludeAggregateRows';
        $translationKeys[] = 'CoreHome_DataTableExcludeAggregateRows';
        $translationKeys[] = 'CoreHome_DataTableCombineDimensions';
        $translationKeys[] = 'CoreHome_DataTableShowDimensions';
        $translationKeys[] = 'CoreHome_Default';
        $translationKeys[] = 'CoreHome_FormatMetrics';
        $translationKeys[] = 'CoreHome_ShowExportUrl';
        $translationKeys[] = 'CoreHome_HideExportUrl';
        $translationKeys[] = 'CoreHome_FlattenDataTable';
        $translationKeys[] = 'CoreHome_UnFlattenDataTable';
        $translationKeys[] = 'CoreHome_ExternalHelp';
        $translationKeys[] = 'CoreHome_ClickToEditX';
        $translationKeys[] = 'CoreHome_Menu';
        $translationKeys[] = 'CoreHome_AddTotalsRowDataTable';
        $translationKeys[] = 'CoreHome_RemoveTotalsRowDataTable';
        $translationKeys[] = 'CoreHome_PeriodHasOnlyRawData';
        $translationKeys[] = 'SitesManager_NotFound';
        $translationKeys[] = 'Annotations_ViewAndAddAnnotations';
        $translationKeys[] = 'General_RowEvolutionRowActionTooltipTitle';
        $translationKeys[] = 'General_RowEvolutionRowActionTooltip';
        $translationKeys[] = 'Annotations_IconDesc';
        $translationKeys[] = 'Annotations_IconDescHideNotes';
        $translationKeys[] = 'Annotations_HideAnnotationsFor';
        $translationKeys[] = 'General_LoadingPopover';
        $translationKeys[] = 'General_LoadingPopoverFor';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_1';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_2';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_3';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_4';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_5';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_6';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_7';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_8';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_9';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_10';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_11';
        $translationKeys[] = 'Intl_Month_Short_StandAlone_12';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_1';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_2';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_3';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_4';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_5';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_6';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_7';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_8';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_9';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_10';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_11';
        $translationKeys[] = 'Intl_Month_Long_StandAlone_12';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_1';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_2';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_3';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_4';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_5';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_6';
        $translationKeys[] = 'Intl_Day_Short_StandAlone_7';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_1';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_2';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_3';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_4';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_5';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_6';
        $translationKeys[] = 'Intl_Day_Long_StandAlone_7';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_1';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_2';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_3';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_4';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_5';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_6';
        $translationKeys[] = 'Intl_Day_Min_StandAlone_7';
        $translationKeys[] = 'Intl_PeriodDay';
        $translationKeys[] = 'Intl_PeriodWeek';
        $translationKeys[] = 'Intl_PeriodMonth';
        $translationKeys[] = 'Intl_PeriodYear';
        $translationKeys[] = 'General_DateRangeInPeriodList';
        $translationKeys[] = 'General_And';
        $translationKeys[] = 'General_All';
        $translationKeys[] = 'General_Search';
        $translationKeys[] = 'General_Clear';
        $translationKeys[] = 'General_MoreDetails';
        $translationKeys[] = 'General_Help';
        $translationKeys[] = 'General_MoreDetails';
        $translationKeys[] = 'General_Help';
        $translationKeys[] = 'General_Id';
        $translationKeys[] = 'General_Name';
        $translationKeys[] = 'General_JsTrackingTag';
        $translationKeys[] = 'General_Yes';
        $translationKeys[] = 'General_No';
        $translationKeys[] = 'General_Edit';
        $translationKeys[] = 'General_Delete';
        $translationKeys[] = 'General_Default';
        $translationKeys[] = 'General_LoadingData';
        $translationKeys[] = 'General_Error';
        $translationKeys[] = 'General_ErrorRequest';
        $translationKeys[] = 'General_ErrorRequestFaqLink';
        $translationKeys[] = 'General_Warning';
        $translationKeys[] = 'General_YourChangesHaveBeenSaved';
        $translationKeys[] = 'General_LearnMore';
        $translationKeys[] = 'General_ChooseDate';
        $translationKeys[] = 'General_ReadThisToLearnMore';
        $translationKeys[] = 'CoreHome_UndoPivotBySubtable';
        $translationKeys[] = 'CoreHome_PivotBySubtable';
        $translationKeys[] = 'General_LearnMore';
        $translationKeys[] = 'CoreHome_NoSuchPage';
        $translationKeys[] = 'CoreHome_QuickAccessTitle';
        $translationKeys[] = 'CoreHome_Segments';
        $translationKeys[] = 'CoreHome_MenuEntries';
        $translationKeys[] = 'SitesManager_Sites';
        $translationKeys[] = 'CoreHome_MainNavigation';
        $translationKeys[] = 'CoreHome_ChangeCurrentWebsite';
        $translationKeys[] = 'General_CreatedByUser';
        $translationKeys[] = 'General_DateRangeFromTo';
        $translationKeys[] = 'General_DateRangeFrom';
        $translationKeys[] = 'General_DateRangeTo';
        $translationKeys[] = 'General_DoubleClickToChangePeriod';
        $translationKeys[] = 'General_Apply';
        $translationKeys[] = 'General_Period';
        $translationKeys[] = 'General_CompareTo';
        $translationKeys[] = 'CoreHome_DateInvalid';
        $translationKeys[] = 'CoreHome_EnterZenMode';
        $translationKeys[] = 'CoreHome_ExitZenMode';
        $translationKeys[] = 'CoreHome_ShortcutZenMode';
        $translationKeys[] = 'CoreHome_ShortcutSegmentSelector';
        $translationKeys[] = 'CoreHome_ShortcutWebsiteSelector';
        $translationKeys[] = 'CoreHome_ShortcutCalendar';
        $translationKeys[] = 'CoreHome_ShortcutSearch';
        $translationKeys[] = 'CoreHome_ShortcutHelp';
        $translationKeys[] = 'CoreHome_StandardReport';
        $translationKeys[] = 'CoreHome_ReportWithMetadata';
        $translationKeys[] = 'CoreHome_ReportType';
        $translationKeys[] = 'CoreHome_RowLimit';
        $translationKeys[] = 'CoreHome_ExportFormat';
        $translationKeys[] = 'CoreHome_ExportTooltip';
        $translationKeys[] = 'CoreHome_ExportTooltipWithLink';
        $translationKeys[] = 'CoreHome_FlattenReport';
        $translationKeys[] = 'CoreHome_CustomLimit';
        $translationKeys[] = 'CoreHome_ExpandSubtables';
        $translationKeys[] = 'CoreHome_HomeShortcut';
        $translationKeys[] = 'CoreHome_PageUpShortcutDescription';
        $translationKeys[] = 'CoreHome_EndShortcut';
        $translationKeys[] = 'CoreHome_PageDownShortcutDescription';
        $translationKeys[] = 'CoreHome_MacPageUp';
        $translationKeys[] = 'CoreHome_MacPageDown';
        $translationKeys[] = 'CoreHome_SearchOnMatomo';
        $translationKeys[] = 'General_ComputedMetricMax';
        $translationKeys[] = 'General_XComparedToY';
        $translationKeys[] = 'General_ComparisonCardTooltip1';
        $translationKeys[] = 'General_ComparisonCardTooltip2';
        $translationKeys[] = 'General_Comparisons';
        $translationKeys[] = 'General_ClickToRemoveComp';
        $translationKeys[] = 'General_Custom';
        $translationKeys[] = 'General_PreviousPeriod';
        $translationKeys[] = 'General_PreviousYear';
        $translationKeys[] = 'CoreHome_ReportingCategoryHelpPrefix';
        $translationKeys[] = 'CoreHome_TechDeprecationWarning';
    }
}
