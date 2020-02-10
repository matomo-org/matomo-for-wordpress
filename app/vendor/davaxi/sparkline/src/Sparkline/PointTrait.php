<?php

namespace Davaxi\Sparkline;

/**
 * Trait PointTrait.
 */
trait PointTrait
{
    /**
     * @var array
     */
    protected $points = [];

    /**
     * @param $index
     * @param $dotRadius
     * @param $colorHex
     * @param int $seriesIndex
     */
    public function addPoint($index, $dotRadius, $colorHex, $seriesIndex = 0)
    {
        $mapping = $this->getPointIndexMapping($seriesIndex);
        if (array_key_exists($index, $mapping)) {
            $index = $mapping[$index];
            if ($index < 0) {
                return;
            }
        }
        $this->checkPointIndex($index, $seriesIndex);
        $this->points[] = [
            'series' => $seriesIndex,
            'index' => $index,
            'radius' => $dotRadius,
            'color' => $this->colorHexToRGB($colorHex),
        ];
    }

    /**
     * @param int $seriesIndex
     * @return array
     */
    protected function getPointIndexMapping($seriesIndex = 0)
    {
        $count = $this->getCount($seriesIndex);
        list($minIndex, $min, $maxIndex, $max) = $this->getExtremeValues($seriesIndex);

        $mapping = [];
        $mapping['first'] = $count > 1 ? 0 : -1;
        $mapping['last'] = $count > 1 ? $count - 1 : -1;
        $mapping['minimum'] = $min !== $max ? $minIndex : -1;
        $mapping['maximum'] = $min !== $max ? $maxIndex : -1;

        return $mapping;
    }

    /**
     * @param int $seriesIndex
     * @param $index
     */
    protected function checkPointIndex($index, $seriesIndex)
    {
        $count = $this->getCount($seriesIndex);
        if (!is_numeric($index)) {
            throw new \InvalidArgumentException('Invalid index : ' . $index);
        }
        if ($index < 0 || $index >= $count) {
            throw new \InvalidArgumentException('Index out of range [0-' . ($count - 1) . '] : ' . $index);
        }
    }
}
