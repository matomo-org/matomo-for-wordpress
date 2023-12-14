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
use Matomo\Network\IPUtils;

/**
 * Provides URL related helper methods.
 *
 * This class provides simple methods that can be used to parse and modify
 * the current URL. It is most useful when plugins need to redirect the current
 * request to a URL and when they need to link to other parts of Piwik in
 * HTML.
 *
 * ### Examples
 *
 * **Redirect to a different controller action**
 *
 *     public function myControllerAction()
 *     {
 *         $url = Url::getCurrentQueryStringWithParametersModified(array(
 *             'module' => 'DevicesDetection',
 *             'action' => 'index'
 *         ));
 *         Url::redirectToUrl($url);
 *     }
 *
 * **Link to a different controller action in a template**
 *
 *     public function myControllerAction()
 *     {
 *         $url = Url::getCurrentQueryStringWithParametersModified(array(
 *             'module' => 'UserCountryMap',
 *             'action' => 'realtimeMap',
 *             'changeVisitAlpha' => 0,
 *             'removeOldVisits' => 0
 *         ));
 *         $view = new View("@MyPlugin/myPopup");
 *         $view->realtimeMapUrl = $url;
 *         return $view->render();
 *     }
 *
 */
class Url
{
    /**
     * Returns the current URL.
     *
     * @return string eg, `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     * @api
     */
    public static function getCurrentUrl()
    {
        return self::getCurrentScheme() . '://'
        . self::getCurrentHost()
        . self::getCurrentScriptName(false)
        . self::getCurrentQueryString();
    }

    /**
     * Returns the current URL without the query string.
     *
     * @param bool $checkTrustedHost Whether to do trusted host check. Should ALWAYS be true,
     *                               except in {@link Piwik\Plugin\Controller}.
     * @return string eg, `"http://example.org/dir1/dir2/index.php"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`.
     * @api
     */
    public static function getCurrentUrlWithoutQueryString($checkTrustedHost = true)
    {
        return self::getCurrentScheme() . '://'
        . self::getCurrentHost($default = 'unknown', $checkTrustedHost)
        . self::getCurrentScriptName(false);
    }

    /**
     * Returns the current URL without the query string and without the name of the file
     * being executed.
     *
     * @return string eg, `"http://example.org/dir1/dir2/"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`.
     * @api
     */
    public static function getCurrentUrlWithoutFileName()
    {
        return self::getCurrentScheme() . '://'
        . self::getCurrentHost()
        . self::getCurrentScriptPath();
    }

    /**
     * Returns the path to the script being executed. The script file name is not included.
     *
     * @return string eg, `"/dir1/dir2/"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     * @api
     */
    public static function getCurrentScriptPath()
    {
        $queryString = self::getCurrentScriptName();

        //add a fake letter case /test/test2/ returns /test which is not expected
        $urlDir = dirname($queryString . 'x');
        $urlDir = str_replace('\\', '/', $urlDir);
        // if we are in a subpath we add a trailing slash
        if (strlen($urlDir) > 1) {
            $urlDir .= '/';
        }
        return $urlDir;
    }

