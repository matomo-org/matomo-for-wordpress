<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
declare (strict_types=1);
namespace Piwik\Plugins\BotTracking\Reports;

use Piwik\Piwik;
class GetAIChatbotsRealTime extends \Piwik\Plugins\BotTracking\Reports\AbstractAIChatbotsRealTimeChatbotsReport
{
    protected function init() : void
    {
        parent::init();
        $this->name = Piwik::translate('BotTracking_AIChatbotsLast30MinutesTitle');
        $this->documentation = Piwik::translate('BotTracking_AIChatbotsLast30MinutesDocumentation');
        $this->order = 10;
    }
    /**
     * @return array<int, array{name: string, documentation: string, lastMinutes: int, order: int}>
     */
    protected function getWidgetDefinitions() : array
    {
        return \Piwik\Plugins\BotTracking\Reports\AIChatbotsRealTimeWidgets::getChatbotWidgets();
    }
}
