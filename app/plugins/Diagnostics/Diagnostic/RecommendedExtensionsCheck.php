<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Diagnostics\Diagnostic;

use Piwik\Translation\Translator;
/**
 * Check the PHP extensions that are not required but recommended.
 */
class RecommendedExtensionsCheck implements \Piwik\Plugins\Diagnostics\Diagnostic\Diagnostic
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
        $label = $this->translator->translate('Installation_SystemCheckOtherExtensions');
        $result = new \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult($label);
        $loadedExtensions = @get_loaded_extensions();
        $loadedExtensions = array_map(function ($extension) {
            return strtolower($extension);
        }, $loadedExtensions);
        foreach ($this->getRecommendedExtensions() as $extension) {
            if (!in_array(strtolower($extension), $loadedExtensions)) {
                $status = \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_WARNING;
                $comment = $extension . '<br/>' . $this->getHelpMessage($extension);
            } else {
                $status = \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult::STATUS_OK;
                $comment = $extension;
            }
            $result->addItem(new \Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResultItem($status, $comment));
        }
        return array($result);
    }
    /**
     * @return string[]
     */
    private function getRecommendedExtensions()
    {
        return array('json', 'libxml', 'dom', 'SimpleXML', 'openssl');
    }
    private function getHelpMessage($missingExtension)
    {
        $messages = array('json' => 'Installation_SystemCheckWarnJsonHelp', 'libxml' => 'Installation_SystemCheckWarnLibXmlHelp', 'dom' => 'Installation_SystemCheckWarnDomHelp', 'SimpleXML' => 'Installation_SystemCheckWarnSimpleXMLHelp', 'openssl' => 'Installation_SystemCheckWarnOpensslHelp');
        return $this->translator->translate($messages[$missingExtension]);
    }
}
