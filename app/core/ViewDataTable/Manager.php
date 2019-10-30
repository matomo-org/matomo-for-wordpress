<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\ViewDataTable;

use Piwik\Cache;
use Piwik\Common;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\Cloud;
use Piwik\Plugins\CoreVisualizations\Visualizations\HtmlTable;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Bar;
use Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Pie;
use Piwik\Plugins\Goals\Visualizations\Goals;
use Piwik\Plugins\Insights\Visualizations\Insight;
use Piwik\Plugin\Manager as PluginManager;

/**
 * ViewDataTable Manager.
 *
 */
class Manager
{
    /**
     * Returns the viewDataTable IDs of a visualization's class lineage.
     *
     * @see self::getVisualizationClassLineage
     *
     * @param string $klass The visualization class.
     *
     * @return array
     */
    public static function getIdsWithInheritance($klass)
    {
        $klasses = Common::getClassLineage($klass);

        $result = array();
        foreach ($klasses as $klass) {
            try {
                $result[] = $klass::getViewDataTableId();
            } catch (\Exception $e) {
                // in case $klass did not define an id: eg Plugin\ViewDataTable
                continue;
            }
        }

        return $result;
    }

    /**
     * Returns all registered visualization classes. Uses the 'Visualization.getAvailable'
     * event to retrieve visualizations.
     *
     * @return array Array mapping visualization IDs with their associated visualization classes.
     * @throws \Exception If a visualization class does not exist or if a duplicate visualization ID
     *                   is found.
     * @return array
     */
    public static function getAvailableViewDataTables()
    {
        $cache = Cache::getTransientCache();
        $cacheId = 'ViewDataTable.getAvailableViewDataTables';
        $dataTables = $cache->fetch($cacheId);

        if (!empty($dataTables)) {
            return $dataTables;
        }

        $klassToExtend = '\\Piwik\\Plugin\\ViewDataTable';

        /** @var string[] $visualizations */
        $visualizations = PluginManager::getInstance()->findMultipleComponents('Visualizations', $klassToExtend);

        $result = array();

        foreach ($visualizations as $viz) {
            if (!class_exists($viz)) {
                throw new \Exception("Invalid visualization class '$viz' found in Visualization.getAvailableVisualizations.");
            }

            if (!is_subclass_of($viz, $klassToExtend)) {
                throw new \Exception("ViewDataTable class '$viz' does not extend Plugin/ViewDataTable");
            }

            $vizId = $viz::getViewDataTableId();

            if (isset($result[$vizId])) {
                throw new \Exception("ViewDataTable ID '$vizId' is already in use!");
            }

            $result[$vizId] = $viz;
        }

        /**
         * Triggered to filter available DataTable visualizations.
         *
         * Plugins that want to disable certain visualizations should subscribe to
         * this event and remove visualizations from the incoming array.
         *
         * **Example**
         *
         *     public function filterViewDataTable(&$visualizations)
         *     {
         *         unset($visualizations[HtmlTable::ID]);
         *     }
         *
         * @param array &$visualizations An array of all available visualizations indexed by visualization ID.
         * @since Piwik 3.0.0
         */
        Piwik::postEvent('ViewDataTable.filterViewDataTable', array(&$result));

        $cache->save($cacheId, $result);

        return $result;
    }

    /**
     * Returns all available visualizations that are not part of the CoreVisualizations plugin.
     *
     * @return array Array mapping visualization IDs with their associated visualization classes.
     */
    public static function getNonCoreViewDataTables()
    {
        $result = array();

        foreach (static::getAvailableViewDataTables() as $vizId => $vizClass) {
            if (false === strpos($vizClass, 'Piwik\\Plugins\\CoreVisualizations')
                && false === strpos($vizClass, 'Piwik\\Plugins\\Goals\\Visualizations\\Goals')) {
                $result[$vizId] = $vizClass;
            }
        }

        return $result;
    }

