<?php

namespace Test\CpChart;

use Codeception\Test\Unit;
use CpChart\Image;
use Test\CpChart\UnitTester;

use const LABEL_POS_BOTTOM;
use const LABEL_POS_CENTER;
use const LABEL_POS_INSIDE;
use const LABEL_POS_LEFT;
use const LABEL_POS_RIGHT;
use const LABEL_POS_TOP;
use const ORIENTATION_VERTICAL;

class ProgressTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testChartRender()
    {
        $image = new Image(700, 250);
        $image->setShadow(
            true,
            ['X' => 1, 'Y' => 1, 'R' => 0, 'G' => 0, 'B' => 0, 'Alpha' => 20]
        );

        /* Left Red bar */
        $progressOptions = [
            'R' => 209, 'G' => 31, 'B' => 27, 'Surrounding' => 20,
            'BoxBorderR' => 0, 'BoxBorderG' => 0, 'BoxBorderB' => 0, 'BoxBackR' => 255,
            'BoxBackG' => 255, 'BoxBackB' => 255, 'RFade' => 206, 'GFade' => 133,
            'BFade' => 30, 'ShowLabel' => true
        ];
        $image->drawProgress(40, 60, 77, $progressOptions);
        $progressOptions = [
            'Width' => 165, 'R' => 209, 'G' => 125, 'B' => 27, 'Surrounding' => 20,
            'BoxBorderR' => 0, 'BoxBorderG' => 0, 'BoxBorderB' => 0, 'BoxBackR' => 255,
            'BoxBackG' => 255, 'BoxBackB' => 255, 'NoAngle' => true, 'ShowLabel' => true,
            'LabelPos' => LABEL_POS_RIGHT
        ];
        $image->drawProgress(40, 100, 50, $progressOptions);
        $progressOptions = [
            'Width' => 165, 'R' => 209, 'G' => 198, 'B' => 27, 'Surrounding' => 20,
            'BoxBorderR' => 0, 'BoxBorderG' => 0, 'BoxBorderB' => 0, 'BoxBackR' => 255,
            'BoxBackG' => 255, 'BoxBackB' => 255, 'ShowLabel' => true, 'LabelPos' => LABEL_POS_LEFT
        ];
        $image->drawProgress(75, 140, 25, $progressOptions);
        $progressOptions = ['Width' => 400, 'R' => 134, 'G' => 209, 'B' => 27, 'Surrounding' => 20,
            'BoxBorderR' => 0, 'BoxBorderG' => 0, 'BoxBorderB' => 0, 'BoxBackR' => 255,
            'BoxBackG' => 255, 'BoxBackB' => 255, 'RFade' => 206, 'GFade' => 133,
            'BFade' => 30, 'ShowLabel' => true, 'LabelPos' => LABEL_POS_CENTER];
        $image->drawProgress(40, 180, 80, $progressOptions);
        $progressOptions = [
            'Width' => 20, 'Height' => 150, 'R' => 209, 'G' => 31,
            'B' => 27, 'Surrounding' => 20, 'BoxBorderR' => 0, 'BoxBorderG' => 0,
            'BoxBorderB' => 0, 'BoxBackR' => 255, 'BoxBackG' => 255, 'BoxBackB' => 255,
            'RFade' => 206, 'GFade' => 133, 'BFade' => 30, 'ShowLabel' => true, 'Orientation' => ORIENTATION_VERTICAL,
            'LabelPos' => LABEL_POS_BOTTOM
        ];
        $image->drawProgress(500, 200, 77, $progressOptions);
        $progressOptions = [
            'Width' => 20, 'Height' => 150, 'R' => 209, 'G' => 125,
            'B' => 27, 'Surrounding' => 20, 'BoxBorderR' => 0, 'BoxBorderG' => 0,
            'BoxBorderB' => 0, 'BoxBackR' => 255, 'BoxBackG' => 255, 'BoxBackB' => 255,
            'NoAngle' => true, 'ShowLabel' => true, 'Orientation' => ORIENTATION_VERTICAL,
            'LabelPos' => LABEL_POS_TOP
        ];
        $image->drawProgress(540, 200, 50, $progressOptions);
        $progressOptions = [
            'Width' => 20, 'Height' => 150, 'R' => 209, 'G' => 198,
            'B' => 27, 'Surrounding' => 20, 'BoxBorderR' => 0, 'BoxBorderG' => 0,
            'BoxBorderB' => 0, 'BoxBackR' => 255, 'BoxBackG' => 255, 'BoxBackB' => 255,
            'ShowLabel' => true, 'Orientation' => ORIENTATION_VERTICAL, 'LabelPos' => LABEL_POS_INSIDE
        ];
        $image->drawProgress(580, 200, 25, $progressOptions);
        $progressOptions = [
            'Width' => 20, 'Height' => 150, 'R' => 134, 'G' => 209,
            'B' => 27, 'Surrounding' => 20, 'BoxBorderR' => 0, 'BoxBorderG' => 0,
            'BoxBorderB' => 0, 'BoxBackR' => 255, 'BoxBackG' => 255, 'BoxBackB' => 255,
            'RFade' => 206, 'GFade' => 133, 'BFade' => 30, 'ShowLabel' => true,
            'Orientation' => ORIENTATION_VERTICAL, 'LabelPos' => LABEL_POS_CENTER
        ];
        $image->drawProgress(620, 200, 80, $progressOptions);

        $filename = $this->tester->getOutputPathForChart('drawProgress.png');
        $image->render($filename);
        $image->stroke();

        $this->tester->seeFileFound($filename);
    }
}
