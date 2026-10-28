<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Plugins\TagManager\Template\Tag\CustomHtmlTag;
use Piwik\Plugins\TagManager\Template\Tag\CustomImageTag;
use Piwik\Plugins\TagManager\Template\Variable\CustomJsFunctionVariable;
use Piwik\Plugins\TagManager\Template\Variable\CustomRequestProcessingVariable;
use Piwik\Plugins\TagManager\Template\Variable\JavaScriptVariable;
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\MatomoAppUrl;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\NoAuthenticationParameters;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\NoPathTraversal;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\NoVariableInterpolation;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\SiteOwnUrl;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\TrackerEndpointPath;
use Piwik\Plugins\WordPress\Overrides\TagManager\Validators\UnfilteredHtmlRequired;
use Piwik\Validators\Exception as ValidatorException;

/**
 * Creates Tag Manager template overrides with extra field validations for use
 * by users without the `unfiltered_html` WordPress capability.
 */
class SecuredTemplateFactory
{
    /**
     * Whether the additional Matomo URL and tracker-resource restrictions are enabled.
     *
     * Set MATOMO_TAG_MANAGER_RESTRICT_URLS to false in wp-config.php, or use the
     * matomo_tag_manager_restrict_urls filter to support custom tracker deployments.
     * Restrictions are enabled by default. Other security validations remain active.
     *
     * @return bool
     */
    private static function restrictMatomoUrls()
    {
        $enabled = !defined('MATOMO_TAG_MANAGER_RESTRICT_URLS') || MATOMO_TAG_MANAGER_RESTRICT_URLS;

        return (bool) apply_filters('matomo_tag_manager_restrict_urls', $enabled);
    }

    /**
     * @return array<class-string, callable>
     */
    public function getTagReplacements()
    {
        return [
            CustomHtmlTag::class => [$this, 'customHtmlTag'],
            CustomImageTag::class => [$this, 'customImageTag'],
        ];
    }

    /**
     * @return array<class-string, callable>
     */
    public function getVariableReplacements()
    {
        return [
            MatomoConfigurationVariable::class => [$this, 'matomoConfigurationVariable'],
            JavaScriptVariable::class => [$this, 'javaScriptVariable'],
            CustomJsFunctionVariable::class => [$this, 'customJsFunctionVariable'],
            CustomRequestProcessingVariable::class => [$this, 'customRequestProcessingVariable'],
        ];
    }

    /**
     * Prevent use of the custom HTML tag entirely. Note: if the user has `unfiltered_html`,
     * this override will not be registered (see WordPress.php).
     *
     * @param CustomHtmlTag $wrapped the instance this one stands in for
     * @return CustomHtmlTag
     */
    public function customHtmlTag(CustomHtmlTag $wrapped)
    {
        return new class(
            $wrapped,
            ['customHtml' => [new UnfilteredHtmlRequired()]]
        ) extends CustomHtmlTag {
            use SecuredTemplate;
        };
    }

    /**
     * A custom image, with its source constrained to this site. Used to prevent
     * exfiltration to another domain.
     *
     * @param CustomImageTag $wrapped the instance this one stands in for
     * @return CustomImageTag
     */
    public function customImageTag(CustomImageTag $wrapped)
    {
        return new class(
            $wrapped,
            ['customImageSrc' => [new SiteOwnUrl()]]
        ) extends CustomImageTag {
            use SecuredTemplate;
        };
    }

    /**
     * The Matomo Configuration variable, with the tracker base and endpoints constrained to this
     * Matomo for WordPress installation.
     *
     * This one is narrowed rather than refused for the additional reason that it is the variable
     * Tag Manager users need most.
     *
     * @param MatomoConfigurationVariable $wrapped the instance this one stands in for
     * @return MatomoConfigurationVariable
     */
    public function matomoConfigurationVariable(MatomoConfigurationVariable $wrapped)
    {
        $restrictUrls = self::restrictMatomoUrls();
        $urlValidators = $restrictUrls ? [new MatomoAppUrl()] : [];
        $endpointValidators = $restrictUrls ? [new TrackerEndpointPath()] : [];

        return new class(
            $wrapped,
            [
                'matomoUrl' => $urlValidators,
                'jsEndpointCustom' => array_merge([new NoVariableInterpolation(), new NoPathTraversal()], $endpointValidators),
                'trackingEndpointCustom' => array_merge([new NoVariableInterpolation(), new NoPathTraversal()], $endpointValidators),
                'appendToTrackingUrl' => [new NoVariableInterpolation(), new NoAuthenticationParameters()],
            ],
            [
                'matomoUrl' => self::dropUrlQueryAndFragment(),
                'jsEndpointCustom' => self::relativeTrackerEndpoint(),
                'trackingEndpointCustom' => self::relativeTrackerEndpoint(),
            ],
            ['matomoUrl' => self::siteOwnDefaultUrl()]
        ) extends MatomoConfigurationVariable {
            use SecuredTemplate;
        };
    }

