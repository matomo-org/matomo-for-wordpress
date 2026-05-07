<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\PagePerformance;

use Piwik\Archive;
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugin\ProcessedMetric;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AveragePageLoadTime;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomCompletion;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeDomProcessing;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeNetwork;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeServer;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeOnLoad;
use Piwik\Plugins\PagePerformance\Columns\Metrics\AverageTimeTransfer;
/**
 * Provides reporting API methods for aggregated page performance metrics.
 *
 * @method static \Piwik\Plugins\PagePerformance\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Returns aggregated page performance metrics for the requested site and period.
     *
     * @param int|string|int[] $idSite Website ID(s) to query.
     *                                 - Single site ID (e.g. 1)
     *                                 - Multiple site IDs (e.g. [1, 4, 5])
     *                                 - Comma-separated list ("1,4,5") or "all"
     * @param 'day'|'week'|'month'|'year'|'range' $period The period to process, processes data for the period
     *                                                   containing the specified date.
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param string|null|false $segment Custom segment to filter the report.
     *                                   Example: "referrerName==example.com"
     *                                   Supports AND (;) and OR (,) operators.
     * @return DataTable|DataTable\Map Metrics for network, server, transfer, DOM, and page load timings.
     */
    public function get($idSite, string $period, string $date, $segment = \false)
    {
        Piwik::checkUserHasViewAccess($idSite);
        $archive = Archive::build($idSite, $period, $date, $segment);
        $columns = [\Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_SERVER_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_HITS, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_HITS];
        $dataTable = $archive->getDataTableFromNumeric($columns);
        $precision = 2;
        $dataTable->filter('ColumnCallbackReplace', [[\Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME], function ($value) {
            return $value / 1000;
        }]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeNetwork::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_NETWORK_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeServer::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_SERVER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_SERVER_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeTransfer::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_TRANSFER_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeDomProcessing::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMPROCESSING_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeDomCompletion::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_DOMCOMPLETION_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AverageTimeOnLoad::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_ONLOAD_HITS, $precision]);
        $dataTable->filter('ColumnCallbackAddColumnQuotient', [$this->getMetricColumn(AveragePageLoadTime::class), \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_TIME, \Piwik\Plugins\PagePerformance\Archiver::PAGEPERFORMANCE_TOTAL_PAGE_LOAD_HITS, $precision]);
        $dataTable->queueFilter('ColumnDelete', [$columns]);
        return $dataTable;
    }
    /**
     * @param class-string<ProcessedMetric> $class
     */
    private function getMetricColumn(string $class) : string
    {
        /** @var ProcessedMetric $metric */
        $metric = new $class();
        return $metric->getName();
    }
}
