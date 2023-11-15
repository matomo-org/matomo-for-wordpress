<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Exception;
use Piwik\Cache as PiwikCache;
use Piwik\Config\GeneralConfig;
use Piwik\Container\StaticContainer;

/**
 * Contains helper methods that can be used to get common Piwik settings.
 *
 */
class SettingsPiwik
{
    public const OPTION_PIWIK_URL = 'piwikUrl';

    /**
     * Get salt from [General] section. Should ONLY be used as a seed to create hashes
     *
     * NOTE: Keep this salt secret! Never output anywhere or share it etc.
     *
     * @return string|null
     */
    public static function getSalt(): ?string
    {
        static $salt = null;
        if (is_null($salt)) {
            $salt = Config::getInstance()->General['salt'] ?? '';
        }
        return $salt;
    }

    /**
     * Should Piwik check that the login & password have minimum length and valid characters?
     *
     * @return bool  True if checks enabled; false otherwise
     */
    public static function isUserCredentialsSanityCheckEnabled(): bool
    {
        return Config::getInstance()->General['disable_checks_usernames_attributes'] == 0;
    }

    /**
     * Should Piwik show the update notification to superusers only?
     *
     * @return bool  True if show to superusers only; false otherwise
     */
    public static function isShowUpdateNotificationToSuperUsersOnlyEnabled(): bool
    {
        return Config::getInstance()->General['show_update_notification_to_superusers_only'] == 1;
    }

    /**
     * Returns every stored segment to pre-process for each site during cron archiving.
     *
     * @return array The list of stored segments that apply to all sites.
     */
    public static function getKnownSegmentsToArchive(): array
    {
        $cacheId = 'KnownSegmentsToArchive';
        $cache   = PiwikCache::getTransientCache();
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        $segments = Config::getInstance()->Segments;
        $segmentsToProcess = isset($segments['Segments']) ? $segments['Segments'] : array();

        /**
         * Triggered during the cron archiving process to collect segments that
         * should be pre-processed for all websites. The archiving process will be launched
         * for each of these segments when archiving data.
         *
         * This event can be used to add segments to be pre-processed. If your plugin depends
         * on data from a specific segment, this event could be used to provide enhanced
         * performance.
         *
         * _Note: If you just want to add a segment that is managed by the user, use the
         * SegmentEditor API._
         *
         * **Example**
         *
         *     Piwik::addAction('Segments.getKnownSegmentsToArchiveAllSites', function (&$segments) {
         *         $segments[] = 'country=jp;city=Tokyo';
         *     });
         *
         * @param array &$segmentsToProcess List of segment definitions, eg,
         *
         *                                      array(
         *                                          'browserCode=ff;resolution=800x600',
         *                                          'country=jp;city=Tokyo'
         *                                      )
         *
         *                                  Add segments to this array in your event handler.
         */
        Piwik::postEvent('Segments.getKnownSegmentsToArchiveAllSites', array(&$segmentsToProcess));

        $segmentsToProcess = array_unique($segmentsToProcess);

        $cache->save($cacheId, $segmentsToProcess);
        return $segmentsToProcess;
    }

    /**
     * Returns the list of stored segments to pre-process for an individual site when executing
     * cron archiving.
     *
     * @param int $idSite The ID of the site to get stored segments for.
     * @return string[] The list of stored segments that apply to the requested site.
     */
    public static function getKnownSegmentsToArchiveForSite($idSite): array
    {
        $cacheId = 'KnownSegmentsToArchiveForSite' . $idSite;
        $cache   = PiwikCache::getTransientCache();
        if ($cache->contains($cacheId)) {
            return $cache->fetch($cacheId);
        }

        $segments = array();
        /**
         * Triggered during the cron archiving process to collect segments that
         * should be pre-processed for one specific site. The archiving process will be launched
         * for each of these segments when archiving data for that one site.
         *
         * This event can be used to add segments to be pre-processed for one site.
         *
         * _Note: If you just want to add a segment that is managed by the user, you should use the
         * SegmentEditor API._
         *
         * **Example**
         *
         *     Piwik::addAction('Segments.getKnownSegmentsToArchiveForSite', function (&$segments, $idSite) {
         *         $segments[] = 'country=jp;city=Tokyo';
         *     });
         *
         * @param array &$segmentsToProcess List of segment definitions, eg,
         *
         *                                      array(
         *                                          'browserCode=ff;resolution=800x600',
         *                                          'country=JP;city=Tokyo'
         *                                      )
         *
         *                                  Add segments to this array in your event handler.
         * @param int $idSite The ID of the site to get segments for.
         */
        Piwik::postEvent('Segments.getKnownSegmentsToArchiveForSite', array(&$segments, $idSite));

        $segments = array_unique($segments);

        $cache->save($cacheId, $segments);

        return $segments;
    }

