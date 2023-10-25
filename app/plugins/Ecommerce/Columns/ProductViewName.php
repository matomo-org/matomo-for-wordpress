<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Ecommerce\Columns;

use Piwik\Columns\Discriminator;
use Piwik\Columns\Join\ActionNameJoin;
use Piwik\Common;
use Piwik\Plugin\Dimension\ActionDimension;
use Piwik\Plugin\Manager;
use Piwik\Plugins\CustomVariables\Tracker\CustomVariablesRequestProcessor;
use Piwik\Tracker\Action;
use Piwik\Tracker\Request;
use Piwik\Tracker\TableLogAction;

class ProductViewName extends ActionDimension
{
    protected $type = self::TYPE_TEXT;
    protected $nameSingular = 'Ecommerce_ViewedProductName';
    protected $columnName = 'idaction_product_name';
    protected $segmentName = 'productViewName';
    protected $columnType = 'INT(10) UNSIGNED NULL';
    protected $category = 'Goals_Ecommerce';
    protected $sqlFilter = [TableLogAction::class, 'getOptimizedIdActionSqlMatch'];

    public function getDbColumnJoin()
    {
        return new ActionNameJoin();
    }

    public function getDbDiscriminator()
    {
        return new Discriminator('log_action', 'type', Action::TYPE_ECOMMERCE_ITEM_NAME);
    }

    public function onLookupAction(Request $request, Action $action)
    {
        if ($request->hasParam('_pkn')) {
            return Common::unsanitizeInputValue($request->getParam('_pkn'));
        }

        // fall back to custom variables (might happen if old logs are replayed)
        if (Manager::getInstance()->isPluginActivated('CustomVariables')) {
            $customVariables = CustomVariablesRequestProcessor::getCustomVariablesInPageScope($request);
            if (isset($customVariables['custom_var_k4']) && $customVariables['custom_var_k4'] === '_pkn') {
                return $customVariables['custom_var_v4'] ?? false;
            }
        }

        return parent::onLookupAction($request, $action);
    }

    public function getActionId()
    {
        return Action::TYPE_ECOMMERCE_ITEM_NAME;
    }
}
