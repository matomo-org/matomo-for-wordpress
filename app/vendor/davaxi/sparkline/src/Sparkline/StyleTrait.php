<?php

namespace Davaxi\Sparkline;

use InvalidArgumentException;
trait StyleTrait
{
    /**
     * RGB
     * Default: #ffffff
     * @var int[]
     */
    protected $backgroundColor = [255, 255, 255];
    /**
     * RGB
     * Default: #1388db
     * @var int[][]
     */
    protected $lineColor = [[19, 136, 219]];
    /**
     * RGB
     * Default: #e6f2fa
     * @var int[][]
     */
    protected $fillColor = [[230, 242, 250]];
    /**
     * Default: 1.75px
     * @var float
     */
    protected $lineThickness = 1.75;
    /**
     * Per-series line dash patterns: [] = solid, [on, off] = dashed
     * @var int[][]
     */
    protected $lineStyle = [];
    /**
     * Series indexes for which the line is deactivated.
     * @var bool[]
     */
    protected $lineDeactivated = [];
    /**
     * Set background to transparent.
     */
    public function deactivateBackgroundColor()
    {
        $this->backgroundColor = [];
    }
    /**
     * @param string $color (hexadecimal)
     */
    public function setBackgroundColorHex(string $color)
    {
        list($red, $green, $blue) = $this->colorHexToRGB($color);
        $this->setBackgroundColorRGB($red, $green, $blue);
    }
    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     */
    public function setBackgroundColorRGB(int $red, int $green, int $blue)
    {
        $this->backgroundColor = [$red, $green, $blue];
    }
    /**
     * @param string $color (hexadecimal)
     * @param int $seriesIndex
     */
    public function setLineColorHex(string $color, int $seriesIndex = 0)
    {
        list($red, $green, $blue) = $this->colorHexToRGB($color);
        $this->setLineColorRGB($red, $green, $blue, $seriesIndex);
    }
    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $seriesIndex
     */
    public function setLineColorRGB(int $red, int $green, int $blue, int $seriesIndex = 0)
    {
        $this->lineColor[$seriesIndex] = [$red, $green, $blue];
    }
    /**
     * @param float $thickness (in px)
     */
    public function setLineThickness(float $thickness)
    {
        $this->lineThickness = $thickness;
    }
    /**
     * @param string $style 'solid' or 'dashed'
     * @param int $seriesIndex
     */
    public function setLineStyle(string $style, int $seriesIndex = 0)
    {
        $patterns = ['solid' => [], 'dashed' => [1, 1]];
        if (!array_key_exists($style, $patterns)) {
            throw new InvalidArgumentException('Invalid line style: ' . $style . '. Must be solid or dashed.');
        }
        $this->lineStyle[$seriesIndex] = $patterns[$style];
    }
    /**
     * @param int $seriesIndex
     * @return array [onPx, offPx] or [] for solid
     */
    public function getLineStyle(int $seriesIndex = 0) : array
    {
        return $this->lineStyle[$seriesIndex] ?? [];
    }
    /**
     * @param int $seriesIndex
     * @return array
     */
    public function getLineColor(int $seriesIndex = 0) : array
    {
        return $this->lineColor[$seriesIndex] ?? $this->lineColor[0];
    }
    /**
     * @param int $seriesIndex
     */
    public function deactivateLine(int $seriesIndex = 0)
    {
        $this->lineDeactivated[$seriesIndex] = \true;
    }
    /**
     * @param int $seriesIndex
     * @return bool
     */
    public function isLineDeactivated(int $seriesIndex = 0) : bool
    {
        return $this->lineDeactivated[$seriesIndex] ?? \false;
    }
    /**
     * Set fill color to transparent.
     * @param int $seriesIndex
     */
    public function deactivateFillColor(int $seriesIndex = 0)
    {
        unset($this->fillColor[$seriesIndex]);
    }
    /**
     * Set all fill color to transparent.
     * @return void
     */
    public function deactivateAllFillColor()
    {
        $this->fillColor = [];
    }
    /**
     * @param string $color (hexadecimal)
     * @param int $seriesIndex
     */
    public function setFillColorHex(string $color, int $seriesIndex = 0)
    {
        list($red, $green, $blue) = $this->colorHexToRGB($color);
        $this->setFillColorRGB($red, $green, $blue, $seriesIndex);
    }
    /**
     * @param int $red
     * @param int $green
     * @param int $blue
     * @param int $seriesIndex
     */
    public function setFillColorRGB(int $red, int $green, int $blue, int $seriesIndex = 0)
    {
        $this->fillColor[$seriesIndex] = [$red, $green, $blue];
    }
    /**
     * @param int $seriesIndex
     * @return array
     */
    public function getFillColor(int $seriesIndex = 0) : array
    {
        if (!isset($this->fillColor[$seriesIndex])) {
            return [];
        }
        return $this->fillColor[$seriesIndex];
    }
    /**
     * @param string $color (hexadecimal)
     * @exceptions \InvalidArgumentException
     *
     * @return array (r,g,b)
     */
    protected function colorHexToRGB(string $color) : array
    {
        if (!self::checkColorHex($color)) {
            throw new InvalidArgumentException('Invalid hexadecimal value ' . $color);
        }
        $color = mb_strtolower($color);
        $color = ltrim($color, '#');
        if (mb_strlen($color) === static::HEXADECIMAL_ALIAS_LENGTH) {
            $color = $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2];
        }
        $color = hexdec($color);
        return [
            0xff & $color >> 0x10,
            // Red
            0xff & $color >> 0x8,
            // Green
            0xff & $color,
        ];
    }
    /**
     * Formats:
     *      all
     *      vertical horizontal
     *      top horizontal bottom
     *      top right bottom left.
     *
     * @param string $padding
     *
     * @return array
     */
    protected function paddingStringToArray(string $padding) : array
    {
        $parts = explode(' ', $padding);
        switch (count($parts)) {
            case static::CSS_PADDING_ONE:
                $value = (float) $parts[0];
                return [$value, $value, $value, $value];
            case static::CSS_PADDING_TWO:
                $verticalValue = (float) $parts[0];
                $horizontalValue = (float) $parts[1];
                return [$verticalValue, $horizontalValue, $verticalValue, $horizontalValue];
            case static::CSS_PADDING_THREE:
                $parts[3] = $parts[1];
                return $parts;
            case static::CSS_PADDING:
                return $parts;
            default:
                throw new InvalidArgumentException('Invalid padding format');
        }
    }
    /**
     * @param $color
     *
     * @return int
     */
    protected static function checkColorHex($color) : int
    {
        return preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $color);
    }
}