    /**
     * Prevent arbitrary reads from the visitor's global JavaScript namespace. In WordPress this
     * includes REST nonces and other credentials used by same-origin authenticated requests.
     *
     * @param JavaScriptVariable $wrapped
     * @return JavaScriptVariable
     */
    public function javaScriptVariable(JavaScriptVariable $wrapped)
    {
        return new class(
            $wrapped,
            ['variableName' => [new UnfilteredHtmlRequired()]]
        ) extends JavaScriptVariable {
            use SecuredTemplate;
        };
    }

    /**
     * Prevent use of custom JavaScript function bodies. Note: if the user has
     * `unfiltered_html`, this override will not be registered (see WordPress.php).
     *
     * @param CustomJsFunctionVariable $wrapped the instance this one stands in for
     * @return CustomJsFunctionVariable
     */
    public function customJsFunctionVariable(CustomJsFunctionVariable $wrapped)
    {
        return new class(
            $wrapped,
            ['jsFunction' => [new UnfilteredHtmlRequired()]]
        ) extends CustomJsFunctionVariable {
            use SecuredTemplate;
        };
    }

    /**
     * Prevent use of custom JavaScript functions run for tracking requests. Note:
     * if the user has `unfiltered_html`, this override will not be registered (see
     * WordPress.php).
     *
     * @param CustomRequestProcessingVariable $wrapped the instance this one stands in for
     * @return CustomRequestProcessingVariable
     */
    public function customRequestProcessingVariable(CustomRequestProcessingVariable $wrapped)
    {
        return new class(
            $wrapped,
            ['jsFunction' => [new UnfilteredHtmlRequired()]]
        ) extends CustomRequestProcessingVariable {
            use SecuredTemplate;
        };
    }

    /**
     * @return \Closure
     */
    private static function dropUrlQueryAndFragment()
    {
        return function ($value) {
            if (!is_string($value)) {
                return $value;
            }

            return preg_replace('/[?#].*$/s', '', $value);
        };
    }

    /**
     * @return \Closure
     */
    private static function relativeTrackerEndpoint()
    {
        $dropUrlQueryAndFragment = self::dropUrlQueryAndFragment();

        return function ($value) use ($dropUrlQueryAndFragment) {
            $value = call_user_func($dropUrlQueryAndFragment, $value);

            if (!is_string($value)) {
                return $value;
            }

            return ltrim($value, '/');
        };
    }

    /**
     * Replaces a default URL that MatomoAppUrl would refuse with this installation's Matomo app.
     *
     * @return \Closure
     */
    private static function siteOwnDefaultUrl()
    {
        return function ($default) {
            foreach (self::siteOwnUrlCandidates($default) as $candidate) {
                if (self::isSiteMatomoAppUrl($candidate)) {
                    return $candidate;
                }
            }

            // can't find a correct URL, leave the default which will be refused with a message
            return $default;
        };
    }

    /**
     * @param mixed $default what Matomo proposes for the field
     * @return string[] URLs to propose instead, best first
     */
    private static function siteOwnUrlCandidates($default)
    {
        $candidates = [];

        if (is_string($default)) {
            $candidates[] = $default;
            $candidates[] = wp_make_link_relative($default);
        }

        if (defined('MATOMO_ANALYTICS_FILE')) {
            $candidates[] = wp_make_link_relative(rtrim(plugins_url('app', MATOMO_ANALYTICS_FILE), '/') . '/');
        }

        return $candidates;
    }

    /**
     * @param mixed $value
     * @return bool whether SiteOwnUrl accepts the value as a URL of this site
     */
    private static function isSiteMatomoAppUrl($value)
    {
        if (!is_string($value) || '' === $value) {
            return false;
        }

        try {
            (new MatomoAppUrl())->validate($value);
        } catch (ValidatorException $e) {
            return false;
        }

        return true;
    }
}
