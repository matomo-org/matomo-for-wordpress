<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use DateTime;
use Piwik\Container\StaticContainer;
/**
 * Simple class to handle the cookies:
 * - read a cookie values
 * - edit an existing cookie and save it
 * - create a new cookie, set values, expiration date, etc. and save it
 *
 */
class Cookie
{
    /**
     * Don't create a cookie bigger than 1k
     */
    const MAX_COOKIE_SIZE = 1024;
    /**
     * The name of the cookie
     * @var string
     */
    protected $name = null;
    /**
     * The expire time for the cookie (expressed in UNIX Timestamp)
     * @var int
     */
    protected $expire = null;
    /**
     * Restrict cookie path
     * @var string
     */
    protected $path = '';
    /**
     * @var string
     */
    protected $keyStore = false;
    /**
     * Restrict cookie to a domain (or subdomains)
     * @var string
     */
    protected $domain = '';
    /**
     * If true, cookie should only be transmitted over secure HTTPS
     * @var bool
     */
    protected $secure = false;
    /**
     * If true, cookie will only be made available via the HTTP protocol.
     * Note: not well supported by browsers.
     * @var bool
     */
    protected $httponly = false;
    /**
     * The content of the cookie
     * @var array
     */
    protected $value = array();
    /**
     * The character used to separate the tuple name=value in the cookie
     */
    const VALUE_SEPARATOR = ':';
    /**
     * Instantiate a new Cookie object and tries to load the cookie content if the cookie
     * exists already.
     *
     * @param string $cookieName cookie Name
     * @param int|string $expire The timestamp after which the cookie will expire, eg time() + 86400;
     *                                  use 0 (int zero) to expire cookie at end of browser session
     * @param string $path The path on the server in which the cookie will be available on.
     * @param bool|string $keyStore Will be used to store several bits of data (eg. one array per website)
     */
    public function __construct($cookieName, $expire = null, $path = null, $keyStore = false)
    {
        $this->name = $cookieName;
        $this->path = $path;
        $this->expire = $expire;
        $this->keyStore = $keyStore;
        if ($this->isCookieFound()) {
            $this->loadContentFromCookie();
        }
    }
    /**
     * Returns true if the visitor already has the cookie.
     *
     * @return bool
     */
    public function isCookieFound()
    {
        return self::isCookieInRequest($this->name);
    }
    /**
     * Returns the default expiry time, 2 years
     *
     * @return int  Timestamp in 2 years
     */
    protected function getDefaultExpire()
    {
        return time() + 86400 * 365 * 2;
    }
    /**
     * setcookie() replacement -- we don't use the built-in function because
     * it is buggy for some PHP versions.
     *
     * @link http://php.net/setcookie
     *
     * @param string $Name Name of cookie
     * @param string $Value Value of cookie
     * @param int|string $Expires Time the cookie expires
     * @param string $Path
     * @param string $Domain
     * @param bool $Secure
     * @param bool $HTTPOnly
     * @param string $sameSite
     */
    protected function setCookie($Name, $Value, $Expires, $Path = '', $Domain = '', $Secure = false, $HTTPOnly = false, $sameSite = false)
    {
        if (!empty($Domain)) {
            // Fix the domain to accept domains with and without 'www.'.
            if (!strncasecmp($Domain, 'www.', 4)) {
                $Domain = substr($Domain, 4);
            }
            $Domain = '.' . $Domain;
            // Remove port information.
            $Port = strpos($Domain, ':');
            if ($Port !== false) {
                $Domain = substr($Domain, 0, $Port);
            }
        }
        // Format expire time only for non session cookies
        if (0 !== $Expires) {
            $Expires = $this->formatExpireTime($Expires);
        }
        $header = 'Set-Cookie: ' . rawurlencode($Name) . '=' . rawurlencode($Value) . (empty($Expires) ? '' : '; expires=' . $Expires) . (empty($Path) ? '' : '; path=' . $Path) . (empty($Domain) ? '' : '; domain=' . rawurlencode($Domain)) . (!$Secure ? '' : '; secure') . (!$HTTPOnly ? '' : '; HttpOnly') . (!$sameSite ? '' : '; SameSite=' . rawurlencode($sameSite));
        \Piwik\Common::sendHeader($header, false);
    }
    /**
     * We set the privacy policy header
     */
    protected function setP3PHeader()
    {
        \Piwik\Common::sendHeader("P3P: CP='OTI DSP COR NID STP UNI OTPa OUR'");
    }
    /**
     * Delete the cookie
     */
    public function delete()
    {
        $this->setP3PHeader();
        $this->setCookie($this->name, 'deleted', time() - 31536001, $this->path, $this->domain);
        $this->setCookie($this->name, 'deleted', time() - 31536001, $this->path, $this->domain, TRUE, FALSE, 'None');
        $this->clear();
    }
    /**
     * Saves the cookie (set the Cookie header).
     * You have to call this method before sending any text to the browser or you would get the
     * "Header already sent" error.
     * @param string $sameSite Value for SameSite cookie property
     */
    public function save($sameSite = null)
    {
        if ($sameSite) {
            $sameSite = self::getSameSiteValueForBrowser($sameSite);
        }
        $cookieString = $this->generateContentString();
        if (strlen($cookieString) > self::MAX_COOKIE_SIZE) {
            // If the cookie was going to be too large, instead, delete existing cookie and start afresh
            $this->delete();
            return;
        }
        $this->setP3PHeader();
        $this->setCookie($this->name, $cookieString, $this->expire, $this->path, $this->domain, $this->secure, $this->httponly, $sameSite);
    }
    /**
     * Extract signed content from string: content VALUE_SEPARATOR '_=' signature
     * Only needed for BC.
     *
     * @param string $content
     * @return string|bool  Content or false if unsigned
     */
    private function extractSignedContent($content)
    {
        $signature = substr($content, -40);
        if (substr($content, -43, 3) === self::VALUE_SEPARATOR . '_=' && $signature === sha1(substr($content, 0, -40) . \Piwik\SettingsPiwik::getSalt())) {
            // strip trailing: VALUE_SEPARATOR '_=' signature"
            return substr($content, 0, -43);
        }
        return false;
    }
    /**
     * Load the cookie content into a php array.
     * Parses the cookie string to extract the different variables.
     * Unserialize the array when necessary.
     * Decode the non numeric values that were base64 encoded.
     */
    protected function loadContentFromCookie()
    {
        // we keep trying to read signed content for BC ... if it detects a correctly signed cookie then we read
        // this value
        $cookieStr = $this->extractSignedContent($_COOKIE[$this->name]);
        $isSigned = !empty($cookieStr);
        if ($cookieStr === false && !empty($_COOKIE[$this->name]) && strpos($_COOKIE[$this->name], '=') !== false) {
            // cookie was set since Matomo 4
            $cookieStr = $_COOKIE[$this->name];
        }
        if ($cookieStr === false) {
            return;
        }
        $values = explode(self::VALUE_SEPARATOR, $cookieStr);
        foreach ($values as $nameValue) {
            $equalPos = strpos($nameValue, '=');
            $varName = substr($nameValue, 0, $equalPos);
            $varValue = substr($nameValue, $equalPos + 1);
            if (!is_numeric($varValue)) {
                $tmpValue = base64_decode($varValue);
                if ($isSigned) {
                    // only unserialise content if it was signed meaning the cookie was generated pre Matomo 4
                    $varValue = safe_unserialize($tmpValue);
                } else {
                    $varValue = $tmpValue;
                }
                // discard entire cookie
                // note: this assumes we never serialize a boolean
                // can only happen when it was signed pre Matomo 4
                if ($varValue === false && $tmpValue !== 'b:0;') {
                    $this->value = array();
                    unset($_COOKIE[$this->name]);
                    break;
                }
            }
            $this->value[$varName] = $varValue;
        }
    }
    /**
     * Returns the string to save in the cookie from the $this->value array of values.
     * It goes through the array and generates the cookie content string.
     *
     * @return string  Cookie content
     */
    public function generateContentString()
    {
        $cookieStrArr = [];
        foreach ($this->value as $name => $value) {
            if (!is_numeric($value) && !is_string($value)) {
                throw new \Exception('Only strings and numbers can be used in cookies. Value is of type ' . gettype($value));
            } elseif (!is_numeric($value)) {
                $value = base64_encode($value);
            }
            $cookieStrArr[] = "{$name}={$value}";
        }
        return implode(self::VALUE_SEPARATOR, $cookieStrArr);
    }
    /**
     * Set cookie domain
     *
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
    /**
     * Set secure flag
     *
     * @param bool $secure
     */
    public function setSecure($secure)
    {
        $this->secure = $secure;
    }
    /**
     * Set HTTP only
     *
     * @param bool $httponly
     */
    public function setHttpOnly($httponly)
    {
        $this->httponly = $httponly;
    }
    /**
     * Registers a new name => value association in the cookie.
     *
     * Registering new values is optimal if the value is a numeric value.
     * Only numbers and strings can be saved in the cookie.
     * A cookie has to stay small and its size shouldn't increase over time!
     *
     * @param string $name Name of the value to save; the name will be used to retrieve this value
     * @param string|number $value Value to save. If null, entry will be deleted from cookie.
     */
    public function set($name, $value)
    {
        $name = self::escapeValue($name);
        // Delete value if $value === null
        if (is_null($value)) {
            if ($this->keyStore === false) {
                unset($this->value[$name]);
                return;
            }
            unset($this->value[$this->keyStore][$name]);
            return;
        }
        if ($this->keyStore === false) {
            $this->value[$name] = $value;
            return;
        }
        $this->value[$this->keyStore][$name] = $value;
    }
    /**
     * Returns the value defined by $name from the cookie.
     *
     * @param string|integer Index name of the value to return
     * @return mixed  The value if found, false if the value is not found
     */
    public function get($name)
    {
        $name = self::escapeValue($name);
        if (false === $this->keyStore) {
            if (isset($this->value[$name])) {
                return self::escapeValue($this->value[$name]);
            }
            return false;
        }
        if (isset($this->value[$this->keyStore][$name])) {
            return self::escapeValue($this->value[$this->keyStore][$name]);
        }
        return false;
    }
    /**
     * Removes all values from the cookie.
     */
    public function clear()
    {
        $this->value = [];
    }
    /**
     * Returns an easy to read cookie dump
     *
     * @return string  The cookie dump
     */
    public function __toString()
    {
        $str = 'COOKIE ' . $this->name . ', rows count: ' . count($this->value) . ', cookie size = ' . strlen($this->generateContentString()) . " bytes, ";
        $str .= 'path: ' . $this->path . ', expire: ' . $this->expire . "\n";
        $str .= var_export($this->value, $return = true);
        return $str;
    }
    /**
     * Escape values from the cookie before sending them back to the client
     * (when using the get() method).
     *
     * @param string $value Value to be escaped
     * @return mixed  The value once cleaned.
     */
    protected static function escapeValue($value)
    {
        return \Piwik\Common::sanitizeInputValues($value);
    }
    /**
     * Returns true if a cookie named '$name' is in the current HTTP request,
     * false if otherwise.
     *
     * @param string $name the name of the cookie
     * @return boolean
     */
    public static function isCookieInRequest($name)
    {
        return isset($_COOKIE[$name]);
    }
    /**
     * Find the most suitable value for a cookie SameSite attribute, given environmental restrictions which
     * may make the most "correct" value impractical:
     * - On Chrome, the "None" value means that the cookie will not be present on third-party sites (e.g. the site
     * that is being tracked) when the site is loaded over HTTP.  This means that important cookies which should always
     * be present (e.g. the opt-out cookie) won't be there at all. Using "Lax" means that at least they will be there
     * for some requests which are deemed CSRF-safe, although other requests may have broken functionality.
     * - On Safari, the "None" value is interpreted as "Strict".  In order to set a cookie which will be available
     * in all third-party contexts, we have to omit the SameSite attribute altogether.
     * @param string $default The desired SameSite value that we should use if it won't cause any problems.
     * @return string SameSite attribute value that should be set on the cookie. Empty string indicates that no value
     * should be set.
     */
    private static function getSameSiteValueForBrowser($default)
    {
        $sameSite = ucfirst(strtolower($default));
        if ($sameSite === 'None') {
            if (!\Piwik\ProxyHttp::isHttps()) {
                $sameSite = 'Lax';
                // None can be only used when secure flag will be set
            } else {
                $userAgent = \Piwik\Http::getUserAgent();
                $ddFactory = StaticContainer::get(\Piwik\DeviceDetector\DeviceDetectorFactory::class);
                $deviceDetector = $ddFactory->makeInstance($userAgent, \Piwik\Http::getClientHintsFromServerVariables());
                $deviceDetector->parse();
                $browserFamily = \DeviceDetector\Parser\Client\Browser::getBrowserFamily($deviceDetector->getClient('short_name'));
                if ($browserFamily === 'Safari') {
                    $sameSite = '';
                }
            }
        }
        return $sameSite;
    }
    /**
     *  extend Cookie by timestamp or sting like + 30 years, + 10 months, default 2 years
     * @param $time
     * @return string
     */
    public function formatExpireTime($time = null)
    {
        $expireTime = new DateTime();
        if (is_null($time) || is_int($time) && $time < 0) {
            $expireTime->modify("+2 years");
        } else {
            if (is_int($time)) {
                $expireTime->setTimestamp($time);
            } else {
                if (!$expireTime->modify($time)) {
                    $expireTime->modify("+2 years");
                }
            }
        }
        return $expireTime->format(DateTime::COOKIE);
    }
}
