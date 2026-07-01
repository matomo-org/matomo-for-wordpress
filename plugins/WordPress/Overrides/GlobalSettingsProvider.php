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
 * A GlobalSettingsProvider that keeps a backup copy of the Matomo config data in a WordPress option
 * (ie. the database).
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
        $this->syncOrRestoreConfigBackup();
        $this->detectExtraPluginsToLoad();
    }

    public function persistConfigOption()
    {
        // only persist the values that differ from the INI default settings (ie, what would go
        // in config.ini.php)
        $diff = $this->computeUserConfigDiff();

        $settings = $this->getWpMatomoSettings();
        $settings->set_global_option(Settings::NETWORK_CONFIG_OPTIONS, $diff);
        $settings->save();
    }

    private function syncOrRestoreConfigBackup()
    {
        if ($this->localConfigFileExists()) {
            // if local file exists, backup its contents to the WP option
            $this->persistConfigOption();
        } else {
            // if local file does not exist (for example, deleted by hosting provider or another plugin),
            // restore the contents from the backup
            $this->restoreConfigFromBackup();
        }
    }

    private function localConfigFileExists()
    {
        $path = $this->getPathLocal();
        return !empty($path) && is_readable($path) && filesize($path) > 0;
    }

    private function restoreConfigFromBackup()
    {
        $backup = $this->getWpMatomoSettings()->get_global_option(Settings::NETWORK_CONFIG_OPTIONS);
        if (!is_array($backup) || empty($backup)) {
            // nothing to restore, eg. a fresh install before config.ini.php has been created
            return;
        }

        $this->applyUserConfigDiff($backup);
        $this->writeLocalConfigFile();
    }

    private function writeLocalConfigFile()
    {
        $path = $this->getPathLocal();
        if (empty($path)) {
            return;
        }

        $header  = "; <?php exit; ?> DO NOT REMOVE THIS LINE\n";
        $header .= "; file automatically generated or modified by Matomo; you can manually override the default values in global.ini.php by redefining them in this file.\n";

        $content = $this->iniFileChain->dumpChanges($header);
        if (empty($content)) {
            return;
        }

        @file_put_contents($path, $content, LOCK_EX);
    }

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

    private function detectExtraPluginsToLoad()
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

    private function getActualPluginsToLoad( $plugins ) { // TODO: cache result of this?
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
