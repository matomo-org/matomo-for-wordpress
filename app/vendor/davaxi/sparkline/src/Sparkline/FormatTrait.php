<?php

namespace Davaxi\Sparkline;

/**
 * Trait FormatTrait.
 */
trait FormatTrait
{
    /**
     * @var int
     *          Recommended: 50 < 800
     */
    protected $width = 80;

    /**
     * @var int
     *          Recommended: 20 < 800
     */
    protected $height = 20;

    /**
     * @var int
     */
    protected $ratioComputing = 4;

    /**
     * @var array
     */
    protected $padding = [
        'top' => 0,
        'right' => 0,
        'bottom' => 0,
        'left' => 0,
    ];

    /**
     * @param string $format (Width x Height)
     */
    public function setFormat($format)
    {
        $values = explode('x', $format);
        if (count($values) !== static::FORMAT_DIMENSION) {
            throw new \InvalidArgumentException('Invalid format params. Expected string Width x Height');
        }
        $this->setWidth($values[0]);
        $this->setHeight($values[1]);
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->width = (int)$width;
    }

    /**
     * @param int $height
     */
    public function setHeight($height)
    {
        $this->height = (int)$height;
    }

    /**
     * Set padding : format top right bottom left
     * ex: 0 10 0 10.
     *
     * @param string $padding
     */
    public function setPadding($padding)
    {
        list($top, $right, $bottom, $left) = $this->paddingStringToArray($padding);
        $this->padding['top'] = $top;
        $this->padding['right'] = $right;
        $this->padding['bottom'] = $bottom;
        $this->padding['left'] = $left;
    }

    /**
     * @return int
     */
    protected function getNormalizedHeight()
    {
        return $this->height * $this->ratioComputing;
    }

    /**
     * @return int
     */
    protected function getInnerHeight()
    {
        return $this->height - $this->padding['top'] - $this->padding['bottom'];
    }

    /**
     * @return array
     */
    protected function getNormalizedPadding()
    {
        return array_map(
            function ($value) {
                return $value * $this->ratioComputing;
            },
            $this->padding
        );
    }

    /**
     * @return int
     */
    protected function getInnerNormalizedHeight()
    {
        return $this->getInnerHeight() * $this->ratioComputing;
    }

    /**
     * @return int
     */
    protected function getNormalizedWidth()
    {
        return $this->width * $this->ratioComputing;
    }

    /**
     * @return int
     */
    protected function getInnerWidth()
    {
        return $this->width - ($this->padding['left'] + $this->padding['right']);
    }

    /**
     * @return int
     */
    protected function getInnerNormalizedWidth()
    {
        return $this->getInnerWidth() * $this->ratioComputing;
    }

    /**
     * @return array
     */
    protected function getNormalizedSize()
    {
        return [
            $this->getNormalizedWidth(),
            $this->getNormalizedHeight(),
        ];
    }

    /**
     * @return array
     */
    protected function getInnerNormalizedSize()
    {
        return [
            $this->getInnerNormalizedWidth(),
            $this->getInnerNormalizedHeight(),
        ];
    }

    /**
     * @param $count
     *
     * @return float|int
     */
    protected function getStepWidth($count)
    {
        $innerWidth = $this->getInnerNormalizedWidth();

        return $innerWidth / ($count - 1);
    }

    /**
     * @param array $data
     * @param $height
     *
     * @return array
     */
    protected function getDataForChartElements(array $data, $height)
    {
        $max = $this->getMaxValueAcrossSeries();
        $minHeight = 1 * $this->ratioComputing;
        $maxHeight = $height - $minHeight;
        foreach ($data as $i => $value) {
            $value = (int)$value;
            if ($value <= 0) {
                $value = 0;
            }
            if ($value > 0) {
                $value = round(($value / $max) * $height);
            }
            $data[$i] = max($minHeight, min($value, $maxHeight));
        }

        return $data;
    }

    /**
     * @param array $data
     * @param int $count count of steps in sparkline image (does not have to == count($data))
     * @return array
     */
    protected function getChartElements(array $data, $count)
    {
        $step = $this->getStepWidth($count);
        $height = $this->getInnerNormalizedHeight();
        $normalizedPadding = $this->getNormalizedPadding();
        $data = $this->getDataForChartElements($data, $height);

        $pictureX1 = $pictureX2 = $normalizedPadding['left'];
        $pictureY1 = $normalizedPadding['top'] + $height - $data[0];

        $polygon = [];
        $line = [];

        // Initialize
        $polygon[] = $normalizedPadding['left'];
        $polygon[] = $normalizedPadding['top'] + $height;
        // First element
        $polygon[] = $pictureX1;
        $polygon[] = $pictureY1;
        for ($i = 1; $i < count($data); ++$i) {
            $pictureX2 = $pictureX1 + $step;
            $pictureY2 = $normalizedPadding['top'] + $height - $data[$i];

            $line[] = [$pictureX1, $pictureY1, $pictureX2, $pictureY2];

            $polygon[] = $pictureX2;
            $polygon[] = $pictureY2;

            $pictureX1 = $pictureX2;
            $pictureY1 = $pictureY2;
        }
        // Last
        $polygon[] = $pictureX2;
        $polygon[] = $normalizedPadding['top'] + $height;

        return [$polygon, $line];
    }
}
