<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Common;
use Piwik\Translation\Translator;
/**
 * Check that the tracker is working correctly.
 */
class TrackerCheck implements \Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic
{
    /**
     * @var Translator
     */
    private $translator;
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    public function execute()
    {
        $label = $this->translator->translate('Installation_SystemCheckTracker');
        $trackerStatus = Common::getRequestVar('trackerStatus', 0, 'int');
        if ($trackerStatus == 0) {
            $status = \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_OK;
            $comment = '';
        } else {
            $status = \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_WARNING;
            $comment = sprintf('%s<br />%s<br />%s', $trackerStatus, $this->translator->translate('Installation_SystemCheckTrackerHelp'), $this->translator->translate('Installation_RestartWebServer'));
        }
        return array(\Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::singleResult($label, $status, $comment));
    }
}
