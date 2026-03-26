<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Workaround;

use Piwik\Piwik;

class ProcessedReportInnerCallHooks
{
    const PROCESSED_REPORT_INNER_EVENT = 'API.getProcessedReport.inner';
    const PROCESSED_REPORT_INNER_END_EVENT = 'API.getProcessedReport.inner.end';

    /**
     * @var array
     */
    private $apiRequestCallStack = [];

    public function onDispatchStart(&$finalParameters, $pluginName, $methodName) {
        $this->apiRequestCallStack[] = $pluginName . '.' . $methodName;

        if ($this->isPreviousApiMethodProcessedReport()) {
            Piwik::postEvent(self::PROCESSED_REPORT_INNER_EVENT, [&$finalParameters, $pluginName, $methodName]);
        }
    }

    public function onDispatchEnd(&$returnedValue, $extraInfo) {
        if ($this->isPreviousApiMethodProcessedReport()) {
            Piwik::postEvent(self::PROCESSED_REPORT_INNER_END_EVENT, [&$returnedValue, $extraInfo]);
        }

        array_pop($this->apiRequestCallStack);
    }

    private function isPreviousApiMethodProcessedReport()
    {
        $callDepth = count($this->apiRequestCallStack);
        return $callDepth > 1
            && $this->apiRequestCallStack[$callDepth - 2] === 'API.getProcessedReport';
    }
}