    /**
     * This method determines the default set of footer icons to display below a report.
     *
     * $result has the following format:
     *
     * ```
     * array(
     *     array( // footer icon group 1
     *         'class' => 'footerIconGroup1CssClass',
     *         'buttons' => array(
     *             'id' => 'myid',
     *             'title' => 'My Tooltip',
     *             'icon' => 'path/to/my/icon.png'
     *         )
     *     ),
     *     array( // footer icon group 2
     *         'class' => 'footerIconGroup2CssClass',
     *         'buttons' => array(...)
     *     ),
     *     ...
     * )
     * ```
     */
    public static function configureFooterIcons(ViewDataTable $view)
    {
        $result = array();

        $normalViewIcons = self::getNormalViewIcons($view);

        if (!empty($normalViewIcons['buttons'])) {
            $result[] = $normalViewIcons;
        }

        // add insight views
        $insightsViewIcons = array(
            'class'   => 'tableInsightViews',
            'buttons' => array(),
        );

        $graphViewIcons = self::getGraphViewIcons($view);

        $nonCoreVisualizations = static::getNonCoreViewDataTables();

        foreach ($nonCoreVisualizations as $id => $klass) {
            if ($klass::canDisplayViewDataTable($view) || $view::ID == $id) {
                $footerIcon = static::getFooterIconFor($id);
                if (Insight::ID == $footerIcon['id']) {
                    $insightsViewIcons['buttons'][] = static::getFooterIconFor($id);
                } else {
                    $graphViewIcons['buttons'][] = static::getFooterIconFor($id);
                }
            }
        }

        $graphViewIcons['buttons'] = array_filter($graphViewIcons['buttons']);

        if (!empty($insightsViewIcons['buttons'])
            && $view->config->show_insights
        ) {
            $result[] = $insightsViewIcons;
        }

        if (!empty($graphViewIcons['buttons'])) {
            $result[] = $graphViewIcons;
        }

        return $result;
    }

    /**
     * Returns an array with information necessary for adding the viewDataTable to the footer.
     *
     * @param string $viewDataTableId
     *
     * @return array
     */
    private static function getFooterIconFor($viewDataTableId)
    {
        $tables = static::getAvailableViewDataTables();

        if (!array_key_exists($viewDataTableId, $tables)) {
            return;
        }

        $klass = $tables[$viewDataTableId];

        return array(
            'id'    => $klass::getViewDataTableId(),
            'title' => Piwik::translate($klass::FOOTER_ICON_TITLE),
            'icon'  => $klass::FOOTER_ICON,
        );
    }

    public static function clearAllViewDataTableParameters()
    {
        Option::deleteLike('viewDataTableParameters_%');
    }

    public static function clearUserViewDataTableParameters($userLogin)
    {
        Option::deleteLike('viewDataTableParameters_' . $userLogin . '_%');
    }

    public static function getViewDataTableParameters($login, $controllerAction, $containerId = null)
    {
        $paramsKey = self::buildViewDataTableParametersOptionKey($login, $controllerAction, $containerId);
        $params    = Option::get($paramsKey);

        if (empty($params)) {
            return array();
        }

        $params = json_decode($params);
        $params = (array) $params;

        // when setting an invalid parameter, we silently ignore the invalid parameter and proceed
        $params = self::removeNonOverridableParameters($controllerAction, $params);
        self::unsetComparisonParams($params);

        return $params;
    }

    /**
     * Any parameter set here will be set into one of the following objects:
     *
     * - ViewDataTable.requestConfig[paramName]
     * - ViewDataTable.config.custom_parameters[paramName]
     * - ViewDataTable.config.custom_parameters[paramName]
     *
     * (see ViewDataTable::overrideViewPropertiesWithParams)

     * @param $login
     * @param $controllerAction
     * @param $parametersToOverride
     * @param string|null $containerId
     * @throws \Exception
     */
    public static function saveViewDataTableParameters($login, $controllerAction, $parametersToOverride, $containerId = null)
    {
        $params = self::getViewDataTableParameters($login, $controllerAction);

        self::unsetComparisonParams($params);

        foreach ($parametersToOverride as $key => $value) {
            if ($key === 'viewDataTable'
                && !empty($params[$key])
                && $params[$key] !== $value) {
                if (!empty($params['columns'])) {
                    unset($params['columns']);
                }
                if (!empty($params['columns_to_display'])) {
                    unset($params['columns_to_display']);
                }
            }

            $params[$key] = $value;
        }

        $paramsKey = self::buildViewDataTableParametersOptionKey($login, $controllerAction, $containerId);

        // when setting an invalid parameter, we fail and let user know
        self::errorWhenSettingNonOverridableParameter($controllerAction, $params);

        Option::set($paramsKey, json_encode($params));
    }

