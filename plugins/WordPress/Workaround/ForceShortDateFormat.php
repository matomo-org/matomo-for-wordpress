<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Workaround;

use Piwik\Period;

class ForceShortDateFormat extends Period
{
    /**
     * @var Period
     */
    private $wrappedPeriod;

    public function __construct(Period $period)
    {
        parent::__construct($period->getDate());

        $this->wrappedPeriod = $period;
    }

    public function getPrettyString()
    {
        return $this->wrappedPeriod->getPrettyString();
    }

    public function getLocalizedShortString()
    {
        return $this->wrappedPeriod->getLocalizedShortString();
    }

    public function getLocalizedLongString()
    {
        // force using localized short string
        return $this->wrappedPeriod->getLocalizedShortString();
    }

    public function getImmediateChildPeriodLabel()
    {
        return $this->wrappedPeriod->getImmediateChildPeriodLabel();
    }

    public function getParentPeriodLabel()
    {
        return $this->wrappedPeriod->getParentPeriodLabel();
    }
}
