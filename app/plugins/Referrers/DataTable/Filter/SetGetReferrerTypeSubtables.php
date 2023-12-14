<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Referrers\DataTable\Filter;

use Piwik\API\Request;
use Piwik\Common;
use Piwik\DataTable;
use Piwik\Period\Range;

/**
 * Utility function that sets the subtables for the getReferrerType report.
 *
 * If we're not getting an expanded datatable, the subtable ID is set to each parent
 * row's referrer type (stored in the label for the getReferrerType report).
 *
 * If we are getting an expanded datatable, the datatable for the row's referrer
 * type is loaded and attached to the appropriate row in the getReferrerType report.
 */
class SetGetReferrerTypeSubtables extends DataTable\BaseFilter
{
    /** @var int */
    private $idSite;

    /** @var string */
    private $period;

    /** @var string */
    private $date;

    /** @var string */
    private $segment;

    /** @var bool */
    private $expanded;

    /**
     * Constructor.
     *
     * @param DataTable $table The table to eventually filter.
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $segment
     * @param bool $expanded
     */
    public function __construct($table, $idSite, $period, $date, $segment, $expanded)
    {
        parent::__construct($table);
        $this->idSite   = $idSite;
        $this->period   = $period;
        $this->date     = $date;
        $this->segment  = $segment;
        $this->expanded = $expanded;
    }

    public function filter($table)
    {
        foreach ($table->getRows() as $row) {
            $typeReferrer = $row->getColumn('label');

            if ($typeReferrer != Common::REFERRER_TYPE_DIRECT_ENTRY) {
                if (!$this->expanded) // if we don't want the expanded datatable, then don't do any extra queries
                {
                    $row->setNonLoadedSubtableId($typeReferrer);
                } else if (!Range::isMultiplePeriod($this->date, $this->period))
                {
                    // otherwise, we have to get the other datatables
                    // NOTE: not yet possible to do this w/ DataTable\Map instances
                    // (actually it would be maybe possible by using $map->mergeChildren() or so build it would be slow)
                    $subtable = Request::processRequest('Referrers.getReferrerType', [
                        'idSite' => $this->idSite,
                        'period' => $this->period,
                        'date' => $this->date,
                        'segment' => $this->segment,
                        'idSubtable' => $typeReferrer,
                        'disable_generic_filters' => true,
                        'disable_queued_filters' => !$this->expanded
                    ], []);

                    $row->setSubtable($subtable);
                }
            }
        }

    }
}
