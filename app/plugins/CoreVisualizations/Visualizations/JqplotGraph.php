<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreVisualizations\Visualizations;

use Piwik\Plugins\CoreVisualizations\JqplotDataGenerator;
/**
 * DataTable visualization that displays DataTable data in a JQPlot graph.
 * TODO: should merge all this logic w/ jqplotdatagenerator & 'Chart' visualizations.
 *
 * @property JqplotGraph\Config $config
 */
abstract class JqplotGraph extends \Piwik\Plugins\CoreVisualizations\Visualizations\Graph
{
    const ID = 'jqplot_graph';
    const TEMPLATE_FILE = '@CoreVisualizations/_dataTableViz_jqplotGraph.twig';
    public static function getDefaultConfig()
    {
        return new \Piwik\Plugins\CoreVisualizations\Visualizations\JqplotGraph\Config();
    }
    public function getGraphData($dataTable, $properties)
    {
        $dataGenerator = $this->makeDataGenerator($properties);
        return $dataGenerator->generate($dataTable);
    }
    /**
     * @param $properties
     * @return JqplotDataGenerator
     */
    protected abstract function makeDataGenerator($properties);
}
require_once PIWIK_INCLUDE_PATH . '/plugins/CoreVisualizations/Visualizations/JqplotGraph/Bar.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/CoreVisualizations/Visualizations/JqplotGraph/Pie.php';
require_once PIWIK_INCLUDE_PATH . '/plugins/CoreVisualizations/Visualizations/JqplotGraph/Evolution.php';
