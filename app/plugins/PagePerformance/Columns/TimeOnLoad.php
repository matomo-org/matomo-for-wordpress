<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\PagePerformance\Columns;

use Piwik\Columns\DimensionMetricFactory;
use Piwik\Columns\MetricsList;
use Piwik\Piwik;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugin\ComputedMetric;
class TimeOnLoad extends \Piwik\Plugins\PagePerformance\Columns\Base
{
    const COLUMN_TYPE = 'MEDIUMINT(10) UNSIGNED NULL';
    const COLUMN_NAME = 'time_on_load';
    protected $columnName = self::COLUMN_NAME;
    protected $columnType = self::COLUMN_TYPE;
    protected $nameSingular = 'PagePerformance_ColumnTimeOnLoad';
    public function getRequestParam()
    {
        return 'pf_onl';
    }
    public function configureMetrics(MetricsList $metricsList, DimensionMetricFactory $dimensionMetricFactory)
    {
        $metric1 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_SUM);
        $metric1->setName('sum_time_on_load');
        $metricsList->addMetric($metric1);
        $metric2 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MAX);
        $metric2->setName('max_time_on_load');
        $metricsList->addMetric($metric2);
        $metric3 = $dimensionMetricFactory->createMetric('sum(if(%s is null, 0, 1))');
        $metric3->setName('pageviews_with_time_on_load');
        $metric3->setType(self::TYPE_NUMBER);
        $metric3->setTranslatedName(Piwik::translate('PagePerformance_ColumnViewsWithTimeOnLoad'));
        $metricsList->addMetric($metric3);
        $metric4 = $dimensionMetricFactory->createMetric(ArchivedMetric::AGGREGATION_MIN);
        $metric4->setName('min_time_on_load');
        $metricsList->addMetric($metric4);
        $metric = $dimensionMetricFactory->createComputedMetric($metric1->getName(), $metric3->getName(), ComputedMetric::AGGREGATION_AVG);
        $metric->setName('avg_time_on_load');
        $metric->setTranslatedName(Piwik::translate('PagePerformance_ColumnAverageTimeOnLoad'));
        $metric->setDocumentation(Piwik::translate('PagePerformance_ColumnAverageTimeOnLoadDocumentation'));
        $metricsList->addMetric($metric);
    }
}
