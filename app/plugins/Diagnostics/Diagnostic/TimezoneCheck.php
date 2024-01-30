<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\SettingsServer;
use Piwik\Translation\Translator;
/**
 * Check that the PHP timezone setting is set.
 */
class TimezoneCheck implements \Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic
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
        $label = $this->translator->translate('SitesManager_Timezone');
        if (SettingsServer::isTimezoneSupportEnabled()) {
            return array(\Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::singleResult($label, \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_OK));
        }
        $comment = sprintf('%s<br />%s.', $this->translator->translate('SitesManager_AdvancedTimezoneSupportNotFound'), '<a href="http://php.net/manual/en/datetime.installation.php" rel="noreferrer noopener" target="_blank">Timezone PHP documentation</a>');
        return array(\Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::singleResult($label, \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_WARNING, $comment));
    }
}
