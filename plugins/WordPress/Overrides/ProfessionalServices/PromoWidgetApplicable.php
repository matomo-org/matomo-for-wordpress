<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\ProfessionalServices;

use Piwik\Config;
use Piwik\Plugin\Manager;
use Piwik\Plugins\ProfessionalServices\PromoWidgetDismissal;

class PromoWidgetApplicable extends \Piwik\Plugins\ProfessionalServices\PromoWidgetApplicable
{
    private $promoWidgetDismissalAccessible;

    private $managerAccessible;

    public function __construct(Manager $manager, Config $config, PromoWidgetDismissal $promoWidgetDismissal)
    {
        parent::__construct($manager, $config, $promoWidgetDismissal);

        $this->promoWidgetDismissalAccessible = $promoWidgetDismissal;
        $this->managerAccessible = $manager;
    }

    public function check(string $pluginName, string $widgetName): bool
    {
        $disabledPlugins = [
            'AbTesting',
        ];

        if ( in_array( $pluginName, $disabledPlugins, true ) ) {
            return false;
        }

        if ($this->promoWidgetDismissalAccessible->isPromoWidgetDismissedForCurrentUser($widgetName)) {
            return \false;
        }

        if ( ! empty( $_REQUEST['force_promo'] ) ) {
            return true;
        }

        return ! $this->isMatomoPluginActivated($pluginName);
    }

    protected function isMatomoPluginActivated($pluginName)
    {
        return $this->managerAccessible->isPluginActivated($pluginName);
    }
}
