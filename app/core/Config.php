<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik;

use Exception;
use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Config\Cache;
use Piwik\Config\IniFileChain;
use Piwik\Container\StaticContainer;
use Piwik\Exception\MissingFilePermissionException;
use Piwik\ProfessionalServices\Advertising;

/**
 * Singleton that provides read & write access to Piwik's INI configuration.
 *
 * This class reads and writes to the `config/config.ini.php` file. If config
 * options are missing from that file, this class will look for their default
 * values in `config/global.ini.php`.
 *
 * ### Examples
 *
 * **Getting a value:**
 *
 *     // read the minimum_memory_limit option under the [General] section
 *     $minValue = Config::getInstance()->General['minimum_memory_limit'];
 *
 * **Setting a value:**
 *
 *     // set the minimum_memory_limit option
 *     Config::getInstance()->General['minimum_memory_limit'] = 256;
 *     Config::getInstance()->forceSave();
 *
 * **Setting an entire section:**
 *
 *     Config::getInstance()->MySection = array('myoption' => 1);
 *     Config::getInstance()->forceSave();
 */
class Config
{
    const DEFAULT_LOCAL_CONFIG_PATH = '/config/config.ini.php';
    const DEFAULT_COMMON_CONFIG_PATH = '/config/common.config.ini.php';
    const DEFAULT_GLOBAL_CONFIG_PATH = '/config/global.ini.php';

    /**
     * @var boolean
     */
    protected $doNotWriteConfigInTests = false;

    /**
     * @var GlobalSettingsProvider
     */
    protected $settings;

    /**
     * @return Config
     */
    public static function getInstance()
    {
        return StaticContainer::get('Piwik\Config');
    }

