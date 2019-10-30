<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\SegmentEditor;

use Piwik\API\Request;
use Piwik\ArchiveProcessor\PluginsArchiver;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Cache;
use Piwik\CacheId;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\ArchiveSelector;
use Piwik\Notification;
use Piwik\Piwik;
use Piwik\Plugins\CoreHome\SystemSummary;
use Piwik\Plugins\Diagnostics\Diagnostics;
use Piwik\Segment;
use Piwik\SettingsPiwik;
use Piwik\SettingsServer;
use Piwik\Site;
use Piwik\Period;
use Piwik\Url;
use Piwik\View;

/**
 */
class SegmentEditor extends \Piwik\Plugin
{
    const NO_DATA_UNPROCESSED_SEGMENT_ID = 'nodata_segment_not_processed';

    /**
     * @see Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Segments.getKnownSegmentsToArchiveForSite'  => 'getKnownSegmentsToArchiveForSite',
            'Segments.getKnownSegmentsToArchiveAllSites' => 'getKnownSegmentsToArchiveAllSites',
            'AssetManager.getJavaScriptFiles'            => 'getJsFiles',
            'AssetManager.getStylesheetFiles'            => 'getStylesheetFiles',
            'Template.nextToCalendar'                    => 'getSegmentEditorHtml',
            'System.addSystemSummaryItems'               => 'addSystemSummaryItems',
            'Translate.getClientSideTranslationKeys'     => 'getClientSideTranslationKeys',
            'Visualization.onNoData' => 'onNoData',
            'Archive.noArchivedData' => 'onNoArchiveData',
        );
    }

    public function addSystemSummaryItems(&$systemSummary)
    {
        $storedSegments = StaticContainer::get('Piwik\Plugins\SegmentEditor\Services\StoredSegmentService');
        $segments = $storedSegments->getAllSegmentsAndIgnoreVisibility();
        $numSegments = count($segments);
        $systemSummary[] = new SystemSummary\Item($key = 'segments', Piwik::translate('CoreHome_SystemSummaryNSegments', $numSegments), $value = null, $url = null, $icon = 'icon-segment', $order = 6);
    }

    function getSegmentEditorHtml(&$out)
    {
        $selector = new SegmentSelectorControl();
        $out .= $selector->render();
    }

    public function getKnownSegmentsToArchiveAllSites(&$segments)
    {
        $this->getKnownSegmentsToArchiveForSite($segments, $idSite = false);
    }

    /**
     * Adds the pre-processed segments to the list of Segments.
     * Used by CronArchive, ArchiveProcessor\Rules, etc.
     *
     * @param $segments
     * @param $idSite
     */
    public function getKnownSegmentsToArchiveForSite(&$segments, $idSite)
    {
        $model = new Model();
        $segmentToAutoArchive = $model->getSegmentsToAutoArchive($idSite);

        foreach ($segmentToAutoArchive as $segmentInfo) {
            $segments[] = $segmentInfo['definition'];
        }

        $segments = array_unique($segments);
    }

    public function onNoArchiveData()
    {
        // when browser archiving is enabled, the archiving process can be triggered for an API request.
        // for non-day periods, this means the Archive class will be used for smaller periods to build the
        // non-day period (eg, requesting a week period can result in archiving of day periods). in this case
        // Archive can report there is no data for a day, triggering this event, but there may be data for other
        // days in the week. in this case, we don't want to throw an exception.
        if (PluginsArchiver::isArchivingProcessActive()) {
            return null;
        }

        // don't do check unless this is the root API request and it is an HTTP API request
        if (!Request::isCurrentApiRequestTheRootApiRequest()
            || !Request::isRootRequestApiRequest()
        ) {
            return null;
        }

        // don't do check during cron archiving
        if (SettingsServer::isArchivePhpTriggered()
            || Common::isPhpCliMode()
        ) {
            return null;
        }

        $segmentInfo = $this->getSegmentIfIsUnprocessed();
        if (empty($segmentInfo)) {
            return;
        }

        list($segment, $storedSegment, $isSegmentToPreprocess) = $segmentInfo;

        throw new UnprocessedSegmentException($segment, $isSegmentToPreprocess, $storedSegment);
    }

    public function onNoData(View $dataTableView)
    {
        // if the archiving hasn't run in a while notification is up, don't display this one
        if (isset($dataTableView->notifications[Diagnostics::NO_DATA_ARCHIVING_NOT_RUN_NOTIFICATION_ID])) {
            return;
        }

        $segmentInfo = $this->getSegmentIfIsUnprocessed();
        if (empty($segmentInfo)) {
            return;
        }

        list($segment, $storedSegment, $isSegmentToPreprocess) = $segmentInfo;

        if (!$isSegmentToPreprocess) {
            return; // do not display the notification for custom segments
        }

        $segmentDisplayName = !empty($storedSegment['name']) ? $storedSegment['name'] : $segment;

        $view = new View('@SegmentEditor/_unprocessedSegmentMessage.twig');
        $view->isSegmentToPreprocess = $isSegmentToPreprocess;
        $view->segmentName = $segmentDisplayName;
        $view->visitorLogLink = '#' . Url::getCurrentQueryStringWithParametersModified([
            'category' => 'General_Visitors',
            'subcategory' => 'Live_VisitorLog',
        ]);

        $notification = new Notification($view->render());
        $notification->priority = Notification::PRIORITY_HIGH;
        $notification->context = Notification::CONTEXT_INFO;
        $notification->flags = Notification::FLAG_NO_CLEAR;
        $notification->type = Notification::TYPE_TRANSIENT;
        $notification->raw = true;

        $dataTableView->notifications[self::NO_DATA_UNPROCESSED_SEGMENT_ID] = $notification;
    }

