<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Live;

use Exception;
use Piwik\API\Request;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Site;
use Piwik\Log\LoggerInterface;

/**
 * @see plugins/Live/Visitor.php
 */
require_once PIWIK_INCLUDE_PATH . '/plugins/Live/Visitor.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/UserCountry/functions.php';

/**
 * The Live! API lets you access complete visit level information about your visitors. Combined with the power of <a href='http://matomo.org/docs/analytics-api/segmentation/' target='_blank'>Segmentation</a>,
 * you will be able to request visits filtered by any criteria.
 *
 * The method "getLastVisitsDetails" will return extensive <a href='https://matomo.org/guide/apis/raw-data/'>RAW data</a> for each visit, which includes: server time, visitId, visitorId,
 * visitorType (new or returning), number of pages, list of all pages (and events, file downloaded and outlinks clicked),
 * custom variables names and values set to this visit, number of goal conversions (and list of all Goal conversions for this visit,
 * with time of conversion, revenue, URL, etc.), but also other attributes such as: days since last visit, days since first visit,
 * country, continent, visitor IP,
 * provider, referrer used (referrer name, keyword if it was a search engine, full URL), campaign name and keyword, operating system,
 * browser, type of screen, resolution, supported browser plugins (flash, java, silverlight, pdf, etc.), various dates & times format to make
 * it easier for API users... and more!
 *
 * With the parameter <a href='http://matomo.org/docs/analytics-api/segmentation/' rel='noreferrer' target='_blank'>'&segment='</a> you can filter the
 * returned visits by any criteria (visitor IP, visitor ID, country, keyword used, time of day, etc.).
 *
 * The method "getCounters" is used to return a simple counter: visits, number of actions, number of converted visits, in the last N minutes.
 *
 * See also the documentation about <a href='http://matomo.org/docs/real-time/' rel='noreferrer' target='_blank'>Real time widget and visitor level reports</a> in Matomo.
 * You may also be interested in steps to <a href='https://matomo.org/faq/how-to/faq_24536/'>export your RAW data to a data warehouse</a>.
 * @method static \Piwik\Plugins\Live\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * This will return simple counters, for a given website ID, for visits over the last N minutes
     *
     * @param int $idSite Id Site
     * @param int $lastMinutes Number of minutes to look back at (between 1 and 2880)
     * @param bool|string $segment
     * @param array $showColumns The columns to show / not to request. Eg 'visits', 'actions', ...
     * @param array $hideColumns The columns to hide / not to request. Eg 'visits', 'actions', ...
     * @return array( visits => N, actions => M, visitsConverted => P )
     */
    public function getCounters($idSite, int $lastMinutes, $segment = false, $showColumns = array(), $hideColumns = array())
    {
        if ($lastMinutes < 1 || $lastMinutes > 2880) {
            throw new \InvalidArgumentException('lastMinutes only accepts values between 1 and 2880');
        }

        Piwik::checkUserHasViewAccess($idSite);
        $model = new Model();

        if (is_string($showColumns)) {
            $showColumns = explode(',', $showColumns);
        }

        if (is_string($hideColumns)) {
            $hideColumns = explode(',', $hideColumns);
        }

        $counters = array();

        $hasVisits = true;
        if ($this->shouldColumnBePresentInResponse('visits', $showColumns, $hideColumns)) {
            $counters['visits'] = $model->getNumVisits($idSite, $lastMinutes, $segment);
            $hasVisits = !empty($counters['visits']);
        }

        if ($this->shouldColumnBePresentInResponse('actions', $showColumns, $hideColumns)) {
            if ($hasVisits) {
                $counters['actions'] = $model->getNumActions($idSite, $lastMinutes, $segment);
            } else {
                $counters['actions'] = 0;
            }
        }

        if ($this->shouldColumnBePresentInResponse('visitors', $showColumns, $hideColumns)) {
            if ($hasVisits) {
                $counters['visitors'] = $model->getNumVisitors($idSite, $lastMinutes, $segment);
            } else {
                $counters['visitors'] = 0;
            }
        }

        if ($this->shouldColumnBePresentInResponse('visitsConverted', $showColumns, $hideColumns)) {
            if ($hasVisits) {
                $counters['visitsConverted'] = $model->getNumVisitsConverted($idSite, $lastMinutes, $segment);
            } else {
                $counters['visitsConverted'] = 0;
            }
        }

        return array($counters);
    }

    private function shouldColumnBePresentInResponse($column, $showColumns, $hideColumns)
    {
        $show = (empty($showColumns) || in_array($column, $showColumns));
        $hide = in_array($column, $hideColumns);

        return $show && !$hide;
    }

    /**
     * Returns the last visits tracked in the specified website
     * You can define any number of filters: none, one, many or all parameters can be defined
     *
     * @param int $idSite Site ID
     * @param bool|string $period Period to restrict to when looking at the logs
     * @param bool|string $date Date to restrict to
     * @param bool|int $segment (optional) Number of visits rows to return
     * @param bool|int $countVisitorsToFetch DEPRECATED (optional) Only return the last X visits. Please use the API paramaeter 'filter_offset' and 'filter_limit' instead.
     * @param bool|int $minTimestamp (optional) Minimum timestamp to restrict the query to (useful when paginating or refreshing visits)
     * @param bool $flat
     * @param bool $doNotFetchActions
     * @param bool $enhanced for plugins that want to expose additional information
     * @return DataTable
     */
    public function getLastVisitsDetails($idSite, $period = false, $date = false, $segment = false, $countVisitorsToFetch = false, $minTimestamp = false, $flat = false, $doNotFetchActions = false, $enhanced = false)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $idSites = Site::getIdSitesFromIdSitesString($idSite);
        if (is_array($idSites) && count($idSites) === 1) {
            $idSites = array_shift($idSites);
        }
        Piwik::checkUserHasViewAccess($idSites);

        if (is_numeric($minTimestamp)) {
            $minTimestamp = (int) $minTimestamp;
        } else {
            $minTimestamp = false;
        }

        if (Request::isCurrentApiRequestTheRootApiRequest() || !in_array(Request::getRootApiRequestMethod(), ['API.getSuggestedValuesForSegment', 'PrivacyManager.findDataSubjects'])) {
            if (is_array($idSites)) {
                $filteredSites = array_filter($idSites, function($idSite) {
                    return Live::isVisitorLogEnabled($idSite);
                });
                if (empty($filteredSites)) {
                    throw new Exception('Visits log is deactivated for all given websites (idSite='.$idSite.').');
                }
            } else {
                Live::checkIsVisitorLogEnabled($idSites);
            }
        }

        if ($countVisitorsToFetch !== false) {
            $filterLimit     = (int) $countVisitorsToFetch;
            $filterOffset    = 0;
        } else {
            $filterLimit     = Common::getRequestVar('filter_limit', 10, 'int');
            $filterOffset    = Common::getRequestVar('filter_offset', 0, 'int');
        }

        $filterSortOrder = Common::getRequestVar('filter_sort_order', false, 'string');

        $dataTable = $this->loadLastVisitsDetailsFromDatabase($idSites, $period, $date, $segment, $filterOffset, $filterLimit, $minTimestamp, $filterSortOrder, $visitorId = false);
        $this->addFilterToCleanVisitors($dataTable, $idSites, $flat, $doNotFetchActions);

        $filterSortColumn = Common::getRequestVar('filter_sort_column', false, 'string');

        if ($filterSortColumn) {
            $this->logger->warning('Sorting the API method "Live.getLastVisitDetails" by column is currently not supported. To avoid this warning remove the URL parameter "filter_sort_column" from your API request.');
        }

        // Usually one would Sort a DataTable and then apply a Limit. In this case we apply a Limit first in SQL
        // for fast offset usage see https://github.com/piwik/piwik/issues/7458. Sorting afterwards would lead to a
        // wrong sorting result as it would only sort the limited results. Therefore we do not support a Sort for this
        // API
        $dataTable->disableFilter('Sort');
        $dataTable->disableFilter('Limit'); // limit is already applied here

        return $dataTable;
    }

    /**
     * Returns an array describing a visitor using their last visits (uses a maximum of 100).
     *
     * @param int $idSite Site ID
     * @param bool|false|string $visitorId The ID of the visitor whose profile to retrieve.
     * @param bool|false|string $segment
     * @param bool|false|int $limitVisits
     * @return array
     */
    public function getVisitorProfile($idSite, $visitorId = false, $segment = false, $limitVisits = false)
    {
        Piwik::checkUserHasViewAccess($idSite);
        Live::checkIsVisitorProfileEnabled($idSite);

        if (!is_numeric($limitVisits) || $limitVisits <= 0) {
            $limitVisits = VisitorProfile::VISITOR_PROFILE_MAX_VISITS_TO_SHOW;
        } else {
            $limitVisits = (int) $limitVisits;
        }

        if ($visitorId === false) {
            $visitorId = $this->getMostRecentVisitorId($idSite, $segment);
        }

        $limit = Config::getInstance()->General['live_visitor_profile_max_visits_to_aggregate'];

        $visits = $this->loadLastVisitsDetailsFromDatabase($idSite, $period = false, $date = false, $segment,
            $offset = 0, $limit, false, false, $visitorId);
        $this->addFilterToCleanVisitors($visits, $idSite, $flat = false, $doNotFetchActions = false, $filterNow = true);

        if ($visits->getRowsCount() == 0) {
            return array();
        }

        $profile = new VisitorProfile($idSite);
        $result = $profile->makeVisitorProfile($visits, $visitorId, $segment, $limitVisits);

        return $result;
    }

    /**
     * Returns the visitor ID of the most recent visit.
     *
     * @param int $idSite
     * @param bool|string $segment
     * @return string
     */
    public function getMostRecentVisitorId($idSite, $segment = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        // for faster performance search for a visitor within the last 7 days first
        $minTimestamp = Date::now()->subDay(7)->getTimestamp();

        $dataTable = $this->loadLastVisitsDetailsFromDatabase(
            $idSite, $period = false, $date = false, $segment, $offset = 0, $limit = 1, $minTimestamp
        );

        if (0 >= $dataTable->getRowsCount()) {
            $minTimestamp = Date::now()->subYear(1)->getTimestamp();
            // no visitor found in last 7 days, look further back for up to 1 year. This query will be slower
            $dataTable = $this->loadLastVisitsDetailsFromDatabase(
                $idSite, $period = false, $date = false, $segment, $offset = 0, $limit = 1, $minTimestamp
            );
        }

        if (0 >= $dataTable->getRowsCount()) {
            // no visitor found in last year, look over all logs. This query might be quite slow
            $dataTable = $this->loadLastVisitsDetailsFromDatabase(
                $idSite, $period = false, $date = false, $segment, $offset = 0, $limit = 1
            );
        }

        if (0 >= $dataTable->getRowsCount()) {
            return false;
        }

        $visitorFactory = new VisitorFactory();
        $visitDetails   = $dataTable->getFirstRow()->getColumns();
        $visitor        = $visitorFactory->create($visitDetails);

        return $visitor->getVisitorId();
    }

    /**
     * Returns the very first visit for the given visitorId
     *
     * @internal
     *
     * @param $idSite
     * @param $visitorId
     *
     * @return DataTable
     */
    public function getFirstVisitForVisitorId($idSite, $visitorId)
    {
        Piwik::checkUserHasSomeViewAccess();
        Live::checkIsVisitorProfileEnabled($idSite);

        if (empty($visitorId)) {
            return new DataTable();
        }

        $model = new Model();
        $data = $model->queryLogVisits($idSite, false, false, false, 0, 1, $visitorId, false, 'ASC');
        $dataTable = $this->makeVisitorTableFromArray($data);
        $this->addFilterToCleanVisitors($dataTable, $idSite, false, true);

        return $dataTable;
    }

    /**
     * For an array of visits, query the list of pages for this visit
     * as well as make the data human readable
     * @param DataTable $dataTable
     * @param int $idSite
     * @param bool $flat whether to flatten the array (eg. 'customVariables' names/values will appear in the root array rather than in 'customVariables' key
     * @param bool $doNotFetchActions If set to true, we only fetch visit info and not actions (much faster)
     * @param bool $filterNow If true, the visitors will be cleaned immediately
     */
    private function addFilterToCleanVisitors(DataTable $dataTable, $idSite, $flat = false, $doNotFetchActions = false, $filterNow = false)
    {
        $filter = 'queueFilter';
        if ($filterNow) {
            $filter = 'filter';
        }

        $dataTable->$filter(function ($table) use ($idSite, $flat, $doNotFetchActions) {
            /** @var DataTable $table */
            $visitorFactory = new VisitorFactory();

            // live api is not summable, prevents errors like "Unexpected ECommerce status value"
            $table->deleteRow(DataTable::ID_SUMMARY_ROW);

            $actionsByVisitId = array();

            if (!$doNotFetchActions) {
                $visitIds = $table->getColumn('idvisit');

                $visitorDetailsManipulators = Visitor::getAllVisitorDetailsInstances();

                foreach ($visitorDetailsManipulators as $instance) {
                    $instance->provideActionsForVisitIds($actionsByVisitId, $visitIds);
                }
            }

            foreach ($table->getRows() as $visitorDetailRow) {
                $visitorDetailsArray = Visitor::cleanVisitorDetails($visitorDetailRow->getColumns());

                $visitor = $visitorFactory->create($visitorDetailsArray);
                $visitorDetailsArray = $visitor->getAllVisitorDetails();

                $visitorDetailsArray['actionDetails'] = array();
                if (!$doNotFetchActions) {
                    $bulkFetchedActions  = isset($actionsByVisitId[$visitorDetailsArray['idVisit']]) ? $actionsByVisitId[$visitorDetailsArray['idVisit']] : array();
                    $visitorDetailsArray = Visitor::enrichVisitorArrayWithActions($visitorDetailsArray, $bulkFetchedActions);
                }

                if ($flat) {
                    $visitorDetailsArray = Visitor::flattenVisitorDetailsArray($visitorDetailsArray);
                }

                $visitorDetailRow->setColumns($visitorDetailsArray);
            }
        });
    }

    private function loadLastVisitsDetailsFromDatabase($idSite, $period, $date, $segment = false, $offset = 0, $limit = 100, $minTimestamp = false, $filterSortOrder = false, $visitorId = false)
    {
        $model = new Model();
        [$data, $hasMoreVisits] = $model->queryLogVisits($idSite, $period, $date, $segment, $offset, $limit, $visitorId, $minTimestamp, $filterSortOrder, true);
        return $this->makeVisitorTableFromArray($data, $hasMoreVisits);
    }

    /**
     * @param $data
     * @param $hasMoreVisits
     * @return DataTable
     * @throws Exception
     */
    private function makeVisitorTableFromArray($data, $hasMoreVisits=null)
    {
        $dataTable = new DataTable();

        $dataTable->addRowsFromSimpleArray($data);

        if (!empty($data[0])) {
            $columnsToNotAggregate = array_map(function () {
                return 'skip';
            }, $data[0]);

            $dataTable->setMetadata(DataTable::COLUMN_AGGREGATION_OPS_METADATA_NAME, $columnsToNotAggregate);
        }

        if (null !== $hasMoreVisits) {
            $dataTable->setMetadata('hasMoreVisits', $hasMoreVisits);
        }

        return $dataTable;
    }
}
