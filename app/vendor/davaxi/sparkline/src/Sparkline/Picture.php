<?php

namespace Davaxi\Sparkline;

/**
 * Class PictureTrait.
 */
class Picture
{
    const DOT_RADIUS_TO_WIDTH = 2;

    /**
     * @var \resource
     */
    protected $resource;

    /**
     * Picture constructor.
     *
     * @param $width
     * @param $height
     */
    public function __construct($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->resource = imagecreatetruecolor($width, $height);
    }

    /**
     * @param array $setColor
     *
     * @return int
     */
    protected function getBackground(array $setColor = [])
    {
        if ($setColor) {
            return imagecolorallocate(
                $this->resource,
                $setColor[0],
                $setColor[1],
                $setColor[2]
            );
        }

        return imagecolorallocatealpha(
            $this->resource,
            0,
            0,
            0,
            127
        );
    }

    /**
     * @param $lineColor
     *
     * @return int
     */
    public function getLineColor($lineColor)
    {
        return imagecolorallocate(
            $this->resource,
            $lineColor[0],
            $lineColor[1],
            $lineColor[2]
        );
    }

    /**
     * @param array $backgroundColor
     */
    public function applyBackground(array $backgroundColor)
    {
        imagesavealpha($this->resource, true);
        imagefill(
            $this->resource,
            0,
            0,
            $this->getBackground($backgroundColor)
        );
    }

    /**
     * @param $lineThickness
     */
    public function applyThickness($lineThickness)
    {
        imagesetthickness($this->resource, $lineThickness);
    }

    /**
     * @param array $polygon
     * @param array $fillColor
     * @param $count
     */
    public function applyPolygon(array $polygon, array $fillColor, $count)
    {
        if (!$fillColor) {
            return;
        }
        $fillColor = imagecolorallocate($this->resource, $fillColor[0], $fillColor[1], $fillColor[2]);
        imagefilledpolygon($this->resource, $polygon, $count + 2, $fillColor);
    }

    /**
     * @param array $line
     * @param array $lineColor
     */
    public function applyLine(array $line, array $lineColor)
    {
        $lineColor = $this->getLineColor($lineColor);
        foreach ($line as $coordinates) {
            list($pictureX1, $pictureY1, $pictureX2, $pictureY2) = $coordinates;
            imageline($this->resource, $pictureX1, $pictureY1, $pictureX2, $pictureY2, $lineColor);
        }
    }

    /**
     * @param $positionX
     * @param $positionY
     * @param $radius
     * @param array $color
     */
    public function applyDot($positionX, $positionY, $radius, $color)
    {
        if (!$color || !$radius) {
            return;
        }

        $minimumColor = imagecolorallocate(
            $this->resource,
            $color[0],
            $color[1],
            $color[2]
        );
        imagefilledellipse(
            $this->resource,
            $positionX,
            $positionY,
            $radius * static::DOT_RADIUS_TO_WIDTH,
            $radius * static::DOT_RADIUS_TO_WIDTH,
            $minimumColor
        );
    }

    /**
     * @param $width
     * @param $height
     *
     * @return resource
     */
    public function generate($width, $height)
    {
        $sparkline = imagecreatetruecolor($width, $height);
        imagealphablending($sparkline, false);
        imagecopyresampled(
            $sparkline,
            $this->resource,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->width,
            $this->height
        );
        imagesavealpha($sparkline, true);
        imagedestroy($this->resource);

        return $sparkline;
    }
}
