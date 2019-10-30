<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Live\Visualizations;

use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugin\Visualization;
use Piwik\Plugins\PrivacyManager\PrivacyManager;
use Piwik\Plugins\TagManager\Model\Container\StaticContainerIdGenerator;
use Piwik\Tracker\Action;
use Piwik\View;

/**
 * A special DataTable visualization for the Live.getLastVisitsDetails API method.
 *
 * @property VisitorLog\Config $config
 */
class VisitorLog extends Visualization
{
    const ID = 'VisitorLog';
    const TEMPLATE_FILE = "@Live/_dataTableViz_visitorLog.twig";
    const FOOTER_ICON_TITLE = '';
    const FOOTER_ICON = '';

    public static function getDefaultConfig()
    {
        return new VisitorLog\Config();
    }

    public function beforeLoadDataTable()
    {
        $this->requestConfig->addPropertiesThatShouldBeAvailableClientSide(array(
            'filter_limit',
            'filter_offset',
            'filter_sort_column',
            'filter_sort_order',
        ));

        if (!is_numeric($this->requestConfig->filter_limit)
            || $this->requestConfig->filter_limit == -1 // 'all' is not supported for this visualization
        ) {
            $defaultLimit = Config::getInstance()->General['datatable_default_limit'];
            $this->requestConfig->filter_limit = $defaultLimit;
        }

        if ($this->isInPopover()) {
            $this->requestConfig->filter_limit = 10;
        }

        $this->requestConfig->request_parameters_to_modify['filter_limit'] = $this->requestConfig->filter_limit+1; // request one more record, to check if a next page is available
        $this->requestConfig->disable_generic_filters = true;
        $this->requestConfig->filter_sort_column      = false;

        $view = $this;
        $this->config->filters[] = function (DataTable $table) use ($view) {
            if (Plugin\Manager::getInstance()->isPluginActivated('PrivacyManager') && PrivacyManager::haveLogsBeenPurged($table)) {
                $settings = PrivacyManager::getPurgeDataSettings();
                if (!empty($settings['delete_logs_older_than'])) {
                    $numDaysDelete = $settings['delete_logs_older_than'];
                    $view->config->no_data_message = Piwik::translate('CoreHome_ThereIsNoDataForThisReport') .  ' ' . Piwik::translate('Live_VisitorLogNoDataMessagePurged', $numDaysDelete);
                }
            }
        };
        $this->config->filters[] = function (DataTable $table) {
            $this->groupActionsByPageviewId($table);
        };
    }

    public function afterGenericFiltersAreAppliedToLoadedDataTable()
    {
        $this->requestConfig->filter_sort_column = false;
    }

    private function isInPopover()
    {
        return Common::getRequestVar('inPopover', '0') !== '0';
    }

    /**
     * Configure visualization.
     */
    public function beforeRender()
    {
        $this->config->show_as_content_block = false;
        $this->config->title = Piwik::translate('Live_VisitorLog');
        $this->config->disable_row_actions = true;
        $this->config->datatable_js_type = 'VisitorLog';
        $this->config->enable_sort       = false;
        $this->config->show_search       = false;
        $this->config->show_exclude_low_population = false;
        $this->config->show_offset_information     = false;
        $this->config->show_all_views_icons        = false;
        $this->config->show_table_all_columns      = false;
        $this->config->show_export_as_rss_feed     = false;
        $this->config->disable_all_rows_filter_limit = true;

        $this->config->documentation = Piwik::translate('Live_VisitorLogDocumentation', array('<br />', '<br />'));

        if (!is_array($this->config->custom_parameters)) {
            $this->config->custom_parameters = array();
        }

        // ensure to show next link if there are enough rows for a next page
        if ($this->dataTable->getRowsCount() > $this->requestConfig->filter_limit) {
            $this->dataTable->deleteRowsOffset($this->requestConfig->filter_limit);
            $this->config->custom_parameters['totalRows'] = 10000000;
        }

        $this->config->custom_parameters['smallWidth'] = (int)(1 == Common::getRequestVar('small', 0, 'int'));
        $this->config->custom_parameters['hideProfileLink'] = (int)(1 == Common::getRequestVar('hideProfileLink', 0, 'int'));
        $this->config->custom_parameters['pageUrlNotDefined'] = Piwik::translate('General_NotDefined', Piwik::translate('Actions_ColumnPageURL'));

        if (!$this->isInPopover()) {
            $this->config->footer_icons = array(
                array(
                    'class'   => 'tableAllColumnsSwitch',
                    'buttons' => array(
                        array(
                            'id'    => static::ID,
                            'title' => Piwik::translate('Live_LinkVisitorLog'),
                            'icon'  => 'plugins/Morpheus/images/table.png'
                        )
                    )
                )
            );
        } else {
            // It's opening in a popover, just show a few records and don't give the user any actions to play with
            $this->config->footer_icons = array();
            $this->config->show_export = false;
            $this->config->show_pagination_control = false;
            $this->config->show_limit_control = false;
        }
        $this->assignTemplateVar('actionsToDisplayCollapsed', StaticContainer::get('Live.pageViewActionsToDisplayCollapsed'));

        $enableAddNewSegment = Common::getRequestVar('enableAddNewSegment', false);
        if ($enableAddNewSegment) {
            $this->config->datatable_actions[] = [
                'id' => 'addSegmentToMatomo',
                'title' => Piwik::translate('SegmentEditor_AddThisToMatomo'),
                'icon' => 'icon-segment',
            ];
        }
    }

