<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Actions\Columns;

use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\Visitor;
use Piwik\Plugin\Dimension\ActionDimension;
class PageViewPosition extends ActionDimension
{
    protected $columnName = 'pageview_position';
    protected $nameSingular = 'Actions_ColumnPageViewPosition';
    protected $type = self::TYPE_NUMBER;
    /**
     * @param Request $request
     * @param Visitor $visitor
     * @param Action $action
     *
     * @return mixed|false
     */
    public function onNewAction(Request $request, Visitor $visitor, Action $action)
    {
        $shouldCount = \Piwik\Plugins\Actions\Columns\VisitTotalInteractions::shouldCountInteraction($action);
        if ($shouldCount && $visitor->isNewVisit()) {
            return 1;
        } else {
            if ($shouldCount) {
                return \Piwik\Plugins\Actions\Columns\VisitTotalInteractions::getNextInteractionPosition($request);
            }
        }
        // we re-use same interaction position as last page view eg for events etc.
        $position = \Piwik\Plugins\Actions\Columns\VisitTotalInteractions::getCurrentInteractionPosition($request);
        if ($position >= 1) {
            return $position;
        }
        return false;
    }
}
