<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\MobileMessaging\ReportRenderer;

use Piwik\ReportRenderer;
/**
 *
 */
class ReportRendererException extends ReportRenderer
{
    private $rendering = "";
    function __construct($exception)
    {
        $this->rendering = $exception;
    }
    public function setLocale($locale)
    {
        // nothing to do
    }
    public function sendToDisk($filename)
    {
        return ReportRenderer::writeFile($filename, \Piwik\Plugins\MobileMessaging\ReportRenderer\Sms::SMS_FILE_EXTENSION, $this->rendering);
    }
    public function sendToBrowserDownload($filename)
    {
        ReportRenderer::sendToBrowser($filename, \Piwik\Plugins\MobileMessaging\ReportRenderer\Sms::SMS_FILE_EXTENSION, \Piwik\Plugins\MobileMessaging\ReportRenderer\Sms::SMS_CONTENT_TYPE, $this->rendering);
    }
    public function sendToBrowserInline($filename)
    {
        ReportRenderer::inlineToBrowser(\Piwik\Plugins\MobileMessaging\ReportRenderer\Sms::SMS_CONTENT_TYPE, $this->rendering);
    }
    public function getRenderedReport()
    {
        return $this->rendering;
    }
    public function renderFrontPage($reportTitle, $prettyDate, $description, $reportMetadata, $segment)
    {
        // nothing to do
    }
    public function renderReport($processedReport)
    {
        // nothing to do
    }
    /**
     * Get report attachments, ex. graph images
     *
     * @param $report
     * @param $processedReports
     * @param $prettyDate
     * @return array
     */
    public function getAttachments($report, $processedReports, $prettyDate)
    {
        return array();
    }
}
