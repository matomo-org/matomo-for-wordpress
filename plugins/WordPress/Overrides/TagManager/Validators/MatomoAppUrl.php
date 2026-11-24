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

/** Restricts a Tag Manager Matomo base URL to this installation's application path and origin. */
class MatomoAppUrl extends BaseValidator
{
    public function validate($value)
    {
        if ($this->isValueBare($value)) {
            return;
        }

        (new SiteOwnUrl())->validate($value);

        if (!is_string($value) || !$this->hasAllowedOrigin($value)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerMatomoUrlNotAllowed'));
        }

        $parts = wp_parse_url($value);
        $path = isset($parts['path']) ? rawurldecode($parts['path']) : '';
        $expectedPath = $this->getMatomoAppPath();
        $withoutTrailingSlash = rtrim($expectedPath, '/');

        if ('' === $expectedPath || ($path !== $expectedPath && $path !== $withoutTrailingSlash)) {
            throw new Exception(Piwik::translate('WordPress_TagManagerMatomoUrlNotAllowed'));
        }
    }

    /**
     * @param string $value
     * @return bool
     */
    private function hasAllowedOrigin($value)
    {
        $parts = wp_parse_url($value);

        if (empty($parts['host'])) {
            // A root-relative path uses the page's origin. Page-relative and protocol-relative
            // values need an explicit host check below.
            return isset($parts['path']) && 0 === strpos($parts['path'], '/') && 0 !== strpos($parts['path'], '//');
        }

        $host = rtrim(strtolower($parts['host']), '.');
        $scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';

        foreach ([home_url(), site_url()] as $siteUrl) {
            $site = wp_parse_url($siteUrl);
            if (empty($site['host']) || rtrim(strtolower($site['host']), '.') !== $host) {
                continue;
            }

            if ('' !== $scheme && strtolower($site['scheme']) !== $scheme) {
                continue;
            }

            if (isset($parts['port'])) {
                $sitePort = isset($site['port']) ? (int) $site['port'] : $this->defaultPort(strtolower($site['scheme']));
                if ((int) $parts['port'] !== $sitePort) {
                    continue;
                }
            } elseif ('' !== $scheme) {
                $sitePort = isset($site['port']) ? (int) $site['port'] : $this->defaultPort(strtolower($site['scheme']));
                if ($sitePort !== $this->defaultPort($scheme)) {
                    continue;
                }
            } else {
                // A protocol-relative URL without a port resolves to the scheme's default port;
                // it does not inherit a nonstandard port from the current page.
                $siteScheme = strtolower($site['scheme']);
                $sitePort = isset($site['port']) ? (int) $site['port'] : $this->defaultPort($siteScheme);
                if ($sitePort !== $this->defaultPort($siteScheme)) {
                    continue;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param string $scheme
     * @return int
     */
    private function defaultPort($scheme)
    {
        return 'https' === $scheme ? 443 : 80;
    }

    /**
     * @return string
     */
    private function getMatomoAppPath()
    {
        if (!defined('MATOMO_ANALYTICS_FILE')) {
            return '';
        }

        $url = plugins_url('app', MATOMO_ANALYTICS_FILE);
        $path = wp_parse_url($url, PHP_URL_PATH);

        return is_string($path) && '' !== $path ? rtrim(rawurldecode($path), '/') . '/' : '';
    }
}
