<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\ProfessionalServices;

class PromoWidgetApplicable extends \Piwik\Plugins\ProfessionalServices\PromoWidgetApplicable
{
    public function check(string $pluginName, string $widgetName): bool
    {
        $enabledPlugins = [
            'Funnels',
            'HeatmapSessionRecording',
            'SearchEngineKeywordsPerformance',
            'UsersFlow',
        ];

        return in_array( $pluginName, $enabledPlugins, true );
    }
}
