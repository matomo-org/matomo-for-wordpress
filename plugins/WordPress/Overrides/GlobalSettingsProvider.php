<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides;

use Piwik\Application\Kernel\GlobalSettingsProvider as DefaultGlobalSettingsProvider;
use WpMatomo\Settings;

/**
 * A GlobalSettingsProvider that obtains the Matomo config data from a WordPress option (ie. the
 * database) instead of directly from the local INI files.
 */
class GlobalSettingsProvider extends DefaultGlobalSettingsProvider
{
    /**
     * @var \WpMatomo\Settings
     */
    private $settings;

    public function __construct($pathGlobal = null, $pathLocal = null, $pathCommon = null, Settings $settings = null)
    {
        $this->settings = $settings;

        parent::__construct($pathGlobal, $pathLocal, $pathCommon);
    }

    public function reload($pathGlobal = null, $pathLocal = null, $pathCommon = null)
    {
        parent::reload($pathGlobal, $pathLocal, $pathCommon);
        $this->loadConfigFromOptionOrSeed();
        $this->reapplyConfigModifier();
    }

    public function persistConfigOption()
    {
        // only persist the values that differ from the INI default settings (ie. the same data that
        // would be written to config.ini.php). storing the full merged config would freeze the
        // defaults shipped with core and overwrite them on every reload, so we keep just the diff.
        $diff = $this->computeUserConfigDiff();

        $settings = $this->getWpMatomoSettings();
        $settings->set_global_option(Settings::NETWORK_CONFIG_OPTIONS, $diff);
        $settings->save();
    }

    private function loadConfigFromOptionOrSeed()
    {
        // use WP option data if it exists, if not save current INI config data
        // to the option
        $stored = $this->getWpMatomoSettings()->get_global_option(Settings::NETWORK_CONFIG_OPTIONS);
        if (is_array($stored) && !empty($stored)) {
            $this->applyUserConfigDiff($stored);
        } else {
            $this->persistConfigOption();
        }
    }

    /**
     * Returns the config values that differ from the merged INI default settings, grouped by
     * section. This is the same set of values core would write to config.ini.php.
     *
     * @return array
     */
    private function computeUserConfigDiff()
    {
        $diff = [];
        foreach ($this->iniFileChain->getAll() as $sectionName => $section) {
            if (!is_array($section)) {
                continue;
            }

            $sectionDiff = $this->iniFileChain->arrayUnmerge($this->getDefaultSection($sectionName), $section);
            if (!empty($sectionDiff)) {
                $diff[$sectionName] = $sectionDiff;
            }
        }
        return $diff;
    }

    /**
     * Overlays the stored config diff on top of the freshly merged INI settings. Sections not present
     * in the diff keep their default values, so this never wipes the defaults shipped with core (and
     * legacy partial data written by older versions merges in harmlessly instead of replacing it).
     *
     * @param array $diff
     */
    private function applyUserConfigDiff($diff)
    {
        foreach ($diff as $sectionName => $section) {
            if (!is_array($section)) {
                continue;
            }

            $existing = $this->iniFileChain->get($sectionName);
            $existing = is_array($existing) ? $existing : [];
            $this->iniFileChain->set($sectionName, array_merge($existing, $section));
        }
    }

    /**
     * Re-applies the $GLOBALS['MATOMO_MODIFY_CONFIG_SETTINGS'] callback to the [Plugins] section.
     *
     * The list of activated plugins is computed dynamically on every request (eg. depending on which
     * premium plugins are active, whether Tag Manager is installed, incompatible plugins, ...).
     * IniFileChain::reload() already applies the callback, but loadConfigFromOptionOrSeed() overlays
     * the stored config on top of it, which would otherwise leave PluginList - and therefore the DI
     * container - looking at the plugin list frozen in the stored option. Re-applying it here keeps
     * the activated plugin list current for every consumer of the iniFileChain (PluginList and Config).
     */
    private function reapplyConfigModifier()
    {
        $merged = $this->iniFileChain->getAll();
        if (empty($merged)) {
            return;
        }

        $plugins = isset($merged['Plugins']['Plugins']) ? $merged['Plugins']['Plugins'] : [];
        if (!is_array($plugins)) {
            $plugins = [];
        }

        $modified = $this->getActualPluginsToLoad($plugins);
        $this->iniFileChain->set('Plugins', [ 'Plugins' => $modified ]);
    }

    private function getActualPluginsToLoad( $plugins ) {
        $pluginsToRemove = array('Marketplace', 'MultiSites', 'TwoFactorAuth', 'Widgetize', 'Feedback', 'ExamplePlugin', 'ExampleAPI', 'MobileAppMeasurable', 'CustomPiwikJs');
        foreach ($pluginsToRemove as $pluginToRemove) {
            // Marketplace => this is instead done in wordpress
            // MultiSites => doesn't really make sense since we have only one website per installation
            // TwoFactorAuth => not needed as login is being handled by WordPress
            // widgetize for now we don't want to allow widgetizing as it is based on the token_auth authentication
            // Monolog => we use our own logger
            // ProfessionalServices => we advertise in the WP plugin itself instead
            // feedback => we want to hide things like Need help in the admin etc
            // MobileAppMeasurable => for WP mobile apps are not a thing
            // custom variables we don't want to enable as we will deprecate them in Matomo 4 anyway => used to be disabled but we need to make sure the columns get installed otherwise matomo has issues... need to wait to matomo 4 to remove it
            $pos = array_search($pluginToRemove, $plugins);
            if ($pos !== false) {
                array_splice($plugins, $pos, 1);
            }
        }
        if (matomo_has_tag_manager()) {
            $plugins[] = 'TagManager';
        }
        $mustEnable = ['BulkTracking', 'CustomJsTracker'];
        foreach ($mustEnable as $enable) {
            if (!in_array($enable, $plugins)) {
                $plugins[] = $enable;
            }
        }
        if (!empty($GLOBALS['MATOMO_PLUGINS_ENABLED'])) {
            foreach ($GLOBALS['MATOMO_PLUGINS_ENABLED'] as $plugin) {
                if (!in_array($plugin, $plugins)) {
                    $plugins[] = $plugin;
                }
            }
        }
        if (!empty($GLOBALS['MATOMO_MARKETPLACE_PLUGINS'])) {
            matomo_filter_incompatible_plugins($plugins);
        }
        return $plugins;
    }

    /**
     * Returns the default values (from global.ini.php and common.ini.php) for the given section.
     *
     * @param string $sectionName
     * @return array
     */
    private function getDefaultSection($sectionName)
    {
        $global = $this->iniFileChain->getFrom($this->getPathGlobal(), $sectionName);
        $common = $this->iniFileChain->getFrom($this->getPathCommon(), $sectionName);

        $global = is_array($global) ? $global : [];
        $common = is_array($common) ? $common : [];

        return array_merge($global, $common);
    }

    private function getWpMatomoSettings()
    {
        if ( empty( $this->settings ) ) {
            $this->settings = \WpMatomo::$settings ?: new Settings();
        }
        return $this->settings;
    }
}
