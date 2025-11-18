<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Html;

use Piwik\Common;

class PluginUrlReplacer
{
    const CONTENT_TYPE_HTML = 'html';
    const CONTENT_TYPE_LESS = 'less';
    // TODO: rewrite this class so we only do one preg_replace_callback

    public function replaceUrls(string $matomoUrl, string $content, string $contentType): string
    {
        // TODO: rename forEachUrl
        $this->forEachUrlReplaceWith($content, $contentType, function ($quote, $url) use ($matomoUrl) {
            if (preg_match('%^(' . preg_quote($matomoUrl, '%') . ')?/*index\.php%', $url)) {
                $rewritten = $this->rewriteMatomoPathToWpAdmin($url);
                if ($rewritten) {
                    return $quote . $rewritten . $quote;
                }
            }

            // TODO: comment
            $path = parse_url( $url, PHP_URL_PATH );
            if (is_file(PIWIK_INCLUDE_PATH . '/' . $path)) {
                return $quote . plugins_url( '/app/' . $url, MATOMO_ANALYTICS_FILE ) . $quote;
            }
            if (is_file(dirname(MATOMO_ANALYTICS_FILE) . '/' . $path)) {
                return $quote . plugins_url( '/' . $url, MATOMO_ANALYTICS_FILE ) . $quote;
            }

            // TODO: comment, or refactor
            if (preg_match('%^(?:\./)?plugins/%', $url)) {
                $replace = $this->rewritePathIfThirdPartyPluginUrl($url);
                if (!empty($replace)) {
                    return $quote . $replace . $quote;
                }
            }

            return null;
        });

        return $content;
    }

    public function replaceThirdPartyPluginUrls(string $html): string
    {
        // replace all links to third party Matomo plugin files with their proper WordPress URLs
        $html = preg_replace_callback(
            '%\"((?:\\./)?plugins/.*?)\"%',
            function ($matches) {
                // $url looks like plugins/SearchEngineKeywordsPerformance/images/...
                $url = $matches[1];
                $replace = $this->rewritePathIfThirdPartyPluginUrl($url);
                if (!empty($replace)) {
                    return '"' . $replace . '"';
                }

                return $matches[0];
            },
            $html
        );

        // replace URLs to third party Matomo plugin files in JSON values (used to initiate Vue components)
        $html = preg_replace_callback(
            '%&quot;(?:\\.\\\\/)?plugins\\\\/.*?&quot;%',
            function ($matches) {
                // $url looks like plugins/SearchEngineKeywordsPerformance/images/...
                $url = Common::unsanitizeInputValue( $matches[0] );
                $url = json_decode($url, true);
                if (empty($url) || !is_string($url)) { // sanity check
                    return $matches[0];
                }

                $replace = $this->rewritePathIfThirdPartyPluginUrl($url);
                if (!empty($replace)) {
                    $replace = json_encode($replace);
                    $replace = Common::sanitizeInputValue($replace);
                    return $replace;
                }

                return $matches[0];
            },
            $html
        );

        return $html;
    }

    // TODO: docs + test
    public function replaceIndexPhpUrlsToMwpReporting(string $matomoUrl, string $html): string
    {
        // replace all links to index.php? to admin.php?page=matomo-reporting&...

        // TODO: handle single quote strings in above code too
        $html = preg_replace_callback(
            '%=\s*(["\'])((' . preg_quote( $matomoUrl ) . ')?/*(index\.php)?[^/]*?)\1%',
            function ($matches) {
                $url = Common::unsanitizeInputValue( $matches[2] );

                $replace = $this->rewriteMatomoPathToWpAdmin($url);
                if (!empty($replace)) {
                    return '=' . $matches[1] . $replace . $matches[1];
                }

                return $matches[0];
            },
            $html
        );

        $jsonMatomoUrl = json_encode($matomoUrl);
        $jsonMatomoUrl = substr($jsonMatomoUrl, 1, strlen($jsonMatomoUrl) - 2);

        // replace URLs to Matomo in JSON values (used to initiate Vue components)
        $html = preg_replace_callback(
            '%&quot;(' . preg_quote($jsonMatomoUrl) . ')?(?:\\\\/)*(index\.php)\?[^/]*?&quot;%',
            function ($matches) {
                // $url looks like plugins/SearchEngineKeywordsPerformance/images/...
                $url = Common::unsanitizeInputValue( $matches[0] );
                $url = json_decode($url, true);
                if (empty($url) || !is_string($url)) { // sanity check
                    return $matches[0];
                }

                $replace = $this->rewriteMatomoPathToWpAdmin($url);
                if (!empty($replace)) {
                    $replace = json_encode($replace);
                    $replace = Common::sanitizeInputValue($replace);
                    return $replace;
                }

                return $matches[0];
            },
            $html
        );

        return $html;
    }