    private function getSegmentIfIsUnprocessed()
    {
        // get idSites
        $idSite = Common::getRequestVar('idSite', false);
        if (empty($idSite)
            || !is_numeric($idSite)
        ) {
            return null;
        }

        // get segment
        $segment = Request::getRawSegmentFromRequest();
        if (empty($segment)) {
            return null;
        }
        $segment = new Segment($segment, [$idSite]);

        // get period
        $date = Common::getRequestVar('date', false);
        $periodStr = Common::getRequestVar('period', false);
        $period = Period\Factory::build($periodStr, $date);

        // check if archiving is enabled. if so, the segment should have been processed.
        $isArchivingDisabled = Rules::isArchivingDisabledFor([$idSite], $segment, $period);
        if (!$isArchivingDisabled) {
            return null;
        }

        // check if segment archive does not exist
        $processorParams = new \Piwik\ArchiveProcessor\Parameters(new Site($idSite), $period, $segment);
        $archiveIdAndStats = ArchiveSelector::getArchiveIdAndVisits($processorParams, null);
        if (!empty($archiveIdAndStats[0])) {
            return null;
        }

        $idSites = Site::getIdSitesFromIdSitesString($idSite);

        if (strpos($date, ',') !== false) { // if getting multiple periods, check the whole range for visits
            $periodStr = 'range';
        }

        // if no visits recorded, data will not appear, so don't show the message
        $liveModel = new \Piwik\Plugins\Live\Model();
        $visits = $liveModel->queryLogVisits($idSites, $periodStr, $date, $segment->getString(), $offset = 0, $limit = 1, null, null, 'ASC');
        if (empty($visits)) {
            return null;
        }

        // check if requested segment is segment to preprocess
        $isSegmentToPreprocess = Rules::isSegmentPreProcessed([$idSite], $segment);

        // this archive has no data, the report is for a segment that gets preprocessed, and the archive for this
        // data does not exist. this means the data will be processed later. we let the user know so they will not
        // be confused.
        $model = new Model();
        $storedSegment = $model->getSegmentByDefinition($segment->getString());
        if (empty($storedSegment)) {
            $storedSegment = $model->getSegmentByDefinition(urldecode($segment->getString()));
        }
        if (empty($storedSegment)) {
            $storedSegment = null;
        }

        return [$segment, $storedSegment, $isSegmentToPreprocess];
    }

    public function install()
    {
        Model::install();
    }

    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = "plugins/SegmentEditor/javascripts/Segmentation.js";
        $jsFiles[] = "plugins/SegmentEditor/angularjs/segment-generator/segmentgenerator-model.js";
        $jsFiles[] = "plugins/SegmentEditor/angularjs/segment-generator/segmentgenerator.controller.js";
        $jsFiles[] = "plugins/SegmentEditor/angularjs/segment-generator/segmentgenerator.directive.js";
    }

    public function getStylesheetFiles(&$stylesheets)
    {
        $stylesheets[] = "plugins/SegmentEditor/stylesheets/segmentation.less";
        $stylesheets[] = "plugins/SegmentEditor/angularjs/segment-generator/segmentgenerator.directive.less";
    }

    /**
     * Returns whether adding segments for all websites is enabled or not.
     *
     * @return bool
     */
    public static function isAddingSegmentsForAllWebsitesEnabled()
    {
        return Config::getInstance()->General['allow_adding_segments_for_all_websites'] == 1;
    }

    public function getClientSideTranslationKeys(&$translationKeys)
    {
        $translationKeys[] = 'SegmentEditor_CustomSegment';
        $translationKeys[] = 'SegmentEditor_VisibleToSuperUser';
        $translationKeys[] = 'SegmentEditor_SharedWithYou';
        $translationKeys[] = 'SegmentEditor_ChooseASegment';
        $translationKeys[] = 'SegmentEditor_CurrentlySelectedSegment';
        $translationKeys[] = 'SegmentEditor_OperatorAND';
        $translationKeys[] = 'SegmentEditor_OperatorOR';
        $translationKeys[] = 'SegmentEditor_AddANDorORCondition';
        $translationKeys[] = 'SegmentEditor_DefaultAllVisits';
        $translationKeys[] = 'General_OperationEquals';
        $translationKeys[] = 'General_OperationNotEquals';
        $translationKeys[] = 'General_OperationAtMost';
        $translationKeys[] = 'General_OperationAtLeast';
        $translationKeys[] = 'General_OperationLessThan';
        $translationKeys[] = 'General_OperationGreaterThan';
        $translationKeys[] = 'General_OperationIs';
        $translationKeys[] = 'General_OperationIsNot';
        $translationKeys[] = 'General_OperationContains';
        $translationKeys[] = 'General_OperationDoesNotContain';
        $translationKeys[] = 'General_OperationStartsWith';
        $translationKeys[] = 'General_OperationEndsWith';
        $translationKeys[] = 'General_Unknown';
        $translationKeys[] = 'SegmentEditor_ThisSegmentIsCompared';
        $translationKeys[] = 'SegmentEditor_ThisSegmentIsSelectedAndCannotBeCompared';
        $translationKeys[] = 'SegmentEditor_CompareThisSegment';
        $translationKeys[] = 'Live_VisitsLog';
    }

    public static function getAllSegmentsForSite($idSite)
    {
        $cache = Cache::getTransientCache();
        $cacheKey = CacheId::siteAware('SegmentEditor_getAll', [$idSite]);

        $segments = $cache->fetch($cacheKey);
        if (!is_array($segments)) {
            $segments = Request::processRequest('SegmentEditor.getAll', ['idSite' => $idSite], $default = []);
            usort($segments, function ($lhs, $rhs) {
                return strcmp($lhs['name'], $rhs['name']);
            });
            $cache->save($cacheKey, $segments);
        }
        return $segments;
    }
}
