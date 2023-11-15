<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Piwik\Container\StaticContainer;

/**
 * Class to check if a newer version of Piwik is available
 */
class UpdateCheck
{
    const CHECK_INTERVAL = 28800; // every 8 hours
    const UI_CLICK_CHECK_INTERVAL = 10; // every 10s when user clicks UI link
    const LAST_CHECK_FAILED = 'UpdateCheck_LastCheckFailed';
    const LAST_TIME_CHECKED = 'UpdateCheck_LastTimeChecked';
    const LATEST_VERSION = 'UpdateCheck_LatestVersion';
    const SOCKET_TIMEOUT = 5;

    /**
     * Check for a newer version
     *
     * @param bool $force Force check
     * @param int $interval Interval used for update checks
     */
    public static function check($force = false, $interval = null)
    {
        if (!SettingsPiwik::isAutoUpdateEnabled()) {
            return;
        }

        if ($interval === null) {
            $interval = self::CHECK_INTERVAL;
        }

        $lastTimeChecked = Option::get(self::LAST_TIME_CHECKED);
        if ($force
            || $lastTimeChecked === false
            || time() - $interval > $lastTimeChecked
        ) {
            // set the time checked first, so that parallel Piwik requests don't all trigger the http requests
            Option::set(self::LAST_TIME_CHECKED, time(), $autoLoad = 1);

            $latestVersion = self::getLatestAvailableVersionNumber();
            $latestVersion = trim((string) $latestVersion);
            if (!preg_match('~^[0-9][0-9a-zA-Z_.-]*$~D', $latestVersion)) {
                $latestVersion = '';
            }

            $hasLastCheckFailed = '' === $latestVersion;

            Option::set(self::LAST_CHECK_FAILED, $hasLastCheckFailed);

            if ($hasLastCheckFailed) {
                // retry check on next request if previous attempt failed
                Option::set(self::LAST_TIME_CHECKED, $lastTimeChecked, $autoLoad = 1);
            } else {
                Option::set(self::LATEST_VERSION, $latestVersion);
            }
        }
    }

    /**
     * Get the latest available version number for the currently active release channel. Eg '2.15.0-b4' or '2.15.0'.
     * Should return a semantic version number in format MAJOR.MINOR.PATCH (http://semver.org/).
     * Returns an empty string in case one cannot connect to the remote server.
     * @return string
     */
    private static function getLatestAvailableVersionNumber()
    {
        $releaseChannels = StaticContainer::get('\Piwik\Plugin\ReleaseChannels');
        $channel = $releaseChannels->getActiveReleaseChannel();
        $url = $channel->getUrlToCheckForLatestAvailableVersion();

        try {
            $latestVersion = Http::sendHttpRequest($url, self::SOCKET_TIMEOUT);
        } catch (\Exception $e) {
            // e.g., disable_functions = fsockopen; allow_url_open = Off
            $latestVersion = '';
        }

        return $latestVersion;
    }

    /**
     * Returns the latest available version number. Does not perform a check whether a later version is available.
     *
     * @return false|string
     */
    public static function getLatestVersion()
    {
        return Option::get(self::LATEST_VERSION);
    }

    /**
     * Returns whether the last update check was flagged as having failed or not.
     *
     * @return bool
     */
    public static function hasLastCheckFailed(): bool
    {
        return (bool) Option::get(self::LAST_CHECK_FAILED);
    }

    /**
     * Returns version number of a newer Piwik release.
     *
     * @return string|bool  false if current version is the latest available,
     *                       or the latest version number if a newest release is available
     */
    public static function isNewestVersionAvailable()
    {
        $latestVersion = self::getLatestVersion();
        if (!empty($latestVersion)
            && version_compare(Version::VERSION, $latestVersion) == -1
        ) {
            return $latestVersion;
        }
        return false;
    }
}
