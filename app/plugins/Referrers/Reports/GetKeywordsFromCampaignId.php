<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Referrers\Reports;

use Piwik\Piwik;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Referrers\Columns\Keyword;
use Piwik\Url;

class GetKeywordsFromCampaignId extends Base
{
    protected function init()
    {
        parent::init();
        $this->dimension     = new Keyword();
        $this->name          = Piwik::translate('Referrers_Campaigns');
        $this->documentation = Piwik::translate('Referrers_CampaignsReportDocumentation',
                               ['<br />', '<a href="' . Url::addCampaignParametersToMatomoLink('https://matomo.org/docs/tracking-campaigns/') . '" rel="noreferrer noopener" target="_blank">', '</a>']);
        $this->isSubtableReport = true;
        $this->order = 10;
    }

    public function configureView(ViewDataTable $view)
    {
        $view->config->show_search = false;
        $view->config->show_exclude_low_population = false;
    }

}
