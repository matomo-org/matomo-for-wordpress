<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CoreHome\Columns;

use Piwik\Columns\DimensionMetricFactory;
use Piwik\Columns\MetricsList;
use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\Dimension\VisitDimension;
use Piwik\Plugins\Live\Live;
use Piwik\Segment\SegmentsList;
use Piwik\Columns\DimensionSegmentFactory;
/**
 * Dimension for the log_visit.idvisitor column. This column is added in the CREATE TABLE
 * statement, so this dimension exists only to configure a segment.
 */
class VisitorId extends VisitDimension
{
    protected $columnName = 'idvisitor';
    protected $metricId = 'visitors';
    protected $nameSingular = 'General_VisitorID';
    protected $namePlural = 'General_Visitors';
    protected $segmentName = 'visitorId';
    protected $acceptValues = '34c31e04394bdc63 - any 16 Hexadecimal chars ID, which can be fetched using the Tracking API function getVisitorId()';
    protected $allowAnonymous = false;
    protected $sqlFilterValue = ['Piwik\\Common', 'convertVisitorIdToBin'];
    protected $type = self::TYPE_BINARY;
    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_UNIQUE);
        $metric->setTranslatedName(Piwik::translate('General_ColumnNbUniqVisitors'));
        $metric->setName('nb_uniq_visitors');
        $metricsList->addMetric($metric);
    }
    public function configureSegments(SegmentsList $segmentsList, DimensionSegmentFactory $dimensionSegmentFactory)
    {
        try {
            $visitorProfileEnabled = Live::isVisitorProfileEnabled();
        } catch (\Zend_Db_Exception $e) {
            // when running tests the db might not yet be set up when fetching available segments
            if (!defined('PIWIK_TEST_MODE') || !PIWIK_TEST_MODE) {
                throw $e;
            }
            $visitorProfileEnabled = true;
        }
        if ($visitorProfileEnabled) {
            parent::configureSegments($segmentsList, $dimensionSegmentFactory);
        }
    }
}
