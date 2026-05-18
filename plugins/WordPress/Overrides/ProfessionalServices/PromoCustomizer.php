<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\ProfessionalServices;

use Piwik\Piwik;

class PromoCustomizer
{
    public function customizePromoHtml($promoContents)
    {
        $promoContents = $this->replaceUrlsHrefs($promoContents);
        $promoContents = $this->replaceUrlText($promoContents);
        return $promoContents;
    }

    private function replaceUrlsHrefs($promoContents)
    {
        $matchNumber = 0;

        $promoContents = preg_replace_callback(
            '/\\?module=Marketplace&action=overview#\\?showPlugin=(.+?)"/',
            function ($matches) use (&$matchNumber) {
                if ($matchNumber === 0) { // unlock button
                    if (!$this->isMwpMarketplaceInstalled()) {
                        $replacement = esc_attr( home_url( '/wp-admin/admin.php?page=matomo-marketplace&tab=install' ) ) . '"';
                    } else {
                        $replacement = esc_attr( 'https://plugins.matomo.org/' . $matches[1] . '?add-to-cart=ws&currency=EUR&wp=1' ) . '" target="_blank"';
                    }
                } else { // learn more link
                    $replacement = esc_attr( 'https://plugins.matomo.org/' . $matches[1] . '?currency=EUR&wp=1' ) . '" target="_blank"';
                }

                ++$matchNumber;
                return $replacement;
            },
            $promoContents
        );
        return $promoContents;
    }

    private function replaceUrlText($promoContents)
    {
        $promoContents = str_replace(
            Piwik::translate( 'ProfessionalServices_CTAStartFreeTrial' ),
            __( 'Unlock', 'matomo' ),
            $promoContents
        );
        return $promoContents;
    }

    protected function isMwpMarketplaceInstalled()
    {
        return is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME );
    }
}
