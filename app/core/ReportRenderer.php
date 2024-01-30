<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Exception;
use Piwik\API\Request;
use Piwik\Container\StaticContainer;
use Piwik\DataTable\Row;
use Piwik\DataTable\Simple;
use Piwik\Plugins\ImageGraph\API;
/**
 * A Report Renderer produces user friendly renderings of any given Piwik report.
 * All new Renderers must be copied in ReportRenderer and added to the $availableReportRenderers.
 */
abstract class ReportRenderer extends \Piwik\BaseFactory
{
    const DEFAULT_REPORT_FONT_FAMILY = 'dejavusans';
    const REPORT_TEXT_COLOR = "13,13,13";
    const REPORT_TITLE_TEXT_COLOR = "13,13,13";
    const TABLE_HEADER_BG_COLOR = "255,255,255";
    const TABLE_HEADER_TEXT_COLOR = "13,13,13";
    const TABLE_HEADER_TEXT_TRANSFORM = "uppercase";
    const TABLE_HEADER_TEXT_WEIGHT = "normal";
    const TABLE_CELL_BORDER_COLOR = "217,217,217";
    const TABLE_BG_COLOR = "242,242,242";
    const HTML_FORMAT = 'html';
    const PDF_FORMAT = 'pdf';
    const CSV_FORMAT = 'csv';
    const TSV_FORMAT = 'tsv';
    protected $idSite = 'all';
    protected $report;
    private static $availableReportRenderers = [self::PDF_FORMAT, self::HTML_FORMAT, self::CSV_FORMAT, self::TSV_FORMAT];
    /**
     * Sets the site id
     *
     * @param int $idSite
     */
    public function setIdSite($idSite)
    {
        $this->idSite = $idSite;
    }
    public function setReport($report)
    {
        $this->report = $report;
    }
    protected static function getClassNameFromClassId($rendererType)
    {
        return 'Piwik\\ReportRenderer\\' . self::normalizeRendererType($rendererType);
    }
    protected static function getInvalidClassIdExceptionMessage($rendererType)
    {
        return \Piwik\Piwik::translate('General_ExceptionInvalidReportRendererFormat', [self::normalizeRendererType($rendererType), implode(', ', self::$availableReportRenderers)]);
    }
    protected static function normalizeRendererType($rendererType)
    {
        return ucfirst(strtolower($rendererType));
    }
    /**
     * Initialize locale settings.
     * If not called, locale settings defaults to 'en'
     *
     * @param string $locale
     */
    public abstract function setLocale($locale);
    /**
     * Save rendering to disk
     *
     * @param string $filename without path & without format extension
     * @return string path of file
     */
    public abstract function sendToDisk($filename);
    /**
     * Send rendering to browser with a 'download file' prompt
     *
     * @param string $filename without path & without format extension
     */
    public abstract function sendToBrowserDownload($filename);
    /**
     * Output rendering to browser
     *
     * @param string $filename without path & without format extension
     */
    public abstract function sendToBrowserInline($filename);
    /**
     * Get rendered report
     */
    public abstract function getRenderedReport();
    /**
     * Generate the first page.
     *
     * @param string $reportTitle
     * @param string $prettyDate formatted date
     * @param string $description
     * @param array $reportMetadata metadata for all reports
     * @param array $segment segment applied to all reports
     */
    public abstract function renderFrontPage($reportTitle, $prettyDate, $description, $reportMetadata, $segment);
    /**
     * Render the provided report.
     * Multiple calls to this method before calling outputRendering appends each report content.
     *
     * @param array $processedReport @see API::getProcessedReport()
     */
    public abstract function renderReport($processedReport);
    /**
     * Get report attachments, ex. graph images
     *
     * @param $report
     * @param $processedReports
     * @param $prettyDate
     * @return array
     */
    public abstract function getAttachments($report, $processedReports, $prettyDate);
    /**
     * Append $extension to $filename
     *
     * @static
     * @param  string $filename
     * @param  string $extension
     * @return string  filename with extension
     */
    protected static function makeFilenameWithExtension($filename, $extension)
    {
        // the filename can be used in HTTP headers, remove new lines to prevent HTTP header injection
        $filename = str_replace(["\n", "\t"], " ", $filename);
        return $filename . "." . $extension;
    }
    /**
     * Return $filename with temp directory and delete file
     *
     * @static
     * @param  $filename
     * @return string path of file in temp directory
     */
    protected static function getOutputPath($filename)
    {
        $baseAssetsDir = StaticContainer::get('path.tmp') . '/assets/';
        $outputFilename = $baseAssetsDir . $filename;
        if (!is_dir($baseAssetsDir)) {
            \Piwik\Filesystem::mkdir($baseAssetsDir);
        }
        @chmod($outputFilename, 0600);
        if (file_exists($outputFilename)) {
            @unlink($outputFilename);
        }
        return $outputFilename;
    }
    protected static function writeFile($filename, $extension, $content)
    {
        $filename = self::makeFilenameWithExtension($filename, $extension);
        $outputFilename = self::getOutputPath($filename);
        $bytesWritten = file_put_contents($outputFilename, $content);
        if ($bytesWritten === false) {
            throw new Exception("ReportRenderer: Could not write to file '" . $outputFilename . "'.");
        }
        return $outputFilename;
    }
    protected static function sendToBrowser($filename, $extension, $contentType, $content)
    {
        $filename = \Piwik\ReportRenderer::makeFilenameWithExtension($filename, $extension);
        \Piwik\ProxyHttp::overrideCacheControlHeaders();
        \Piwik\Common::sendHeader('Content-Description: File Transfer');
        \Piwik\Common::sendHeader('Content-Type: ' . $contentType);
        \Piwik\Common::sendHeader('Content-Disposition: attachment; filename="' . str_replace('"', '\'', basename($filename)) . '";');
        \Piwik\Common::sendHeader('Content-Length: ' . strlen($content));
        echo $content;
    }
    protected static function inlineToBrowser($contentType, $content)
    {
        \Piwik\Common::sendHeader('Content-Type: ' . $contentType);
        echo $content;
    }
    /**
     * Convert a dimension-less report to a multi-row two-column data table
     *
     * @static
     * @param  $reportMetadata array
     * @param  $report DataTable
     * @param  $reportColumns array
     * @return array DataTable $report & array $columns
     */
    protected static function processTableFormat($reportMetadata, $report, $reportColumns)
    {
        $finalReport = $report;
        if (empty($reportMetadata['dimension'])) {
            $simpleReportMetrics = $report->getFirstRow();
            if ($simpleReportMetrics) {
                $finalReport = new Simple();
                foreach ($simpleReportMetrics->getColumns() as $metricId => $metric) {
                    $newRow = new Row();
                    $newRow->addColumn("label", $reportColumns[$metricId]);
                    $newRow->addColumn("value", $metric);
                    $finalReport->addRow($newRow);
                }
            }
            $reportColumns = ['label' => \Piwik\Piwik::translate('General_Name'), 'value' => \Piwik\Piwik::translate('General_Value')];
        }
        return [$finalReport, $reportColumns];
    }
    public static function getStaticGraph($reportMetadata, $width, $height, $evolution, $segment)
    {
        $imageGraphUrl = $reportMetadata['imageGraphUrl'];
        if ($evolution && !empty($reportMetadata['imageGraphEvolutionUrl'])) {
            $imageGraphUrl = $reportMetadata['imageGraphEvolutionUrl'];
        }
        $requestGraph = $imageGraphUrl . '&outputType=' . API::GRAPH_OUTPUT_PHP . '&format=original&serialize=0' . '&filter_truncate=' . '&width=' . $width . '&height=' . $height . ($segment != null ? '&segment=' . urlencode($segment['definition']) : '');
        $request = new Request($requestGraph);
        try {
            $imageGraph = $request->process();
            // Get image data as string
            ob_start();
            imagepng($imageGraph);
            $imageGraphData = ob_get_contents();
            ob_end_clean();
            imagedestroy($imageGraph);
            return $imageGraphData;
        } catch (Exception $e) {
            throw new Exception("ImageGraph API returned an error: " . $e->getMessage() . "\n");
        }
    }
}
