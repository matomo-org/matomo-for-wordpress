<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugin;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Metrics;
use Piwik\Metrics\Formatter;
/**
 * Base type of metric metadata classes.
 *
 * A metric metadata class is a class that describes how a metric is described, computed and
 * formatted.
 *
 * There are two types of metrics: aggregated and processed. An aggregated metric is computed
 * in the backend datastore and aggregated in PHP when archiving period reports.
 *
 * Currently, only processed metrics can be defined as metric metadata classes. Support for
 * aggregated metrics will be added at a later date.
 *
 * See {@link Piwik\Plugin\ProcessedMetric} and {@link Piwik\Plugin|AggregatedMetric}.
 *
 * @api
 */
abstract class Metric
{
    /**
     * The sub-namespace name in a plugin where Metric components are stored.
     */
    const COMPONENT_SUBNAMESPACE = 'Metrics';
    /**
     * Returns the column name of this metric, eg, `"nb_visits"` or `"avg_time_on_site"`.
     *
     * This string is what appears in API output.
     *
     * @return string
     */
    public abstract function getName();
    /**
     * Returns the human readable translated name of this metric, eg, `"Visits"` or `"Avg. time on site"`.
     *
     * This string is what appears in the UI.
     *
     * @return string
     */
    public abstract function getTranslatedName();
    /**
     * Returns the category that this metric belongs to.
     * @return string
     * @api since Piwik 3.2.0
     */
    public function getCategoryId()
    {
        return '';
    }
    /**
     * Returns a string describing what the metric represents. The result will be included in report metadata
     * API output, including processed reports.
     *
     * Implementing this method is optional.
     *
     * @return string
     */
    public function getDocumentation()
    {
        return "";
    }
    /**
     * Returns this metric's semantic type. This can be used to provide the semantic
     * type for processed metrics.
     *
     * A metric's semantic type is metadata used primarily in integrations with Matomo
     * and third party services/applications. It provides information that can be used
     * to determine how to display or use the information.
     *
     * It is recommended for your plugin to provide this information so users of third
     * party services that connect with Matomo can make full use of the data your plugin
     * tracks.
     *
     * See {@link \Piwik\Columns\Dimension} for the list of available semantic types.
     *
     * @return string|null
     */
    public function getSemanticType() : ?string
    {
        return null;
    }
    /**
     * Returns a formatted metric value. This value is what appears in API output. From within Piwik,
     * (core & plugins) the computed value is used. Only when outputting to the API does a metric
     * get formatted.
     *
     * By default, just returns the value.
     *
     * @param mixed $value The metric value.
     * @param Formatter $formatter The formatter to use when formatting a value.
     * @return mixed $value
     */
    public function format($value, Formatter $formatter)
    {
        return $value;
    }
    /**
     * Executed before formatting all metrics for a report. Implementers can return `false`
     * to skip formatting this metric and can use this method to access information needed for
     * formatting (for example, the site ID).
     *
     * @param Report $report
     * @param DataTable $table
     * @return bool Return `true` to format the metric for the table, `false` to skip formatting.
     */
    public function beforeFormat($report, DataTable $table)
    {
        return true;
    }
    /**
     * Helper method that will access a metric in a {@link Piwik\DataTable\Row} or array either by
     * its name or by its special numerical index value.
     *
     * @param Row|array $row
     * @param string $columnName
     * @param int[]|null $mappingNameToId A custom mapping of metric names to special index values. By
     *                                    default {@link Metrics::getMappingFromNameToId()} is used.
     * @return mixed The metric value or false if none exists.
     */
    public static function getMetric($row, $columnName, $mappingNameToId = null)
    {
        if ($row instanceof Row) {
            $value = $row->getColumn($columnName);
            if ($value === false) {
                if (empty($mappingNameToId)) {
                    $mappingNameToId = Metrics::getMappingFromNameToId();
                }
                if (isset($mappingNameToId[$columnName])) {
                    return $row->getColumn($mappingNameToId[$columnName]);
                }
            }
            return $value;
        } elseif (!empty($row)) {
            if (array_key_exists($columnName, $row)) {
                return $row[$columnName];
            } else {
                if (empty($mappingNameToId)) {
                    $mappingNameToId = Metrics::getMappingFromNameToId();
                }
                if (isset($mappingNameToId[$columnName])) {
                    $columnName = $mappingNameToId[$columnName];
                    if (array_key_exists($columnName, $row)) {
                        return $row[$columnName];
                    }
                }
            }
        }
        return null;
    }
    /**
     * Helper method that will determine the actual column name for a metric in a
     * {@link Piwik\DataTable} and return every column value for this name.
     *
     * @param DataTable $table
     * @param string $columnName
     * @param int[]|null $mappingNameToId A custom mapping of metric names to special index values. By
     *                                    default {@link Metrics::getMappingFromNameToId()} is used.
     * @return array
     */
    public static function getMetricValues(DataTable $table, $columnName, $mappingNameToId = null)
    {
        if (empty($mappingIdToName)) {
            $mappingNameToId = Metrics::getMappingFromNameToId();
        }
        $columnName = self::getActualMetricColumn($table, $columnName, $mappingNameToId);
        return $table->getColumn($columnName);
    }
    /**
     * Helper method that determines the actual column for a metric in a {@link Piwik\DataTable}.
     *
     * @param DataTable $table
     * @param string $columnName
     * @param int[]|null $mappingNameToId A custom mapping of metric names to special index values. By
     *                                    default {@link Metrics::getMappingFromNameToId()} is used.
     * @return string
     */
    public static function getActualMetricColumn(DataTable $table, $columnName, $mappingNameToId = null)
    {
        $firstRow = $table->getFirstRow();
        if (!empty($firstRow) && $firstRow->hasColumn($columnName) === false) {
            if (empty($mappingIdToName)) {
                $mappingNameToId = Metrics::getMappingFromNameToId();
            }
            if (array_key_exists($columnName, $mappingNameToId)) {
                $columnName = $mappingNameToId[$columnName];
            }
        }
        return $columnName;
    }
}
