<?php

namespace Davaxi;

use Davaxi\Sparkline\DataTrait;
use Davaxi\Sparkline\FormatTrait;
use Davaxi\Sparkline\Picture;
use Davaxi\Sparkline\PointTrait;
use Davaxi\Sparkline\StyleTrait;

/**
 * Class Sparkline.
 */
class Sparkline
{
    use StyleTrait;
    use DataTrait;
    use FormatTrait;
    use PointTrait;

    const MIN_DATA_LENGTH = 2;
    const FORMAT_DIMENSION = 2;
    const HEXADECIMAL_ALIAS_LENGTH = 3;
    const CSS_PADDING_ONE = 1;
    const CSS_PADDING_TWO = 2;
    const CSS_PADDING_THREE = 3;
    const CSS_PADDING = 4;

    /**
     * @var string
     *             ex: QUERY_STRING if dedicated url
     */
    protected $eTag;

    /**
     * @var int
     */
    protected $expire;

    /**
     * @var string
     */
    protected $filename = 'sparkline';

    /**
     * @var resource
     */
    protected $file;

    /**
     * @var array
     */
    protected $server = [];

    /**
     * Sparkline constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        if (!extension_loaded('gd')) {
            throw new \InvalidArgumentException('GD extension is not installed');
        }
    }

    /**
     * @param string $eTag
     */
    public function setETag($eTag)
    {
        if (null === $eTag) {
            $this->eTag = null;

            return;
        }
        $this->eTag = md5($eTag);
    }

    /**
     * @param string $filename
     *                         Without extension
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param string|int $expire
     *                           time format or string format
     */
    public function setExpire($expire)
    {
        if (null === $expire) {
            $this->expire = null;

            return;
        }
        if (is_numeric($expire)) {
            $this->expire = $expire;

            return;
        }
        $this->expire = strtotime($expire);
    }

    public function generate()
    {
        list($width, $height) = $this->getNormalizedSize();

        $count = $this->getCount();

        $picture = new Picture($width, $height);
        $picture->applyBackground($this->backgroundColor);
        $picture->applyThickness($this->lineThickness * $this->ratioComputing);

        $stepCount = $this->getMaxNumberOfDataPointsAcrossSerieses();

        foreach ($this->data as $seriesIndex => $series) {
            list($polygon, $line) = $this->getChartElements($series, $stepCount);
            $picture->applyPolygon($polygon, $this->getFillColor($seriesIndex), $count);
            $picture->applyLine($line, $this->getLineColor($seriesIndex));

            foreach ($this->points as $point) {
                if ($point['series'] != $seriesIndex) {
                    continue;
                }

                $isFirst = $point['index'] === 0;
                $lineIndex = $isFirst ? 0 : $point['index'] - 1;
                $picture->applyDot(
                    $line[$lineIndex][$isFirst ? 0 : 2],
                    $line[$lineIndex][$isFirst ? 1 : 3],
                    $point['radius'] * $this->ratioComputing,
                    $point['color']
                );
            }
        }

        $this->file = $picture->generate($this->width, $this->height);
    }

    /**
     * @param array $server
     */
    public function setServer(array $server)
    {
        $this->server = $server;
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function getServerValue($key)
    {
        if (isset($this->server[$key])) {
            return $this->server[$key];
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function checkNoModified()
    {
        $httpIfNoneMatch = $this->getServerValue('HTTP_IF_NONE_MATCH');
        if ($this->eTag && $httpIfNoneMatch) {
            if ($httpIfNoneMatch === $this->eTag) {
                $serverProtocol = $this->getServerValue('SERVER_PROTOCOL');
                header($serverProtocol . ' 304 Not Modified', true, 304);

                return true;
            }
        }

        return false;
    }

    public function display()
    {
        if (!$this->file) {
            $this->generate();
        }

        if ($this->checkNoModified()) {
            return;
        }

        header('Content-Type: image/png');
        header('Content-Disposition: inline; filename="' . $this->filename . '.png"');
        header('Accept-Ranges: none');
        if ($this->eTag) {
            header('ETag: ' . $this->eTag);
        }
        if (null !== $this->expire) {
            header('Expires: ' . gmdate('D, d M Y H:i:s T', $this->expire));
        }
        imagepng($this->file);
    }

    public function save($savePath)
    {
        if (!$this->file) {
            $this->generate();
        }
        imagepng($this->file, $savePath);
    }

    /**
     * @return string
     */
    public function toBase64()
    {
        if (!$this->file) {
            $this->generate();
        }
        ob_start();
        imagepng($this->file);
        $buffer = ob_get_contents();
        if (ob_get_length()) {
            ob_end_clean();
        }

        return base64_encode($buffer);
    }

    public function destroy()
    {
        if ($this->file) {
            imagedestroy($this->file);
        }
        $this->file = null;
    }
}
