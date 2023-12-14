<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\ProfessionalServices;

use Piwik\Config;
use Piwik\Plugin\Manager;
use Piwik\ProfessionalServices\Advertising;

class PromoWidgetApplicable
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var PromoWidgetDismissal
     */
    private $promoWidgetDismissal;

    public function __construct(Manager $manager, Config $config, PromoWidgetDismissal $promoWidgetDismissal)
    {
        $this->manager = $manager;
        $this->config = $config;
        $this->promoWidgetDismissal = $promoWidgetDismissal;
    }

    public function check(string $pluginName, string $widgetName): bool
    {
        if (Advertising::isAdsEnabledInConfig($this->config->General) === false) {
            return false;
        }

        if ($this->manager->isPluginActivated('Marketplace') === false) {
            return false;
        }

        if ((bool) $this->config->General['enable_internet_features'] === false) {
            return false;
        }

        if ($this->promoWidgetDismissal->isPromoWidgetDismissedForCurrentUser($widgetName)) {
            return false;
        }

        return $this->manager->isPluginActivated($pluginName) === false;
    }
}
