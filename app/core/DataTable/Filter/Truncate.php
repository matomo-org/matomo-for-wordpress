<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\DataTable\Filter;

use Piwik\DataTable\BaseFilter;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Piwik;
/**
 * Truncates a {@link DataTable} by merging all rows after a certain index into a new summary
 * row. If the count of rows is less than the index, nothing happens.
 *
 * The {@link ReplaceSummaryRowLabel} filter will be queued after the table is truncated.
 *
 * ### Examples
 *
 * **Basic usage**
 *
 *     $dataTable->filter('Truncate', [$truncateAfter = 500]);
 *
 * **Using a custom summary row label**
 *
 *     $dataTable->filter('Truncate', [$truncateAfter = 500, $summaryRowLabel = Piwik::translate('General_Total')]);
 *
 * @api
 */
class Truncate extends BaseFilter
{
    /**
     * @var int
     */
    protected $truncateAfter;
    /**
     * @var string|null
     */
    protected $labelSummaryRow;
    /**
     * @var string|null
     */
    protected $columnToSortByBeforeTruncating;
    /**
     * @var bool
     */
    protected $filterRecursive;
    /**
     * Constructor.
     *
     * @param DataTable $table The table that will be filtered eventually.
     * @param int $truncateAfter The row index to truncate at. All rows passed this index will
     *                           be removed.
     * @param string $labelSummaryRow The label to use for the summary row. Defaults to
     *                                `Piwik::translate('General_Others')`.
     * @param string $columnToSortByBeforeTruncating The column to sort by before truncation, eg,
     *                                               `'nb_visits'`.
     * @param bool $filterRecursive If true executes this filter on all subtables descending from
     *                              `$table`.
     */
    public function __construct($table, $truncateAfter, $labelSummaryRow = null, $columnToSortByBeforeTruncating = null, $filterRecursive = true)
    {
        parent::__construct($table);
        $this->truncateAfter = $truncateAfter;
        if ($labelSummaryRow === null) {
            $labelSummaryRow = Piwik::translate('General_Others');
        }
        $this->labelSummaryRow = $labelSummaryRow;
        $this->columnToSortByBeforeTruncating = $columnToSortByBeforeTruncating;
        $this->filterRecursive = $filterRecursive;
    }
    /**
     * Executes the filter, see {@link Truncate}.
     *
     * @param DataTable $table
     */
    public function filter($table)
    {
        if ($this->truncateAfter < 0) {
            return;
        }
        $this->addSummaryRow($table);
        $table->queueFilter('ReplaceSummaryRowLabel', [$this->labelSummaryRow]);
        if ($this->filterRecursive) {
            foreach ($table->getRowsWithoutSummaryRow() as $row) {
                if ($row->isSubtableLoaded()) {
                    $this->filter($row->getSubtable());
                }
            }
        }
    }
    /**
     * @param DataTable $table
     */
    private function addSummaryRow($table)
    {
        if ($table->getRowsCount() <= $this->truncateAfter + 1) {
            return;
        }
        $table->filter('Sort', [$this->columnToSortByBeforeTruncating, 'desc', $naturalSort = true, $recursiveSort = false]);
        $rows = array_values($table->getRows());
        $count = $table->getRowsCount();
        $newRow = new Row([Row::COLUMNS => ['label' => DataTable::LABEL_SUMMARY_ROW]]);
        $aggregationOps = $table->getMetadata(DataTable::COLUMN_AGGREGATION_OPS_METADATA_NAME);
        for ($i = $this->truncateAfter; $i < $count; $i++) {
            if (!isset($rows[$i])) {
                // case when the last row is a summary row, it is not indexed by $count but by DataTable::ID_SUMMARY_ROW
                $summaryRow = $table->getRowFromId(DataTable::ID_SUMMARY_ROW);
                //FIXME: I'm not sure why it could return false, but it was reported in: http://forum.piwik.org/read.php?2,89324,page=1#msg-89442
                if ($summaryRow) {
                    $newRow->sumRow($summaryRow, $enableCopyMetadata = false, $aggregationOps);
                }
            } else {
                $newRow->sumRow($rows[$i], $enableCopyMetadata = false, $aggregationOps);
            }
        }
        $table->filter('Limit', [0, $this->truncateAfter]);
        $table->addSummaryRow($newRow);
        unset($rows);
    }
}
