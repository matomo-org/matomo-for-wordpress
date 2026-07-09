<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress;

use Piwik\Application\Environment;
use Piwik\Plugins\WordPress\Overrides\GlobalSettingsProvider as WordPressGlobalSettingsProvider;

/**
 * A Matomo Environment that uses the WordPress specific GlobalSettingsProvider.
 *
 * Core Matomo entry points are patched to instantiate this class instead of the
 * base Environment (see patches/prefixed/wordpress-environment.diff); the WordPress
 * plugin's own code references it directly.
 */
class WordPressEnvironment extends Environment
{
    protected function getGlobalSettings()
    {
        return new WordPressGlobalSettingsProvider();
    }
}
