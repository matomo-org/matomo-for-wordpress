<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TagManager\Template\Variable\PreConfigured;


class VisibleElementClassesVariable extends BaseDataLayerVariable
{
    public function getCategory()
    {
        return self::CATEGORY_VISIBILITY;
    }

    protected function getDataLayerVariableName()
    {
        return 'mtm.elementVisibilityClasses';
    }

}
