<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Period;

use Piwik\Date;
use Piwik\Period;
/**
 */
class Year extends Period
{
    const PERIOD_ID = 4;
    protected $label = 'year';
    /**
     * Returns the current period as a localized short string
     *
     * @return string
     */
    public function getLocalizedShortString()
    {
        return $this->getLocalizedLongString();
    }
    /**
     * Returns the current period as a localized long string
     *
     * @return string
     */
    public function getLocalizedLongString()
    {
        //"2009"
        $out = $this->getDateStart()->getLocalized(Date::DATE_FORMAT_YEAR);
        return $out;
    }
    /**
     * Returns the current period as a string
     *
     * @return string
     */
    public function getPrettyString()
    {
        $out = $this->getDateStart()->toString('Y');
        return $out;
    }
    /**
     * Generates the subperiods (one for each month of the year)
     */
    protected function generate()
    {
        if ($this->subperiodsProcessed) {
            return;
        }
        parent::generate();
        $year = $this->date->toString("Y");
        for ($i = 1; $i <= 12; $i++) {
            $this->addSubperiod(new \Piwik\Period\Month(Date::factory("{$year}-{$i}-01")));
        }
    }
    /**
     * Returns the current period as a string
     *
     * @param string $format
     * @return array
     */
    public function toString($format = 'ignored')
    {
        $this->generate();
        $stringMonth = array();
        foreach ($this->subperiods as $month) {
            $stringMonth[] = $month->getDateStart()->toString("Y") . "-" . $month->getDateStart()->toString("m") . "-01";
        }
        return $stringMonth;
    }
    public function getImmediateChildPeriodLabel()
    {
        return 'month';
    }
    public function getParentPeriodLabel()
    {
        return null;
    }
}