    private static function buildViewDataTableParametersOptionKey($login, $controllerAction, $containerId)
    {
        $result = sprintf('viewDataTableParameters_%s_%s', $login, $controllerAction);
        if (!empty($containerId)) {
            $result .= '_' . $containerId;
        }
        return $result;
    }

    /**
     * Display a meaningful error message when any invalid parameter is being set.
     *
     * @param $params
     * @throws
     */
    private static function errorWhenSettingNonOverridableParameter($controllerAction, $params)
    {
        $viewDataTable = self::makeTemporaryViewDataTableInstance($controllerAction, $params);
        $viewDataTable->throwWhenSettingNonOverridableParameter($params);
    }

    private static function removeNonOverridableParameters($controllerAction, $params)
    {
        $viewDataTable = self::makeTemporaryViewDataTableInstance($controllerAction, $params);
        $nonOverridableParams = $viewDataTable->getNonOverridableParams($params);

        foreach($params as $key => $value) {
            if(in_array($key, $nonOverridableParams)) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * @param $controllerAction
     * @param $params
     * @return ViewDataTable
     * @throws \Exception
     */
    private static function makeTemporaryViewDataTableInstance($controllerAction, $params)
    {
        $report = new Report();
        $viewDataTableType = isset($params['viewDataTable']) ? $params['viewDataTable'] : $report->getDefaultTypeViewDataTable();

        $apiAction = $controllerAction;
        $loadViewDataTableParametersForUser = false;
        $viewDataTable = Factory::build($viewDataTableType, $apiAction, $controllerAction, $forceDefault = false, $loadViewDataTableParametersForUser);
        return $viewDataTable;
    }

    private static function getNormalViewIcons(ViewDataTable $view)
    {
        // add normal view icons (eg, normal table, all columns, goals)
        $normalViewIcons = array(
            'class'   => 'tableAllColumnsSwitch',
            'buttons' => array(),
        );

        if ($view->config->show_table) {
            $normalViewIcons['buttons'][] = static::getFooterIconFor(HtmlTable::ID);
        }

        if ($view->config->show_table_all_columns) {
            $normalViewIcons['buttons'][] = static::getFooterIconFor(HtmlTable\AllColumns::ID);
        }

        if ($view->config->show_goals) {
            $goalButton = static::getFooterIconFor(Goals::ID);
            if (Common::getRequestVar('idGoal', false) == 'ecommerceOrder') {
                $goalButton['icon'] = 'icon-ecommerce-order';
            }

            $normalViewIcons['buttons'][] = $goalButton;
        }

        if ($view->config->show_ecommerce) {
            $normalViewIcons['buttons'][] = array(
                'id' => 'ecommerceOrder',
                'title' => Piwik::translate('General_EcommerceOrders'),
                'icon' => 'icon-ecommerce-order',
                'text' => Piwik::translate('General_EcommerceOrders')
            );

            $normalViewIcons['buttons'][] = array(
                'id' => 'ecommerceAbandonedCart',
                'title' => Piwik::translate('General_AbandonedCarts'),
                'icon' => 'icon-ecommerce-abandoned-cart',
                'text' => Piwik::translate('General_AbandonedCarts')
            );
        }

        $normalViewIcons['buttons'] = array_filter($normalViewIcons['buttons']);

        return $normalViewIcons;
    }

    private static function getGraphViewIcons(ViewDataTable $view)
    {
        // add graph views
        $graphViewIcons = array(
            'class'   => 'tableGraphViews',
            'buttons' => array(),
        );

        if ($view->config->show_all_views_icons) {
            if ($view->config->show_bar_chart) {
                $graphViewIcons['buttons'][] = static::getFooterIconFor(Bar::ID);
            }

            if ($view->config->show_pie_chart) {
                $graphViewIcons['buttons'][] = static::getFooterIconFor(Pie::ID);
            }

            if ($view->config->show_tag_cloud) {
                $graphViewIcons['buttons'][] = static::getFooterIconFor(Cloud::ID);
            }
        }

        return $graphViewIcons;
    }

    private static function unsetComparisonParams(&$params)
    {
        unset($params['compareDates']);
        unset($params['comparePeriods']);
        unset($params['compareSegments']);
        unset($params['compare']);
    }
}
