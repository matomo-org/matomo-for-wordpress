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
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
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
     * The Matomo Configuration variable, with the parameters that decide which origin the tracker
     * is loaded from constrained to this site.
     *
     * This one is narrowed rather than refused for the additional reason that it is the variable
     * Tag Manager users need most.
     *
     * @param MatomoConfigurationVariable $wrapped the instance this one stands in for
     * @return MatomoConfigurationVariable
     */
    public function matomoConfigurationVariable(MatomoConfigurationVariable $wrapped)
    {
        return new class(
            $wrapped,
            [
                'matomoUrl' => [new SiteOwnUrl()],
                'jsEndpointCustom' => [new NoVariableInterpolation(), new NoPathTraversal(), new TrackerEndpointPath()],
                'trackingEndpointCustom' => [new NoVariableInterpolation(), new NoPathTraversal(), new TrackerEndpointPath()],
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
     * Replaces a default URL that SiteOwnUrl would refuse with one this site does answer to.
     *
     * @return \Closure
     */
    private static function siteOwnDefaultUrl()
    {
        return function ($default) {
            foreach (self::siteOwnUrlCandidates($default) as $candidate) {
                if (self::isSiteOwnUrl($candidate)) {
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
    private static function isSiteOwnUrl($value)
    {
        if (!is_string($value) || '' === $value) {
            return false;
        }

        try {
            (new SiteOwnUrl())->validate($value);
        } catch (ValidatorException $e) {
            return false;
        }

        return true;
    }
}
