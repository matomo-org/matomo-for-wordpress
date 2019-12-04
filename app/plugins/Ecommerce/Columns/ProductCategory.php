<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Ecommerce\Columns;

use Piwik\Columns\Dimension;
use Piwik\Piwik;
use Piwik\Plugin\Segment;

class ProductCategory extends Dimension
{
    const PRODUCT_CATEGORY_COUNT = 5;

    protected $type = self::TYPE_TEXT;
    protected $category = 'Goals_Ecommerce';
    protected $nameSingular = 'Goals_ProductCategory';

    protected function configureSegments()
    {
        $individualProductCategorySegments = $this->getProductCategorySegments(self::PRODUCT_CATEGORY_COUNT);

        // add individual productCategoryN segments for use as a union (these segments are not available through the UI/API)
        foreach ($individualProductCategorySegments as $i => $productCategoryName) {
            $productCategoryColumnName = 'idaction_category';
            if ($i > 0) {
                $productCategoryColumnName .= $i + 1;
            }

            $segment = new Segment();
            $segment->setCategory($this->category);
            $segment->setType('dimension');
            $segment->setName($this->getName() . ' ' . ($i + 1));
            $segment->setSegment($productCategoryName);
            $segment->setSqlFilter('\\Piwik\\Tracker\\TableLogAction::getIdActionFromSegment');
            $segment->setSqlSegment('log_conversion_item.' . $productCategoryColumnName);
            $segment->setIsInternal(true);
            $this->addSegment($segment);
        }

        // add a union of these individual columns as productCategory
        $segment = new Segment();
        $segment->setCategory($this->category);
        $segment->setType('dimension');
        $segment->setSegment('productCategory');
        $segment->setName($this->getName());
        $segment->setUnionOfSegments($individualProductCategorySegments);
        $this->addSegment($segment);
    }

    private function getProductCategorySegments($categoryCount)
    {
        $result = [];
        for ($i = 0; $i < $categoryCount; ++$i) {
            $segmentName = 'productCategory' . ($i + 1);
            $result[] = $segmentName;
        }
        return $result;
    }
}