    /**
     * Number of websites to show in the Website selector
     *
     * @return int
     */
    public static function getWebsitesCountToDisplay(): int
    {
        $count = max(Config::getInstance()->General['site_selector_max_sites'],
            Config::getInstance()->General['autocomplete_min_sites']);
        return (int)$count;
    }

    /**
     * Returns the URL to this Piwik instance, eg. **http://demo.piwik.org/** or **http://example.org/piwik/**.
     *
     * @return string|false return false if no value is configured and we are in PHP CLI mode
     * @api
     */
    public static function getPiwikUrl()
    {
        $url = Option::get(self::OPTION_PIWIK_URL);

        $isPiwikCoreDispatching = defined('PIWIK_ENABLE_DISPATCH') && PIWIK_ENABLE_DISPATCH;
        if (Common::isPhpCliMode()
            // in case core:archive command is triggered (often with localhost domain)
            || SettingsServer::isArchivePhpTriggered()
            // When someone else than core is dispatching this request then we return the URL as it is read only
            || !$isPiwikCoreDispatching
        ) {
            return $url;
        }

        $currentUrl = Common::sanitizeInputValue(Url::getCurrentUrlWithoutFileName());

        // when script is called from /misc/cron/archive.php, Piwik URL is /index.php
        $currentUrl = str_replace("/misc/cron", "", $currentUrl);

        if (empty($url)
            // if URL changes, always update the cache
            || $currentUrl !== $url
        ) {
            $host = Url::getHostFromUrl($currentUrl);

            if (strlen($currentUrl) >= strlen('http://a/')
                && Url::isValidHost($host)
                && !Url::isLocalHost($host)) {
                self::overwritePiwikUrl($currentUrl);
            }
            $url = $currentUrl;
        }

        if (ProxyHttp::isHttps()) {
            $url = str_replace("http://", "https://", $url);
        }
        return $url;
    }

    /**
     * @return bool
     */
    public static function isMatomoInstalled(): bool
    {
        $config = Config::getInstance()->getLocalPath();
        $exists = file_exists($config);

        // Piwik is not installed if the config file is not found
        if (!$exists) {
            return false;
        }

        $general = Config::getInstance()->General;

        $isInstallationInProgress = false;
        if (array_key_exists('installation_in_progress', $general)) {
            $isInstallationInProgress = (bool) $general['installation_in_progress'];
        }
        if ($isInstallationInProgress) {
            return false;
        }

        // Check that the database section is really set, ie. file is not empty
        if (empty(Config::getInstance()->database['username'])) {
            return false;
        }
        return true;
    }

    /**
     * Check if outgoing internet connections are enabled
     * This is often disable in an intranet environment
     * 
     * @return bool
     */
    public static function isInternetEnabled(): bool
    {
        return (bool) Config::getInstance()->General['enable_internet_features'];
    }

    /**
     * Detect whether user has enabled auto updates. Please note this config is a bit misleading. It is currently
     * actually used for 2 things: To disable making any connections back to Piwik, and to actually disable the auto
     * update of core and plugins.
     * @return bool
     */
    public static function isAutoUpdateEnabled(): bool
    {
        $enableAutoUpdate = (bool) Config::getInstance()->General['enable_auto_update'];
        if(self::isInternetEnabled() === true && $enableAutoUpdate === true){
            return true;
        }
        
        return false;
    }