    public static function canDisplayViewDataTable(ViewDataTable $view)
    {
        return ($view->requestConfig->getApiModuleToRequest() === 'Live');
    }

    // TODO: need to unit test this
    public static function groupActionsByPageviewId(DataTable $table)
    {
        foreach ($table->getRows() as $row) {
            $actionGroups = [];
            foreach ($row->getColumn('actionDetails') as $key => $action) {
                // if action is not a pageview action
                if (empty($action['idpageview'])
                    && self::isPageviewAction($action)
                ) {
                    $actionGroups[] = [
                        'pageviewAction' => null,
                        'actionsOnPage' => [$action],
                        'refreshActions' => [],
                    ];
                    continue;
                }

                // if there is no idpageview for wahtever reason, invent one
                $idPageView = !empty($action['idpageview']) ? $action['idpageview'] : count($actionGroups);
                if (empty($actionGroups[$idPageView])) {
                    $actionGroups[$idPageView] = [
                        'pageviewAction' => null,
                        'actionsOnPage' => [],
                        'refreshActions' => [],
                    ];
                }

                if ($action['type'] == 'action') {
                    if (empty($actionGroups[$idPageView]['pageviewAction'])) {
                        $actionGroups[$idPageView]['pageviewAction'] = $action;
                    } else if (empty($actionGroups[$idPageView]['pageviewAction']['url'])) {
                        // set this action as the pageview action either if there isn't one set already, or the existing one
                        // has no URL
                        $actionGroups[$idPageView]['refreshActions'][] = $actionGroups[$idPageView]['pageviewAction'];
                        $actionGroups[$idPageView]['pageviewAction'] = $action;
                    } else {
                        $actionGroups[$idPageView]['refreshActions'][] = $actionGroups[$idPageView]['pageviewAction'];
                    }
                } else {
                    $actionGroups[$idPageView]['actionsOnPage'][] = $action;
                }
            }

            // merge action groups that have the same page url/action and no pageviewactions
            $actionGroups = self::mergeRefreshes($actionGroups);

            $row->setColumn('actionGroups', $actionGroups);
        }
    }

    private static function mergeRefreshes(array $actionGroups)
    {
        $previousId = null;
        foreach ($actionGroups as $idPageview => $group) {
            if (empty($previousId)) {
                $previousId = $idPageview;
                continue;
            }

            $action = $group['pageviewAction'];
            $lastActionGroup = $actionGroups[$previousId];

            $isLastGroupEmpty = empty($actionGroups[$previousId]['actionsOnPage']);
            $isPageviewActionSame = $lastActionGroup['pageviewAction']['url'] == $action['url']
                && $lastActionGroup['pageviewAction']['pageTitle'] == $action['pageTitle'];

            // if the current action has the same url/action name as the last, merge w/ the last action group
            if ($isLastGroupEmpty
                && $isPageviewActionSame
            ) {
                $actionGroups[$previousId]['refreshActions'][] = $action;
                $actionGroups[$previousId]['actionsOnPage'] = array_merge($actionGroups[$previousId]['actionsOnPage'], $actionGroups[$idPageview]['actionsOnPage']);
                unset($actionGroups[$idPageview]);
            } else {
                $previousId = $idPageview;
            }
        }
        return $actionGroups;
    }

    private static function isPageviewAction($action)
    {
        return $action['type'] != 'action'
            && $action['type'] != Action::TYPE_PAGE_URL
            && $action['type'] != Action::TYPE_PAGE_TITLE;
    }
}
