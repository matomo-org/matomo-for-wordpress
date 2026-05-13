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
        $promoContents = preg_replace_callback(
            '/\\?module=Marketplace&action=overview#\\?showPlugin=(.+?)"/',
            function ($matches) {
                $pluginKebabCase = $this->asKebabCase($matches[1]);
                return 'https://matomo.org/get/matomo-for-wordpress-full-reporting-' . $pluginKebabCase . '/" target="_blank"';
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

    private function asKebabCase($pluginName)
    {
        return strtolower( substr( $pluginName, 0, 1 ) )
            . strtolower( preg_replace( '/[A-Z]/', '-$0', $pluginName ) );
    }
}