    /**
     * Detects whether an auto update can be made. An update is possible if the user is not on multiple servers and if
     * automatic updates are actually enabled. If a user is running Piwik on multiple servers an update is not possible
     * as it would be installed only on one server instead of all of them. Also if a user has disabled automatic updates
     * we cannot perform any automatic updates.
     *
     * @return bool
     */
    public static function isAutoUpdatePossible(): bool
    {
        return !self::isMultiServerEnvironment() && self::isAutoUpdateEnabled();
    }

    /**
     * Returns `true` if Piwik is running on more than one server. For example in a load balanced environment. In this
     * case we should not make changes to the config and not install a plugin via the UI as it would be only executed
     * on one server.
     * @return bool
     */
    public static function isMultiServerEnvironment(): bool
    {
        $is = Config::getInstance()->General['multi_server_environment'];

        return !empty($is);
    }

    /**
     * Returns `true` if segmentation is allowed for this user, `false` if otherwise.
     *
     * @return bool
     * @api
     */
    public static function isSegmentationEnabled(): bool
    {
        return !Piwik::isUserIsAnonymous()
        || Config::getInstance()->General['anonymous_user_enable_use_segments_API'];
    }

    /**
     * Returns true if unique visitors should be processed for the given period type.
     *
     * Unique visitor processing is controlled by the `[General] enable_processing_unique_visitors_...`
     * INI config options. By default, unique visitors are processed only for day/week/month periods.
     *
     * @param string $periodLabel `"day"`, `"week"`, `"month"`, `"year"` or `"range"`
     * @return bool
     * @api
     */
    public static function isUniqueVisitorsEnabled(string $periodLabel): bool
    {
        $generalSettings = Config::getInstance()->General;

        $settingName = "enable_processing_unique_visitors_$periodLabel";
        $result = !empty($generalSettings[$settingName]) && $generalSettings[$settingName] == 1;

        // check enable_processing_unique_visitors_year_and_range for backwards compatibility
        if (($periodLabel === 'year' || $periodLabel === 'range')
            && isset($generalSettings['enable_processing_unique_visitors_year_and_range'])
        ) {
            $result |= $generalSettings['enable_processing_unique_visitors_year_and_range'] == 1;
        }

        return $result;
    }

    /**
     * If Piwik uses per-domain config file, make sure CustomLogo is unique
     * @param string $path
     * @return string
     * @throws \Piwik\Exception\DI\DependencyException
     * @throws \Piwik\Exception\DI\NotFoundException
     * @throws Exception
     */
    public static function rewriteMiscUserPathWithInstanceId(string $path): string
    {
        $tmp = StaticContainer::get('path.misc.user');
        $path = self::rewritePathAppendPiwikInstanceId($path, $tmp);
        return $path;
    }

    /**
     * Returns true if the Piwik server appears to be working.
     *
     * If the Piwik server is in an error state (eg. some directories are not writable and Piwik displays error message),
     * or if the Piwik server is "offline",
     * this will return false..
     *
     * @param string $piwikServerUrl
     * @param bool $acceptInvalidSSLCertificates
     * @return void
     * @throws Exception
     */
    public static function checkPiwikServerWorking(string $piwikServerUrl, bool $acceptInvalidSSLCertificates = false): void
    {
        // Now testing if the webserver is running
        try {
            $fetched = Http::sendHttpRequestBy('curl',
                                                $piwikServerUrl,
                                                $timeout = 45,
                                                $userAgent = null,
                                                $destinationPath = null,
                                                $file = null,
                                                $followDepth = 0,
                                                $acceptLanguage = false,
                                                $acceptInvalidSSLCertificates
            );
        } catch (Exception $e) {
            $fetched = "ERROR fetching: " . $e->getMessage();
        }
        // this will match when Piwik not installed yet, or favicon not customised
        $expectedStringAlt = 'plugins/CoreHome/images/favicon.png';

        // this will match when Piwik is installed and favicon has been customised
        $expectedString = 'misc/user/';

        // see checkPiwikIsNotInstalled()
        $expectedStringAlreadyInstalled = 'piwik-is-already-installed';

        $expectedStringNotFound = strpos($fetched, $expectedString) === false
                                && strpos($fetched, $expectedStringAlt) === false
                                && strpos($fetched, $expectedStringAlreadyInstalled) === false;

        $hasError = false !== strpos($fetched, PAGE_TITLE_WHEN_ERROR);

        if ($hasError || $expectedStringNotFound) {
            throw new Exception("\nMatomo should be running at: "
                . $piwikServerUrl
                . " but this URL returned an unexpected response: '"
                . $fetched . "'\n\n");
        }
    }

