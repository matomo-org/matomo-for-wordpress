# Drawing a contour chart

[Reference](http://wiki.pchart.net/doc.surface.drawcontour.html)

```php
require '/path/to/your/vendor/autoload.php';

use CpChart\Chart\Surface;
use CpChart\Data;
use CpChart\Image;

/* Create the Image object */
$image = new Image(400, 400);

/* Create a solid background */
$image->drawFilledRectangle(0, 0, 400, 400, [
    "R" => 179,
    "G" => 217,
    "B" => 91,
    "Dash" => 1,
    "DashR" => 199,
    "DashG" => 237,
    "DashB" => 111
]);

/* Do a gradient overlay */
$image->drawGradientArea(0, 0, 400, 400, DIRECTION_VERTICAL, [
    "StartR" => 194,
    "StartG" => 231,
    "StartB" => 44,
    "EndR" => 43,
    "EndG" => 107,
    "EndB" => 58,
    "Alpha" => 50
]);
$image->drawGradientArea(0, 0, 400, 20, DIRECTION_VERTICAL, [
    "StartR" => 0,
    "StartG" => 0,
    "StartB" => 0,
    "EndR" => 50,
    "EndG" => 50,
    "EndB" => 50,
    "Alpha" => 100
]);

/* Add a border to the picture */
$image->drawRectangle(0, 0, 399, 399, ["R" => 0, "G" => 0, "B" => 0]);

/* Write the picture title */
$image->setFontProperties(["FontName" => "Silkscreen.ttf", "FontSize" => 6]);
$image->drawText(10, 13, "pSurface() :: 2D surface charts", ["R" => 255, "G" => 255, "B" => 255]);

/* Define the charting area */
$image->setGraphArea(20, 40, 380, 380);
$image->drawFilledRectangle(20, 40, 380, 380, [
    "R" => 255,
    "G" => 255,
    "B" => 255,
    "Surrounding" => -200,
    "Alpha" => 20
]);

$image->setShadow(true, ["X" => 1, "Y" => 1]);

/* Create the surface object */
$surfaceChart = new Surface($image);

/* Set the grid size */
$surfaceChart->setGrid(20, 20);

/* Write the axis labels */
$image->setFontProperties(["FontName" => "pf_arma_five.ttf", "FontSize" => 6]);
$surfaceChart->writeXLabels(["Position" => LABEL_POSITION_BOTTOM]);
$surfaceChart->writeYLabels();

/* Add random values */
for ($i = 0; $i <= 50; $i++) {
    $surfaceChart->addPoint(rand(0, 20), rand(0, 20), rand(0, 100));
}

/* Compute the missing points */
$surfaceChart->computeMissing();

/* Draw the surface chart */
$surfaceChart->drawSurface(["Border" => true, "Surrounding" => 40]);

/* Draw the contour with a threshold of 50 */
$surfaceChart->drawContour(50, ["R" => 0, "G" => 0, "B" => 0]);

/* Render the picture (choose the best way) */
$image->autoOutput("example.surface.png");
```
