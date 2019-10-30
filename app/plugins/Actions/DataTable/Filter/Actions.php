<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Actions\DataTable\Filter;

use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable\BaseFilter;
use Piwik\DataTable\Row;
use Piwik\DataTable;
use Piwik\Tracker\Action;

class Actions extends BaseFilter
{
    private $actionType;
    /**
     * Constructor.
     *
     * @param DataTable $table The table to eventually filter.
     * @param bool $isPageTitleType Whether we are handling page title or regular URL
     */
    public function __construct($table, $actionType)
    {
        parent::__construct($table);
        $this->actionType = $actionType;
    }

    /**
     * @param DataTable $table
     */
    public function filter($table)
    {
        $isFlattening = Common::getRequestVar('flat', 0);
        $table->filter(function (DataTable $dataTable) use ($isFlattening) {
            $site = $dataTable->getMetadata('site');
            $urlPrefix = $site ? $site->getMainUrl() : null;

            $defaultActionName = Config::getInstance()->General['action_default_name'];

            $isPageTitleType = $this->actionType == Action::TYPE_PAGE_TITLE;

            // for BC, we read the old style delimiter first (see #1067)
            $actionDelimiter = @Config::getInstance()->General['action_category_delimiter'];
            if (empty($actionDelimiter)) {
                if ($isPageTitleType) {
                    $actionDelimiter = Config::getInstance()->General['action_title_category_delimiter'];
                } else {
                    $actionDelimiter = Config::getInstance()->General['action_url_category_delimiter'];
                }
            }

            foreach ($dataTable->getRows() as $row) {
                if (!$row->isSummaryRow()) {
                    $url = $row->getMetadata('url');
                    $pageTitlePath = $row->getMetadata('page_title_path');
                    $folderUrlStart = $row->getMetadata('folder_url_start');
                    $label = $row->getColumn('label');
                    if ($url) {
                        $row->setMetadata('segmentValue', urlencode($url));
                    } else if ($folderUrlStart) {
                        $row->setMetadata('segment', 'pageUrl=^' . urlencode(urlencode($folderUrlStart)));
                    } else if ($pageTitlePath) {
                        if ($row->getIdSubDataTable()) {
                            $row->setMetadata('segment', 'pageTitle=^' . urlencode(urlencode(trim($pageTitlePath))));
                        } else {
                            $row->setMetadata('segmentValue', urlencode(trim($pageTitlePath)));
                        }
                    } else if ($isPageTitleType && !in_array($label, [DataTable::LABEL_SUMMARY_ROW])) {
                        // for older data w/o page_title_path metadata
                        if ($row->getIdSubDataTable()) {
                            $row->setMetadata('segment', 'pageTitle=^' . urlencode(urlencode(trim($label))));
                        } else {
                            $row->setMetadata('segmentValue', urlencode(trim($label)));
                        }
                    } else if ($this->actionType == Action::TYPE_PAGE_URL && $urlPrefix) { // folder for older data w/ no folder URL metadata
                        $row->setMetadata('segment', 'pageUrl=^' . urlencode(urlencode($urlPrefix . '/' . $label)));
                    }
                }

                // remove the default action name 'index' in the end of flattened urls and prepend $actionDelimiter
                if ($isFlattening) {
                    $label = $row->getColumn('label');
                    $stringToSearch = $actionDelimiter.$defaultActionName;
                    if (substr($label, -strlen($stringToSearch)) == $stringToSearch) {
                        $label = substr($label, 0, -strlen($defaultActionName));
                        $label = rtrim($label, $actionDelimiter) . $actionDelimiter;
                        $row->setColumn('label', $label);
                    }
                    $dataTable->setLabelsHaveChanged();
                }

                $row->deleteMetadata('folder_url_start');
                $row->deleteMetadata('page_title_path');
            }
        });

        foreach ($table->getRowsWithoutSummaryRow() as $row) {
            $subtable = $row->getSubtable();
            if ($subtable) {
                $this->filter($subtable);
            }
        }
    }
}