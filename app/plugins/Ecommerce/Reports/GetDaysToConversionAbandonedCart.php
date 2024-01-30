<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Ecommerce\Reports;

use Piwik\Piwik;
use Piwik\Plugins\Goals\Columns\DaysToConversion;
class GetDaysToConversionAbandonedCart extends \Piwik\Plugins\Ecommerce\Reports\Base
{
    protected function init()
    {
        parent::init();
        $this->action = 'getDaysToConversion';
        $this->name = Piwik::translate('General_AbandonedCarts') . ' - ' . Piwik::translate('Goals_DaysToConv');
        $this->dimension = new DaysToConversion();
        $this->constantRowsCount = true;
        $this->processedMetrics = false;
        $this->metrics = array('nb_conversions');
        $this->order = 25;
        $this->parameters = array('idGoal' => Piwik::LABEL_ID_GOAL_IS_ECOMMERCE_CART);
    }
}
