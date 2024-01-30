<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\VisitorInterest;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Metrics;
use Piwik\Piwik;
/**
 * VisitorInterest API lets you access two Visitor Engagement reports: number of visits per number of pages,
 * and number of visits per visit duration.
 *
 * @method static \Piwik\Plugins\VisitorInterest\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    protected function getDataTable($name, $idSite, $period, $date, $segment, $column = Metrics::INDEX_NB_VISITS)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $archive = Archive::build($idSite, $period, $date, $segment);
        $dataTable = $archive->getDataTable($name);
        $dataTable->queueFilter('ReplaceColumnNames');
        return $dataTable;
    }
    public function getNumberOfVisitsPerVisitDuration($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable(\Piwik\Plugins\VisitorInterest\Archiver::TIME_SPENT_RECORD_NAME, $idSite, $period, $date, $segment);
        $dataTable->queueFilter('Sort', array('label', 'asc', true, false));
        $dataTable->queueFilter('AddSegmentByRangeLabel', array('visitDuration'));
        $dataTable->queueFilter('BeautifyTimeRangeLabels', array(Piwik::translate('VisitorInterest_BetweenXYSeconds'), Piwik::translate('Intl_OneMinuteShort'), Piwik::translate('Intl_NMinutesShort')));
        return $dataTable;
    }
    public function getNumberOfVisitsPerPage($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable(\Piwik\Plugins\VisitorInterest\Archiver::PAGES_VIEWED_RECORD_NAME, $idSite, $period, $date, $segment);
        $dataTable->queueFilter('Sort', array('label', 'asc', true, false));
        $dataTable->queueFilter('AddSegmentByRangeLabel', array('actions'));
        $dataTable->queueFilter('BeautifyRangeLabels', array(Piwik::translate('VisitorInterest_OnePage'), Piwik::translate('VisitorInterest_NPages')));
        return $dataTable;
    }
    /**
     * Returns a DataTable that associates counts of days (N) with the count of visits that
     * occurred within N days of the last visit.
     *
     * @param int $idSite The site to select data from.
     * @param string $period The period type.
     * @param string $date The date type.
     * @param string|bool $segment The segment.
     * @return DataTable the archived report data.
     */
    public function getNumberOfVisitsByDaysSinceLast($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable(\Piwik\Plugins\VisitorInterest\Archiver::DAYS_SINCE_LAST_RECORD_NAME, $idSite, $period, $date, $segment, Metrics::INDEX_NB_VISITS);
        $dataTable->queueFilter('AddSegmentByRangeLabel', array('daysSinceLastVisit'));
        $dataTable->queueFilter('BeautifyRangeLabels', array(Piwik::translate('Intl_OneDay'), Piwik::translate('Intl_NDays')));
        return $dataTable;
    }
    /**
     * Returns a DataTable that associates ranges of visit numbers with the count of visits
     * whose visit number falls within those ranges.
     *
     * @param int $idSite The site to select data from.
     * @param string $period The period type.
     * @param string $date The date type.
     * @param string|bool $segment The segment.
     * @return DataTable the archived report data.
     */
    public function getNumberOfVisitsByVisitCount($idSite, $period, $date, $segment = false)
    {
        $dataTable = $this->getDataTable(\Piwik\Plugins\VisitorInterest\Archiver::VISITS_COUNT_RECORD_NAME, $idSite, $period, $date, $segment, Metrics::INDEX_NB_VISITS);
        $dataTable->queueFilter('AddSegmentByRangeLabel', array('visitCount'));
        $dataTable->queueFilter('BeautifyRangeLabels', array(Piwik::translate('General_OneVisit'), Piwik::translate('General_NVisits')));
        return $dataTable;
    }
}
