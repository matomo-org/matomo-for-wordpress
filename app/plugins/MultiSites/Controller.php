<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MultiSites;

use Piwik\Common;
use Piwik\Config;
use Piwik\Date;
use Piwik\Piwik;
use Piwik\Translation\Translator;
use Piwik\View;
class Controller extends \Piwik\Plugin\Controller
{
    /**
     * @var Translator
     */
    private $translator;
    public function __construct(Translator $translator)
    {
        parent::__construct();
        $this->translator = $translator;
    }
    public function index()
    {
        return $this->getSitesInfo($isWidgetized = false);
    }
    public function standalone()
    {
        return $this->getSitesInfo($isWidgetized = true);
    }
    /**
     * @throws \Piwik\NoAccessException
     */
    public function getSitesInfo($isWidgetized = false)
    {
        Piwik::checkUserHasSomeViewAccess();
        $date = Piwik::getDate('today');
        $period = Piwik::getPeriod('day');
        $view = new View("@MultiSites/getSitesInfo");
        $view->isWidgetized = $isWidgetized;
        $view->displayRevenueColumn = Common::isGoalPluginEnabled();
        $view->limit = Config::getInstance()->General['all_websites_website_per_page'];
        $view->show_sparklines = Config::getInstance()->General['show_multisites_sparklines'];
        $view->autoRefreshTodayReport = 0;
        // if the current date is today, or yesterday,
        // in case the website is set to UTC-12), or today in UTC+14, we refresh the page every 5min
        if (in_array($date, array('today', date('Y-m-d'), 'yesterday', Date::factory('yesterday')->toString('Y-m-d'), Date::factory('now', 'UTC+14')->toString('Y-m-d')))) {
            $view->autoRefreshTodayReport = Config::getInstance()->General['multisites_refresh_after_seconds'];
        }
        $paramsToSet = ['period' => $period, 'date' => $date];
        $params = $this->getGraphParamsModified($paramsToSet);
        $view->dateSparkline = $period == 'range' ? $date : $params['date'];
        $this->setGeneralVariablesView($view);
        $view->siteName = $this->translator->translate('General_AllWebsitesDashboard');
        return $view->render();
    }
    public function getEvolutionGraph($columns = false)
    {
        if (empty($columns)) {
            $columns = Common::getRequestVar('columns');
        }
        $api = "API.get";
        if ($columns == 'revenue') {
            $api = "Goals.get";
        }
        $view = $this->getLastUnitGraph($this->pluginName, __FUNCTION__, $api);
        $view->requestConfig->totals = 0;
        return $this->renderView($view);
    }
}