    private function rewritePathIfThirdPartyPluginUrl(string $url): ?string
    {
        // TODO: rename method since it renames for core plugins too

        if (substr($url, 0, 2) === './') {
            $url = substr($url, 2);
        }

        $segments = explode('/', $url);
        $plugin = $segments[1] ?? '';

        // entries in this array will look like:
        // /path/to/wordpress/wp-content/plugins/SearchEngineKeywordsPerformance/SearchEngineKeywordsPerformance.php
        $allPluginsInstalledInWp = $GLOBALS['MATOMO_PLUGIN_FILES'] ?? [];
        foreach ($allPluginsInstalledInWp as $matomoPluginFile) {
            if (basename($matomoPluginFile) === $plugin . '.php') {
                array_shift($segments);
                array_shift($segments);
                $urlRelativeToPlugin = implode('/', $segments);

                $replace = plugins_url($urlRelativeToPlugin, $matomoPluginFile);
                return $replace;
            }
        }

        return null;
    }

    private function rewriteMatomoPathToWpAdmin(string $url): ?string
    {
        if (substr($url, 0, 2) === './') {
            $url = substr($url, 2);
        }

        $url = ltrim($url, '/');

        // check if it is a valid URL
        $urlParts = wp_parse_url($url);
        if ($urlParts === false) {
            return null;
        }

        // check if it looks like a Matomo URL (has a module query param)
        parse_str($urlParts['query'] ?? '', $query);
        if (empty($query['module'])) {
            return null;
        }

        $query['page'] = 'matomo-reporting';

        $newQuery = http_build_query($query);

        return home_url( '/wp-admin/admin.php' ) . '?' . $newQuery;
    }

    private function forEachUrlReplaceWith(string &$content, string $contentType, callable $fn)
    {
        if ($contentType === self::CONTENT_TYPE_HTML) {
            $regex = '%([\'"]|&quot;)[^\s\'"})]*?\1%';
        } else if ($contentType === self::CONTENT_TYPE_LESS) {
            $regex = '%url\(([\'"]?)[^\s\'"})]*?\1\)%';
        } else {
            throw new \InvalidArgumentException('contentType ' . $contentType . ' not recognized');
        }

        $content = preg_replace_callback(
            $regex,
            function ($matches) use ($fn, $contentType) {
                $url = $matches[0];
                if (strlen($url) < 5) {
                    return $matches[0];
                }

                $quote = $matches[1];

                if ($contentType === self::CONTENT_TYPE_HTML) {
                    $url = Common::unsanitizeInputValue($url);

                    if ($quote === '&quot;') {
                        $url = json_decode($url, true);
                    } else {
                        $url = substr($url, 1, strlen($url) - 2);
                    }
                } else if ($contentType === self::CONTENT_TYPE_LESS) {
                    $url = substr($url, 4, strlen($url) - 5); // remove url( ... )
                    if (!empty($quote)) {
                        $url = substr($url, 1, strlen($url) - 2); // remove ['"] ... ['"]
                    }
                }

                $replace = $fn($matches[1], $url);
                if ($replace === null) {
                    return $matches[0];
                }

                if ($contentType === self::CONTENT_TYPE_LESS) {
                    $replace = 'url(' . $replace . ')';
                }

                return $replace;
            },
            $content
        );
    }
}
