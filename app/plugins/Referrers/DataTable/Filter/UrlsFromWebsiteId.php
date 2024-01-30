<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Referrers\DataTable\Filter;

use Piwik\DataTable\BaseFilter;
use Piwik\DataTable;
class UrlsFromWebsiteId extends BaseFilter
{
    /**
     * Constructor.
     *
     * @param DataTable $table The table to eventually filter.
     */
    public function __construct($table)
    {
        parent::__construct($table);
    }
    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $table->filter('ReplaceSummaryRowLabel');
        // the htmlspecialchars_decode call is for BC for before 1.1
        // as the Referrer URL was previously encoded in the log tables, but is now recorded raw
        $table->filter('ColumnCallbackAddMetadata', array('label', 'url', function ($label) {
            return htmlspecialchars_decode($label);
        }));
        $table->filter('GroupBy', array('label', 'Piwik\\Plugins\\Referrers\\getPathFromUrl'));
        foreach ($table->getRowsWithoutSummaryRow() as $row) {
            $subtable = $row->getSubtable();
            if ($subtable) {
                $this->filter($subtable);
            }
        }
    }
}