    public function __construct(GlobalSettingsProvider $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns the path to the local config file used by this instance.
     *
     * @return string
     */
    public function getLocalPath()
    {
        return $this->settings->getPathLocal();
    }

    /**
     * Returns the path to the global config file used by this instance.
     *
     * @return string
     */
    public function getGlobalPath()
    {
        return $this->settings->getPathGlobal();
    }

    /**
     * Returns the path to the common config file used by this instance.
     *
     * @return string
     */
    public function getCommonPath()
    {
        return $this->settings->getPathCommon();
    }

    /**
     * Returns absolute path to the global configuration file
     *
     * @return string
     */
    public static function getGlobalConfigPath()
    {
        return PIWIK_DOCUMENT_ROOT . self::DEFAULT_GLOBAL_CONFIG_PATH;
    }

    /**
     * Returns absolute path to the common configuration file.
     *
     * @return string
     */
    public static function getCommonConfigPath()
    {
        return PIWIK_USER_PATH . self::DEFAULT_COMMON_CONFIG_PATH;
    }

    /**
     * Returns default absolute path to the local configuration file.
     *
     * @return string
     */
    public static function getDefaultLocalConfigPath()
    {
        return PIWIK_USER_PATH . self::DEFAULT_LOCAL_CONFIG_PATH;
    }

    /**
     * Returns absolute path to the local configuration file
     *
     * @return string
     */
    public static function getLocalConfigPath()
    {
        if (!empty($GLOBALS['CONFIG_INI_PATH_RESOLVER']) && is_callable($GLOBALS['CONFIG_INI_PATH_RESOLVER'])) {
            return call_user_func($GLOBALS['CONFIG_INI_PATH_RESOLVER']);
        }
        
        $path = self::getByDomainConfigPath();
        if ($path) {
            return $path;
        }
        return self::getDefaultLocalConfigPath();
    }

    private static function getLocalConfigInfoForHostname($hostname)
    {
        if (!$hostname) {
            return array();
        }

        // Remove any port number to get actual hostname
        $hostname = Url::getHostSanitized($hostname);
        $standardConfigName = 'config.ini.php';
        $perHostFilename  = $hostname . '.' . $standardConfigName;
        $pathDomainConfig = PIWIK_USER_PATH . '/config/' . $perHostFilename;
        $pathDomainMiscUser = PIWIK_USER_PATH . '/misc/user/' . $hostname . '/' . $standardConfigName;

        $locations = array(
            array('file' => $perHostFilename, 'path' => $pathDomainConfig),
            array('file' => $standardConfigName, 'path' => $pathDomainMiscUser)
        );

        return $locations;
    }

    public function getConfigHostnameIfSet()
    {
        if ($this->getByDomainConfigPath() === false) {
            return false;
        }
        return $this->getHostname();
    }

    public function getClientSideOptions()
    {
        $general = $this->General;

        return array(
            'action_url_category_delimiter' => $general['action_url_category_delimiter'],
            'action_title_category_delimiter' => $general['action_title_category_delimiter'],
            'autocomplete_min_sites' => $general['autocomplete_min_sites'],
            'datatable_export_range_as_day' => $general['datatable_export_range_as_day'],
            'datatable_row_limits' => $this->getDatatableRowLimits(),
            'are_ads_enabled' => Advertising::isAdsEnabledInConfig($general)
        );
    }

    /**
     * @param $general
     * @return mixed
     */
    private function getDatatableRowLimits()
    {
        $limits = $this->General['datatable_row_limits'];
        $limits = explode(",", $limits);
        $limits = array_map('trim', $limits);
        return $limits;
    }

    public static function getByDomainConfigPath()
    {
        $host       = self::getHostname();
        $hostConfigs = self::getLocalConfigInfoForHostname($host);

        foreach ($hostConfigs as $hostConfig) {
            if (Filesystem::isValidFilename($hostConfig['file'])
                && file_exists($hostConfig['path'])
            ) {
                return $hostConfig['path'];
            }
        }

        return false;
    }

    /**
     * Returns the hostname of the current request (without port number)
     *
     * @return string
     */
    public static function getHostname()
    {
        // Check trusted requires config file which is not ready yet
        $host = Url::getHost($checkIfTrusted = false);

        // Remove any port number to get actual hostname
        $host = Url::getHostSanitized($host);

        return $host;
    }

    /**
     * If set, Piwik will use the hostname config no matter if it exists or not. Useful for instance if you want to
     * create a new hostname config:
     *
     *     $config = Config::getInstance();
     *     $config->forceUsageOfHostnameConfig('piwik.example.com');
     *     $config->save();
     *
     * @param string $hostname eg piwik.example.com
     * @param string $preferredPath If there are different paths for the config that can be used, eg /config/* and /misc/user/*,
     *                              and a preferred path is given, then the config path must contain the preferred path.
     * @return string
     * @throws \Exception In case the domain contains not allowed characters
     * @internal
     */
    public function forceUsageOfLocalHostnameConfig($hostname, $preferredPath = null)
    {
        $hostConfigs = self::getLocalConfigInfoForHostname($hostname);
        $fileNames = '';

        foreach ($hostConfigs as $hostConfig) {
            if (count($hostConfigs) > 1
                && $preferredPath
                && strpos($hostConfig['path'], $preferredPath) === false) {
                continue;
            }

            $filename = $hostConfig['file'];
            $fileNames .= $filename . ' ';

            if (Filesystem::isValidFilename($filename)) {
                $pathLocal = $hostConfig['path'];

                try {
                    $this->reload($pathLocal);
                } catch (Exception $ex) {
                    // pass (not required for local file to exist at this point)
                }

                return $pathLocal;
            }
        }

        throw new Exception('Matomo domain is not a valid looking hostname (' . trim($fileNames) . ').');
    }

    /**
     * Returns `true` if the local configuration file is writable.
     *
     * @return bool
     */
    public function isFileWritable()
    {
        return is_writable($this->settings->getPathLocal());
    }

    /**
     * Clear in-memory configuration so it can be reloaded
     * @deprecated since v2.12.0
     */
    public function clear()
    {
        $this->reload();
    }

    /**
     * Read configuration from files into memory
     *
     * @throws Exception if local config file is not readable; exits for other errors
     * @deprecated since v2.12.0
     */
    public function init()
    {
        $this->reload();
    }

    /**
     * Reloads config data from disk.
     *
     * @throws \Exception if the global config file is not found and this is a tracker request, or
     *                    if the local config file is not found and this is NOT a tracker request.
     */
    protected function reload($pathLocal = null, $pathGlobal = null, $pathCommon = null)
    {
        $this->settings->reload($pathGlobal, $pathLocal, $pathCommon);
    }

    /**
     * @deprecated
     */
    public function existsLocalConfig()
    {
        return is_readable($this->getLocalPath());
    }

    public function deleteLocalConfig()
    {
        $configLocal = $this->getLocalPath();
        
        if(file_exists($configLocal)){
            @unlink($configLocal);
        }
    }

    /**
     * Returns a configuration value or section by name.
     *
     * @param string $name The value or section name.
     * @return string|array The requested value requested. Returned by reference.
     * @throws Exception If the value requested not found in either `config.ini.php` or
     *                   `global.ini.php`.
     * @api
     */
    public function &__get($name)
    {
        $section =& $this->settings->getIniFileChain()->get($name);
        return $section;
    }

    /**
     * @api
     */
    public function getFromGlobalConfig($name)
    {
        return $this->settings->getIniFileChain()->getFrom($this->getGlobalPath(), $name);
    }

    /**
     * @api
     */
    public function getFromCommonConfig($name)
    {
        return $this->settings->getIniFileChain()->getFrom($this->getCommonPath(), $name);
    }
    
    /**
     * @api
     */
    public function getFromLocalConfig($name)
    {
        return $this->settings->getIniFileChain()->getFrom($this->getLocalPath(), $name);
    }

    /**
     * Sets a configuration value or section.
     *
     * @param string $name This section name or value name to set.
     * @param mixed $value
     * @api
     */
    public function __set($name, $value)
    {
        $this->settings->getIniFileChain()->set($name, $value);
    }

    /**
     * Dump config
     *
     * @return string|null
     * @throws \Exception
     */
    public function dumpConfig()
    {
        $chain = $this->settings->getIniFileChain();

        $header = "; <?php exit; ?> DO NOT REMOVE THIS LINE\n";
        $header .= "; file automatically generated or modified by Matomo; you can manually override the default values in global.ini.php by redefining them in this file.\n";
        return $chain->dumpChanges($header);
    }

    /**
     * Write user configuration file
     *
     * @throws \Exception if config file not writable
     */
    protected function writeConfig()
    {
        $output = $this->dumpConfig();
        if ($output !== null
            && $output !== false
        ) {
            $localPath = $this->getLocalPath();

            if ($this->doNotWriteConfigInTests) {
                // simulate whether it would be successful
                $success = is_writable($localPath);
            } else {
                $success = @file_put_contents($localPath, $output, LOCK_EX);
            }

            if ($success === false) {
                throw $this->getConfigNotWritableException();
            }

            $this->settings->getIniFileChain()->deleteConfigCache();

            /**
             * Triggered when a INI config file is changed on disk.
             *
             * @param string $localPath Absolute path to the changed file on the server.
             */
            Piwik::postEvent('Core.configFileChanged', [$localPath]);
        }
    }

    /**
     * Writes the current configuration to the **config.ini.php** file. Only writes options whose
     * values are different from the default.
     *
     * @api
     */
    public function forceSave()
    {
        $this->writeConfig();
    }

    /**
     * @throws \Exception
     */
    public function getConfigNotWritableException()
    {
        $path = "config/" . basename($this->getLocalPath());
        return new MissingFilePermissionException(Piwik::translate('General_ConfigFileIsNotWritable', array("(" . $path . ")", "")));
    }

    /**
     * Convenience method for setting settings in a single section. Will set them in a new array first
     * to be compatible with certain PHP versions.
     *
     * @param string $sectionName Section name.
     * @param string $name The setting name.
     * @param mixed $value The setting value to set.
     */
    public static function setSetting($sectionName, $name, $value)
    {
        $section = self::getInstance()->$sectionName;
        $section[$name] = $value;
        self::getInstance()->$sectionName = $section;
    }
}
