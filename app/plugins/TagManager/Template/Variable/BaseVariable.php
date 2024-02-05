<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TagManager\Template\Variable;

use Piwik\Plugins\TagManager\Context\WebContext;
use Piwik\Plugins\TagManager\Template\BaseTemplate;
/**
 * @api
 */
abstract class BaseVariable extends BaseTemplate
{
    const CATEGORY_PAGE_VARIABLES = 'TagManager_CategoryPageVariables';
    const CATEGORY_VISIBILITY = 'TagManager_CategoryVisibility';
    const CATEGORY_CLICKS = 'TagManager_CategoryClicks';
    const CATEGORY_CONTAINER_INFO = 'TagManager_CategoryContainerInfo';
    const CATEGORY_HISTORY = 'TagManager_CategoryHistory';
    const CATEGORY_ERRORS = 'TagManager_CategoryErrors';
    const CATEGORY_SCROLLS = 'TagManager_CategoryScrolls';
    const CATEGORY_FORMS = 'TagManager_CategoryForms';
    const CATEGORY_DATE = 'TagManager_CategoryDate';
    const CATEGORY_PERFORMANCE = 'TagManager_CategoryPerformance';
    const CATEGORY_UTILITIES = 'TagManager_CategoryUtilities';
    const CATEGORY_DEVICE = 'TagManager_CategoryDevice';
    const CATEGORY_SEO = 'TagManager_CategorySEO';
    const CATEGORY_OTHERS = 'General_Others';
    protected $templateType = 'Variable';
    /**
     * @inheritdoc
     */
    public function getCategory()
    {
        return self::CATEGORY_OTHERS;
    }
    /**
     * @inheritdoc
     */
    public function getSupportedContexts()
    {
        return array(WebContext::ID);
    }
    /**
     * Defines whether this variable is a preconfigured variable which cannot be configured and is ready to use.
     * @return bool
     */
    public function isPreConfigured()
    {
        return false;
    }
}
