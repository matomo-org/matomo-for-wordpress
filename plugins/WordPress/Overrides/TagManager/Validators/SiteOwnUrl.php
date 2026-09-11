<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager\Validators;

use Piwik\Piwik;
use Piwik\Validators\BaseValidator;
use Piwik\Validators\Exception;

/**
 * Constrains a Tag Manager URL parameter to this WordPress site's host.
 *
 * Several parameters become an origin the browser then fetches from — MatomoConfiguration's
 * matomoUrl (`g.src = url + jsEndpoint` in MatomoTag.web.js), LivezillaDynamicTag's domain, and
 * CustomImageTag's image source. Each of them is free text with at most a NotEmpty()
 * check. An arbitrary origin in the first two is arbitrary JavaScript on every frontend page.
 *
 * This narrows rather than refuses: the value these parameters are normally given already points
 * at the site itself, so a legitimate configuration still saves.
 */
class SiteOwnUrl extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            // whether an empty value is acceptable is NotEmpty()'s decision, not ours
            return;
        }

        if (!is_string($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
        }

        (new NoVariableInterpolation())->validate($value);

        // browsers normalise backslashes to forward slashes and strip control characters and
        // whitespace before parsing a URL, so "\\evil.example" and "htt\nps://evil.example"
        // reach an origin that parse_url() does not report
        if (preg_match('/[\\\\\x00-\x20\x7f]/', $value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
        }

        $host = $this->parseHost($value);
        if ($host === null) {
            // no host is allowed since the browser will resolve it against the page's host
            return;
        }

        $allowed = $this->getSiteHosts();
        if (!in_array($host, $allowed, true)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlHostNotAllowed', [$host, implode(', ', $allowed)]));
        }
    }

    /**
     * @param string $url
     * @return string|null the normalised host, or null when the value addresses no host of its own
     * @throws Exception
     */
    private function parseHost($url)
    {
        if (strpos($url, '//') === 0) {
            // protocol relative, which is the form the default value takes
            $url = 'https:' . $url;
        } elseif (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url)) {
            // a scheme is present. it has to be http(s), and it has to be followed by "//":
            // parse_url() reports no host for "https:evil.example", but a browser reads that as
            // https://evil.example/
            if (!preg_match('~^https?://~i', $url)) {
                throw new Exception(Piwik::translate('WordPress_TagManagerUrlSchemeNotAllowed'));
            }
        } else {
            return null;
        }

        $host = wp_parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
        }

        return $this->normaliseHost($host);
    }

    /**
     * @return string[]
     */
    private function getSiteHosts()
    {
        $hosts = [];

        // note: home_url() and site_url() do not necessarily have to be the same
        foreach ([home_url(), site_url()] as $site_url) {
            $host = wp_parse_url($site_url, PHP_URL_HOST);
            if (!empty($host)) {
                $hosts[] = $this->normaliseHost($host);
            }
        }

        return array_values(array_unique($hosts));
    }

    /**
     * @param string $host
     * @return string
     */
    private function normaliseHost($host)
    {
        // hosts are case insensitive, and a trailing dot addresses the same name
        return rtrim(strtolower($host), '.');
    }
}