    /**
     * Returns true if Piwik is deployed using git
     * FAQ: http://piwik.org/faq/how-to-install/faq_18271/
     *
     * @return bool
     */
    public static function isGitDeployment(): bool
    {
        return file_exists(PIWIK_INCLUDE_PATH . '/.git/HEAD');
    }

    /**
     * @return string
     */
    public static function getCurrentGitBranch(): string
    {
        $file = PIWIK_INCLUDE_PATH . '/.git/HEAD';
        if (!file_exists($file)) {
            return '';
        }
        $firstLineOfGitHead = file($file);
        if (empty($firstLineOfGitHead)) {
            return '';
        }
        $firstLineOfGitHead = $firstLineOfGitHead[0];
        $parts = explode('/', $firstLineOfGitHead);
        if (empty($parts[2])) {
            return '';
        }
        $currentGitBranch = trim($parts[2]);
        return $currentGitBranch;
    }

    /**
     * @param string $pathToRewrite
     * @param string $leadingPathToAppendHostnameTo
     * @return string
     * @throws Exception
     */
    protected static function rewritePathAppendPiwikInstanceId(string $pathToRewrite, string $leadingPathToAppendHostnameTo): string
    {
        $instanceId = self::getPiwikInstanceId();
        if (empty($instanceId)) {
            return $pathToRewrite;
        }

        if (($posTmp = strrpos($pathToRewrite, $leadingPathToAppendHostnameTo)) === false) {
            throw new Exception("The path $pathToRewrite was expected to contain the string  $leadingPathToAppendHostnameTo");
        }

        $tmpToReplace = $leadingPathToAppendHostnameTo . $instanceId . '/';

        // replace only the latest occurrence (in case path contains twice /tmp)
        $pathToRewrite = substr_replace($pathToRewrite, $tmpToReplace, $posTmp, strlen($leadingPathToAppendHostnameTo));
        return $pathToRewrite;
    }

    /**
     * @throws Exception
     * @return string|false return string or false if not set
     */
    public static function getPiwikInstanceId()
    {
        // until Matomo is installed, we use hostname as instance_id
        if (!self::isMatomoInstalled() && Common::isPhpCliMode()) {
            // enterprise:install use case
            return Config::getHostname();
        }

        // config.ini.php not ready yet, instance_id will not be set
        if (!Config::getInstance()->existsLocalConfig()) {
            return false;
        }

        $instanceId = GeneralConfig::getConfigValue('instance_id');
        if (!empty($instanceId)) {
            return preg_replace('/[^\w\.-]/', '', $instanceId);
        }

        // do not rewrite the path as Matomo uses the standard config.ini.php file
        return false;
    }

    /**
     * @param string $currentUrl
     */
    public static function overwritePiwikUrl(string $currentUrl): void
    {
        Option::set(self::OPTION_PIWIK_URL, $currentUrl, $autoLoad = true);
    }

    /**
     * @return bool
     */
    public static function isHttpsForced(): bool
    {
        if (!self::isMatomoInstalled()) {
            // Only enable this feature after Piwik is already installed
            return false;
        }
        return Config::getInstance()->General['force_ssl'] == 1;
    }

    /**
     * Note: this config settig is also checked in the InterSites plugin
     *
     * @return bool
     */
    public static function isSameFingerprintAcrossWebsites(): bool
    {
        return (bool)Config::getInstance()->Tracker['enable_fingerprinting_across_websites'];
    }
}
