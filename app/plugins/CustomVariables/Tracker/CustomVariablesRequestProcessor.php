<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\Tracker;

use Piwik\Common;
use Piwik\Plugins\CustomVariables\CustomVariables;
use Piwik\Plugins\CustomVariables\Model;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\RequestProcessor;
use Piwik\Tracker\Visit\VisitProperties;

/**
 * Handles tracking of visit level custom variables.
 *
 * ### Request Metadata
 *
 * Defines the following request metadata for the **CustomVariables** plugin:
 *
 * * **visitCustomVariables**: An array of custom variable names & values. The data is stored
 *                             as log_visit column name/value pairs, eg,
 *
 *                             ```
 *                             array(
 *                                 'custom_var_k1' => 'the name',
 *                                 'custom_var_v1' => 'the value',
 *                                 ...
 *                             )
 *                             ```
 */
class CustomVariablesRequestProcessor extends RequestProcessor
{
    public function processRequestParams(VisitProperties $visitProperties, Request $request)
    {
        // TODO: re-add optimization where if custom variables exist in request, don't bother selecting them in Visitor
        $visitorCustomVariables = self::getCustomVariablesInVisitScope($request);
        if (!empty($visitorCustomVariables)) {
            Common::printDebug("Visit level Custom Variables: ");
            Common::printDebug($visitorCustomVariables);
        }

        $request->setMetadata('CustomVariables', 'visitCustomVariables', $visitorCustomVariables);
    }

    public function onNewVisit(VisitProperties $visitProperties, Request $request)
    {
        $visitCustomVariables = $request->getMetadata('CustomVariables', 'visitCustomVariables');

        if (!empty($visitCustomVariables)) {
            $visitProperties->setProperties(array_merge($visitProperties->getProperties(), $visitCustomVariables));
        }
    }

    public function onExistingVisit(&$valuesToUpdate, VisitProperties $visitProperties, Request $request)
    {
        $visitCustomVariables = $request->getMetadata('CustomVariables', 'visitCustomVariables');

        if (!empty($visitCustomVariables)) {
            $valuesToUpdate = array_merge($valuesToUpdate, $visitCustomVariables);
        }
    }

    public function afterRequestProcessed(VisitProperties $visitProperties, Request $request)
    {
        $action = $request->getMetadata('Actions', 'action');

        if (empty($action) || !($action instanceof Action)) {
            return;
        }

        $customVariables = self::getCustomVariablesInPageScope($request);

        if (!empty($customVariables)) {
            Common::printDebug("Page level Custom Variables: ");
            Common::printDebug($customVariables);

            foreach ($customVariables as $field => $value) {
                $action->setCustomField($field, $value);
            }
        }
    }

    public static function getCustomVariablesInVisitScope(Request $request)
    {
        return self::getCustomVariables($request, '_cvar');
    }

    public static function getCustomVariablesInPageScope(Request $request)
    {
        return self::getCustomVariables($request, 'cvar');
    }

    private static function getCustomVariables(Request $request, $parameter)
    {
        $cvar      = Common::getRequestVar($parameter, '', 'json', $request->getParams());
        $customVar = Common::unsanitizeInputValues($cvar);

        if (!is_array($customVar)) {
            return array();
        }

        $customVariables = array();
        $maxCustomVars   = CustomVariables::getNumUsableCustomVariables();

        foreach ($customVar as $id => $keyValue) {
            $id = (int)$id;

            if ($id < 1
                || $id > $maxCustomVars
                || count($keyValue) != 2
                || (!is_string($keyValue[0]) && !is_numeric($keyValue[0])
                    || (!is_string($keyValue[1]) && !is_numeric($keyValue[1])))
            ) {
                Common::printDebug("Invalid custom variables detected (id=$id)");
                continue;
            }

            if (strlen($keyValue[1]) == 0) {
                $keyValue[1] = "";
            }
            // We keep in the URL when Custom Variable have empty names
            // and values, as it means they can be deleted server side

            $customVariables['custom_var_k' . $id] = self::truncateCustomVariable($keyValue[0]);
            $customVariables['custom_var_v' . $id] = self::truncateCustomVariable($keyValue[1]);
        }

        return $customVariables;
    }

    public static function truncateCustomVariable($input)
    {
        return substr(trim($input), 0, CustomVariables::getMaxLengthCustomVariables());
    }
}
