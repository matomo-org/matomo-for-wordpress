<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Config;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Translation\Translator;
/**
 * Check if Piwik can use LOAD DATA INFILE.
 */
class DbReaderCheck implements \Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic
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
        $isPiwikInstalling = !Config::getInstance()->existsLocalConfig();
        if ($isPiwikInstalling) {
            // Skip the diagnostic if Piwik is being installed
            return array();
        }
        if (!Db::hasReaderConfigured()) {
            // only show an entry when reader is actually configured
            return array();
        }
        $label = $this->translator->translate('Diagnostics_DatabaseReaderConnection');
        try {
            Db::getReader();
            return array(\Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::singleResult($label, \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_OK, ''));
        } catch (\Exception $e) {
        }
        $comment = Piwik::translate('Installation_CannotConnectToDb');
        return array(\Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::singleResult($label, \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_WARNING, $comment));
    }
}
