<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\DataTable\Filter;

use Piwik\DataTable;
use Piwik\DataTable\BaseFilter;
use Piwik\Metrics;
/**
 * Deletes all rows for which a specific column has a value that is lower than
 * specified minimum threshold value.
 *
 * **Basic usage examples**
 *
 *     // remove all countries from UserCountry.getCountry that have less than 3 visits
 *     $dataTable = // ... get a DataTable whose queued filters have been run ...
 *     $dataTable->filter('ExcludeLowPopulation', array('nb_visits', 3));
 *
 *     // remove all countries from UserCountry.getCountry whose percent of total visits is less than 5%
 *     $dataTable = // ... get a DataTable whose queued filters have been run ...
 *     $dataTable->filter('ExcludeLowPopulation', array('nb_visits', false, 0.05));
 *
 *     // remove all countries from UserCountry.getCountry whose bounce rate is less than 10%
 *     $dataTable = // ... get a DataTable that has a numerical bounce_rate column ...
 *     $dataTable->filter('ExcludeLowPopulation', array('bounce_rate', 0.10));
 *
 * @api
 */
class ExcludeLowPopulation extends BaseFilter
{
    const MINIMUM_SIGNIFICANT_PERCENTAGE_THRESHOLD = 0.02;
    /**
     * The minimum value to enforce in a datatable for a specified column. Rows found with
     * a value less than this are removed.
     *
     * @var number
     */
    private $minimumValue;
    private $columnToFilter;
    /**
     * Constructor.
     *
     * @param DataTable $table The DataTable that will be filtered eventually.
     * @param string $columnToFilter The name of the column whose value will determine whether
     *                               a row is deleted or not.
     * @param number|false $minimumValue The minimum column value. Rows with column values <
     *                                   this number will be deleted. If false,
     *                                   `$minimumPercentageThreshold` is used.
     * @param bool|float $minimumPercentageThreshold If supplied, column values must be a greater
     *                                               percentage of the sum of all column values than
     *                                               this percentage.
     */
    public function __construct($table, $columnToFilter, $minimumValue, $minimumPercentageThreshold = false)
    {
        parent::__construct($table);
        $row = $table->getFirstRow();
        if ($row === false) {
            return;
        }
        $this->columnToFilter = $this->selectColumnToExclude($columnToFilter, $row);
        if ($minimumValue == 0) {
            if ($minimumPercentageThreshold === false) {
                $minimumPercentageThreshold = self::MINIMUM_SIGNIFICANT_PERCENTAGE_THRESHOLD;
            }
            $allValues = $table->getColumn($this->columnToFilter);
            $sumValues = array_sum($allValues);
            $minimumValue = $sumValues * $minimumPercentageThreshold;
        }
        $this->minimumValue = $minimumValue;
    }
    /**
     * See {@link ExcludeLowPopulation}.
     *
     * @param DataTable $table
     */
    public function filter($table)
    {
        if (empty($this->columnToFilter)) {
            return;
        }
        $minimumValue = $this->minimumValue;
        $isValueLowPopulation = function ($value) use($minimumValue) {
            return $value < $minimumValue;
        };
        $table->filter('ColumnCallbackDeleteRow', array($this->columnToFilter, $isValueLowPopulation));
    }
    /**
     * Sets the column to be used for Excluding low population
     *
     * @param DataTable\Row $row
     * @return int
     */
    private function selectColumnToExclude($columnToFilter, $row)
    {
        if ($row->hasColumn($columnToFilter)) {
            return $columnToFilter;
        }
        // filter_excludelowpop=nb_visits but the column name is still Metrics::INDEX_NB_VISITS in the table
        $columnIdToName = Metrics::getMappingFromNameToId();
        if (isset($columnIdToName[$columnToFilter])) {
            $column = $columnIdToName[$columnToFilter];
            if ($row->hasColumn($column)) {
                return $column;
            }
        }
        return $columnToFilter;
    }
}