    /**
     * Returns the path to the script being executed. Includes the script file name.
     *
     * @param bool $removePathInfo If true (default value) then the PATH_INFO will be stripped.
     * @return string eg, `"/dir1/dir2/index.php"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     * @api
     */
    public static function getCurrentScriptName($removePathInfo = true)
    {
        $url = '';

        // insert extra path info if proxy_uri_header is set and enabled
        if (
            isset(Config::getInstance()->General['proxy_uri_header'])
            && Config::getInstance()->General['proxy_uri_header'] == 1
            && !empty($_SERVER['HTTP_X_FORWARDED_URI'])
        ) {
            $url .= $_SERVER['HTTP_X_FORWARDED_URI'];
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $url .= $_SERVER['REQUEST_URI'];

            // strip http://host (Apache+Rails anomaly)
            if (preg_match('~^https?://[^/]+($|/.*)~D', $url, $matches)) {
                $url = $matches[1];
            }

            // strip parameters
            if (($pos = mb_strpos($url, "?")) !== false) {
                $url = mb_substr($url, 0, $pos);
            }

            // strip path_info
            if ($removePathInfo && !empty($_SERVER['PATH_INFO'])) {
                $url = mb_substr($url, 0, -mb_strlen($_SERVER['PATH_INFO']));
            }
        }

        /**
         * SCRIPT_NAME is our fallback, though it may not be set correctly
         *
         * @see http://php.net/manual/en/reserved.variables.php
         */
        if (empty($url)) {
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $url = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['SCRIPT_FILENAME'])) {
                $url = $_SERVER['SCRIPT_FILENAME'];
            } elseif (isset($_SERVER['argv'])) {
                $url = $_SERVER['argv'][0];
            }
        }

        if (!isset($url[0]) || $url[0] !== '/') {
            $url = '/' . $url;
        }

        // A hash part should actually be never send to the server, as browsers automatically remove them from the request
        // The same happens for tools like cUrl. While Apache won't answer requests that contain them, Nginx would handle them
        // and the hash part would be included in REQUEST_URI. Therefor we always remove any hash parts here.
        if (mb_strpos($url, '#')) {
            $url = mb_substr($url, 0, mb_strpos($url, '#'));
        }

        return $url;
    }

    /**
     * Returns the current URL's protocol.
     *
     * @return string `'https'` or `'http'`
     * @api
     */
    public static function getCurrentScheme()
    {
        if (self::isPiwikConfiguredToAssumeSecureConnection()) {
            return 'https';
        }
        return self::getCurrentSchemeFromRequestHeader();
    }

    /**
     * Validates the **Host** HTTP header (untrusted user input). Used to prevent Host header
     * attacks.
     *
     * @param string|bool $host Contents of Host: header from the HTTP request. If `false`, gets the
     *                          value from the request.
     * @return bool `true` if valid; `false` otherwise.
     */
    public static function isValidHost($host = false): bool
    {
        // only do trusted host check if it's enabled
        if (
            isset(Config::getInstance()->General['enable_trusted_host_check'])
            && Config::getInstance()->General['enable_trusted_host_check'] == 0
        ) {
            return true;
        }

        if (false === $host || null === $host) {
            $host = self::getHostFromServerVariable();
            if (empty($host)) {
                // if no current host, assume valid
                return true;
            }
        }

        // if host is in hardcoded allowlist, assume it's valid
        if (in_array($host, self::getAlwaysTrustedHosts())) {
            return true;
        }

        $trustedHosts = self::getTrustedHosts();

        // Only punctuation we allow is '[', ']', ':', '.', '_' and '-'
        $hostLength = strlen($host);
        if ($hostLength !== strcspn($host, '`~!@#$%^&*()+={}\\|;"\'<>,?/ ')) {
            return false;
        }

        // if no trusted hosts, just assume it's valid
        if (empty($trustedHosts)) {
            self::saveTrustedHostnameInConfig($host);
            return true;
        }

        // Escape trusted hosts for preg_match call below
        foreach ($trustedHosts as &$trustedHost) {
            $trustedHost = preg_quote($trustedHost);
        }
        $trustedHosts = str_replace("/", "\\/", $trustedHosts);

        $untrustedHost = mb_strtolower($host);
        $untrustedHost = rtrim($untrustedHost, '.');

        $hostRegex = mb_strtolower('/(^|.)' . implode('$|', $trustedHosts) . '$/');

        $result = preg_match($hostRegex, $untrustedHost);
        return 0 !== $result;
    }

    /**
     * Records one host, or an array of hosts in the config file,
     * if user is Super User
     *
     * @static
     * @param $host string|array
     * @return bool
     */
    public static function saveTrustedHostnameInConfig($host)
    {
        return self::saveHostsnameInConfig($host, 'General', 'trusted_hosts');
    }

    public static function saveCORSHostnameInConfig($host)
    {
        return self::saveHostsnameInConfig($host, 'General', 'cors_domains');
    }

    protected static function saveHostsnameInConfig($host, $domain, $key)
    {
        if (
            Piwik::hasUserSuperUserAccess()
            && file_exists(Config::getLocalConfigPath())
        ) {
            $config = Config::getInstance()->$domain;
            if (!is_array($host)) {
                $host = [$host];
            }
            $host = array_filter($host);
            if (empty($host)) {
                return false;
            }
            $config[$key] = $host;
            Config::getInstance()->$domain = $config;
            Config::getInstance()->forceSave();
            return true;
        }
        return false;
    }

    /**
     * Returns the current host.
     *
     * @param bool $checkIfTrusted Whether to do trusted host check. Should ALWAYS be true,
     *                             except in Controller.
     * @return string|bool eg, `"demo.piwik.org"` or false if no host found.
     */
    public static function getHost($checkIfTrusted = true)
    {
        $host = self::getHostFromServerVariable();

        if (strlen($host) && (!$checkIfTrusted || self::isValidHost($host))) {
            return $host;
        }

        // HTTP/1.0 request doesn't include Host: header
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        return false;
    }

    protected static function getHostFromServerVariable()
    {
        try {
            // this fails when trying to get the hostname before the config was initialized
            // e.g. for loading the domain specific configuration file
            // in such a case we always use HTTP_HOST
            $preferServerName = Config::getInstance()->General['host_validation_use_server_name'];
        } catch (\Exception $e) {
            $preferServerName = false;
        }

        if ($preferServerName && strlen($host = self::getHostFromServerNameVar())) {
            return $host;
        } elseif (isset($_SERVER['HTTP_HOST']) && strlen($host = $_SERVER['HTTP_HOST'])) {
            return $host;
        }

        return false;
    }

    /**
     * Returns the valid hostname (according to RFC standards) as a string; else it will return false if it isn't valid.
     * If the hostname isn't supplied it will default to using Url::getHost
     * Note: this will not verify if the hostname is trusted.
     * @param $hostname
     * @return false|string
     */
    public static function getRFCValidHostname($hostname = null)
    {
        if (empty($hostname)) {
            $hostname = self::getHost(false);
        }
        return filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    /**
     * Sets the host. Useful for CLI scripts, eg. core:archive command
     *
     * @param $host string
     */
    public static function setHost($host)
    {
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['HTTP_HOST'] = $host;
        unset($_SERVER['SERVER_PORT']);
    }

    /**
     * Returns the current host.
     *
     * @param string $default Default value to return if host unknown
     * @param bool $checkTrustedHost Whether to do trusted host check. Should ALWAYS be true,
     *                               except in Controller.
     * @return string eg, `"example.org"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     * @api
     */
    public static function getCurrentHost($default = 'unknown', $checkTrustedHost = true)
    {
        $hostHeaders = [];

        $config = Config::getInstance()->General;
        if (isset($config['proxy_host_headers'])) {
            $hostHeaders = $config['proxy_host_headers'];
        }

        if (!is_array($hostHeaders)) {
            $hostHeaders = [];
        }

        $host = self::getHost($checkTrustedHost);
        $default = Common::sanitizeInputValue($host ? $host : $default);

        return IP::getNonProxyIpFromHeader($default, $hostHeaders);
    }

    /**
     * Returns the query string of the current URL.
     *
     * @return string eg, `"?param1=value1&param2=value2"` if the current URL is
     *                `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     * @api
     */
    public static function getCurrentQueryString()
    {
        $url = '';
        if (
            isset($_SERVER['QUERY_STRING'])
            && !empty($_SERVER['QUERY_STRING'])
        ) {
            $url .= "?" . $_SERVER['QUERY_STRING'];
        }
        return $url;
    }

    /**
     * Returns an array mapping query parameter names with query parameter values for
     * the current URL.
     *
     * @return array If current URL is `"http://example.org/dir1/dir2/index.php?param1=value1&param2=value2"`
     *               this will return:
     *
     *                   array(
     *                       'param1' => string 'value1',
     *                       'param2' => string 'value2'
     *                   )
     * @api
     */
    public static function getArrayFromCurrentQueryString()
    {
        $queryString = self::getCurrentQueryString();
        $urlValues = UrlHelper::getArrayFromQueryString($queryString);
        return $urlValues;
    }

    /**
     * Modifies the current query string with the supplied parameters and returns
     * the result. Parameters in the current URL will be overwritten with values
     * in `$params` and parameters absent from the current URL but present in `$params`
     * will be added to the result.
     *
     * @param array $params set of parameters to modify/add in the current URL
     *                      eg, `array('param3' => 'value3')`
     * @return string eg, `"?param2=value2&param3=value3"`
     * @api
     */
    public static function getCurrentQueryStringWithParametersModified($params)
    {
        $urlValues = self::getArrayFromCurrentQueryString();
        foreach ($params as $key => $value) {
            $urlValues[$key] = $value;
        }
        $query = self::getQueryStringFromParameters($urlValues);
        if (strlen($query) > 0) {
            return '?' . $query;
        }
        return '';
    }

    /**
     * Converts an array of parameters name => value mappings to a query
     * string. Values must already be URL encoded before you call this function.
     *
     * @param array $parameters eg. `array('param1' => 10, 'param2' => array(1,2))`
     * @return string eg. `"param1=10&param2[]=1&param2[]=2"`
     * @api
     */
    public static function getQueryStringFromParameters($parameters)
    {
        $query = '';
        foreach ($parameters as $name => $value) {
            if (is_null($value) || $value === false) {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $theValue) {
                    $query .= $name . "[]=" . $theValue . "&";
                }
            } else {
                $query .= $name . "=" . $value . "&";
            }
        }
        $query = substr($query, 0, -1);
        return $query;
    }

    public static function getQueryStringFromUrl($url)
    {
        return parse_url($url, PHP_URL_QUERY);
    }

    /**
     * Redirects the user to the referrer. If no referrer exists, the user is redirected
     * to the current URL without query string.
     *
     * @api
     */
    public static function redirectToReferrer()
    {
        $referrer = self::getReferrer();
        if ($referrer !== false) {
            self::redirectToUrl($referrer);
        }
        self::redirectToUrl(self::getCurrentUrlWithoutQueryString());
    }

    private static function redirectToUrlNoExit($url)
    {
        if (
            UrlHelper::isLookLikeUrl($url)
            || strpos($url, 'index.php') === 0
        ) {
            Common::sendResponseCode(302);
            Common::sendHeader("X-Robots-Tag: noindex");
            Common::sendHeader("Location: $url");
        } else {
            echo "Invalid URL to redirect to.";
        }

        if (Common::isPhpCliMode()) {
            throw new Exception("If you were using a browser, Matomo would redirect you to this URL: $url \n\n");
        }
    }

    /**
     * Redirects the user to the specified URL.
     *
     * @param string $url
     * @throws Exception
     * @api
     */
    public static function redirectToUrl($url)
    {
        // Close the session manually.
        // We should not have to call this because it was registered via register_shutdown_function,
        // but it is not always called fast enough
        Session::close();

        self::redirectToUrlNoExit($url);

        exit;
    }

    /**
     * If the page is using HTTP, redirect to the same page over HTTPS
     */
    public static function redirectToHttps()
    {
        if (ProxyHttp::isHttps()) {
            return;
        }
        $url = self::getCurrentUrl();
        $url = str_replace("http://", "https://", $url);
        self::redirectToUrl($url);
    }

    /**
     * Returns the **HTTP_REFERER** `$_SERVER` variable, or `false` if not found.
     *
     * @return string|false
     * @api
     */
    public static function getReferrer()
    {
        if (!empty($_SERVER['HTTP_REFERER'])) {
            return $_SERVER['HTTP_REFERER'];
        }
        return false;
    }

    /**
     * Returns `true` if the URL points to something on the same host, `false` if otherwise.
     *
     * @param string $url
     * @return bool True if local; false otherwise.
     * @api
     */
    public static function isLocalUrl($url)
    {
        if (empty($url)) {
            return true;
        }

        // handle host name mangling
        $requestUri = isset($_SERVER['SCRIPT_URI']) ? $_SERVER['SCRIPT_URI'] : '';
        $parseRequest = @parse_url($requestUri);
        $hosts = [self::getHost(), self::getCurrentHost()];
        if (!empty($parseRequest['host'])) {
            $hosts[] = $parseRequest['host'];
        }

        // drop port numbers from hostnames and IP addresses
        $hosts = array_map(self::class . '::getHostSanitized', $hosts);

        $disableHostCheck = Config::getInstance()->General['enable_trusted_host_check'] == 0;
        // compare scheme and host
        $parsedUrl = @parse_url($url);
        $host = IPUtils::sanitizeIp($parsedUrl['host'] ?? '');
        return !empty($host)
        && ($disableHostCheck || in_array($host, $hosts))
        && !empty($parsedUrl['scheme'])
        && in_array($parsedUrl['scheme'], ['http', 'https']);
    }

    /**
     * Checks whether the given host is a local host like `127.0.0.1` or `localhost`.
     *
     * @param string $host
     * @return bool
     */
    public static function isLocalHost($host)
    {
        if (empty($host)) {
            return false;
        }

        // remove port
        $hostWithoutPort = explode(':', $host);
        array_pop($hostWithoutPort);
        $hostWithoutPort = implode(':', $hostWithoutPort);

        $localHostnames = Url::getLocalHostnames();
        return in_array($host, $localHostnames, true)
            || in_array($hostWithoutPort, $localHostnames, true);
    }

    public static function getTrustedHostsFromConfig()
    {
        $hosts = self::getHostsFromConfig('General', 'trusted_hosts');

        // Case user wrote in the config, http://example.com/test instead of example.com
        foreach ($hosts as &$host) {
            if (UrlHelper::isLookLikeUrl($host)) {
                $host = parse_url($host, PHP_URL_HOST);
            }
        }
        return $hosts;
    }

    public static function getTrustedHosts()
    {
        return self::getTrustedHostsFromConfig();
    }

    public static function getCorsHostsFromConfig()
    {
        return self::getHostsFromConfig('General', 'cors_domains');
    }

    /**
     * Returns hostname, without port numbers
     *
     * @param $host
     * @return string
     */
    public static function getHostSanitized($host)
    {
        if (!class_exists("Matomo\\Network\\IPUtils")) {
            throw new Exception("Matomo\\Network\\IPUtils could not be found, maybe you are using Matomo from git and need to update Composer. $ php composer.phar update");
        }
        return IPUtils::sanitizeIp($host);
    }

    protected static function getHostsFromConfig($domain, $key)
    {
        $config = @Config::getInstance()->$domain;

        if (!isset($config[$key])) {
            return [];
        }

        $hosts = $config[$key];
        if (!is_array($hosts)) {
            return [];
        }
        return $hosts;
    }

    /**
     * Returns the host part of any valid URL.
     *
     * @param string $url  Any fully qualified URL
     * @return string|null The actual host in lower case or null if $url is not a valid fully qualified URL.
     */
    public static function getHostFromUrl($url)
    {
        if (!is_string($url)) {
            return null;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);

        if (empty($urlHost)) {
            return null;
        }

        return mb_strtolower($urlHost);
    }

    /**
     * Checks whether any of the given URLs has the given host. If not, we will also check whether any URL uses a
     * subdomain of the given host. For instance if host is "example.com" and a URL is "http://www.example.com" we
     * consider this as valid and return true. The always trusted hosts such as "127.0.0.1" are considered valid as well.
     *
     * @param $host
     * @param $urls
     * @return bool
     */
    public static function isHostInUrls($host, $urls)
    {
        if (empty($host)) {
            return false;
        }

        $host = mb_strtolower($host);

        if (!empty($urls)) {
            foreach ($urls as $url) {
                if (mb_strtolower($url) === $host) {
                    return true;
                }

                $siteHost = self::getHostFromUrl($url);

                if ($siteHost === $host) {
                    return true;
                }

                if (Common::stringEndsWith($siteHost, '.' . $host)) {
                    // allow subdomains
                    return true;
                }
            }
        }

        return in_array($host, self::getAlwaysTrustedHosts());
    }

    /**
     * List of hosts that are never checked for validity.
     *
     * @return array
     */
    private static function getAlwaysTrustedHosts()
    {
        return self::getLocalHostnames();
    }

    /**
     * @return array
     */
    public static function getLocalHostnames()
    {
        return ['localhost', '127.0.0.1', '::1', '[::1]', '[::]', '0000::1', '0177.0.0.1', '2130706433', '[0:0:0:0:0:ffff:127.0.0.1]'];
    }

    /**
     * @return bool
     */
    public static function isSecureConnectionAssumedByPiwikButNotForcedYet()
    {
        $isSecureConnectionLikelyNotUsed = Url::isSecureConnectionLikelyNotUsed();
        $hasSessionCookieSecureFlag = ProxyHttp::isHttps();
        $isSecureConnectionAssumedByPiwikButNotForcedYet = Url::isPiwikConfiguredToAssumeSecureConnection() && !SettingsPiwik::isHttpsForced();

        return     $isSecureConnectionLikelyNotUsed
                && $hasSessionCookieSecureFlag
                && $isSecureConnectionAssumedByPiwikButNotForcedYet;
    }

    /**
     * @return string
     */
    protected static function getCurrentSchemeFromRequestHeader()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_SCHEME']) && strtolower($_SERVER['HTTP_X_FORWARDED_SCHEME']) === 'https') {
            return 'https';
        }

        if (isset($_SERVER['HTTP_X_URL_SCHEME']) && strtolower($_SERVER['HTTP_X_URL_SCHEME']) === 'https') {
            return 'https';
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'http') {
            return 'http';
        }

        if (
            (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] === true))
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            return 'https';
        }
        return 'http';
    }

    protected static function isSecureConnectionLikelyNotUsed()
    {
        return  Url::getCurrentSchemeFromRequestHeader() == 'http';
    }

    /**
     * @return bool
     */
    protected static function isPiwikConfiguredToAssumeSecureConnection()
    {
        $assume_secure_protocol = @Config::getInstance()->General['assume_secure_protocol'];
        return (bool) $assume_secure_protocol;
    }

    public static function getHostFromServerNameVar()
    {
        $host = @$_SERVER['SERVER_NAME'];
        if (!empty($host)) {
            if (
                strpos($host, ':') === false
                && !empty($_SERVER['SERVER_PORT'])
                && $_SERVER['SERVER_PORT'] != 80
                && $_SERVER['SERVER_PORT'] != 443
            ) {
                $host .= ':' . $_SERVER['SERVER_PORT'];
            }
        }
        return $host;
    }

    /**
     * Add campaign parameters to URLs linking to matomo.org to improve understanding of how online help is being
     * used for different parts of the application, no personally identifiable information is included, just the area
     * of the application from which the link originated.
     *
     * @param string|null $url      eg. www.matomo.org/faq/123 or https://matomo.org/faq/456
     * @param string|null $campaign Optional campaign override, defaults to 'Matomo_App'
     * @param string|null $source   Optional campaign source override, defaults to either 'Matomo_App_OnPremise' or
     *                              'Matomo_App_Cloud'
     * @param string|null $medium   Optional campaign medium, defaults to App.[module].[action] where module and action are
     *                              taken from the currently viewed application page, eg. 'CoreAdminHome.trackingCodeGenerator'
     *
     * @return string|null      www.matomo.org/faq/123?mtm_campaign=Matomo_App&mtm_source=Matomo_App_OnPremise&mtm_medium=App.CoreAdminHome.trackingCodeGenerator
     */
    public static function addCampaignParametersToMatomoLink(?string $url = null, ?string $campaign = null,
                                                             ?string $source = null, ?string $medium = null): ?string
    {

        // Ignore if disabled by config setting
        if (Config::getInstance()->General['disable_tracking_matomo_app_links']) {
            return $url;
        }

        // Ignore nulls
        if ($url === null) {
            return $url;
        }

        // Ignore non-matomo domains
        $domain = self::getHostFromUrl($url);
        if (!in_array($domain, ['matomo.org', 'www.matomo.org', 'developer.matomo.org', 'plugins.matomo.org'])) {
            return $url;
        }

        // Build parameters
        if ($medium === null) {
            $module = Piwik::getModule();
            $action = Piwik::getAction();
            if (empty($module) || empty($action)) {
                return $url; // Ignore if no module or action
            }
            $medium = 'App.' . $module.'.'.$action;
        }
        $newParams = [
            'mtm_campaign' => $campaign ?? 'Matomo_App',
            'mtm_source' => $source ?? 'Matomo_App_' . (\Piwik\Plugin\Manager::getInstance()->isPluginLoaded('Cloud') ? 'Cloud' : 'OnPremise'),
            'mtm_medium' => $medium
            ];

        // Add parameters to the link, overriding any existing campaign parameters while preserving the path and query string
        $pathAndQueryString = UrlHelper::getPathAndQueryFromUrl($url, $newParams, true);
        return 'https://' . $domain . '/' . $pathAndQueryString;
    }

}
