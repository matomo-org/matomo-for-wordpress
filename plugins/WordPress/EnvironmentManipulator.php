<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress;

use Piwik\Application\EnvironmentManipulator as EnvironmentManipulatorInterface;
use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Plugins\WordPress\Overrides\GlobalSettingsProvider as WordPressGlobalSettingsProvider;

/**
 * manipulates the Matomo environment so the WordPress specific GlobalSettingsProvider is used.
 */
class EnvironmentManipulator implements EnvironmentManipulatorInterface
{
    public function makeGlobalSettingsProvider(GlobalSettingsProvider $original)
    {
        return new WordPressGlobalSettingsProvider();
    }

    public function makePluginList(GlobalSettingsProvider $globalSettingsProvider)
    {
        // keep the default plugin list
        return null;
    }

    public function beforeContainerCreated()
    {
    }

    public function getExtraDefinitions()
    {
        return array();
    }

    public function onEnvironmentBootstrapped()
    {
    }

    public function getExtraEnvironments()
    {
        return array();
    }
}
