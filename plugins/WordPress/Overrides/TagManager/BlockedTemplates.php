<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\TagManager\Template\Tag\AddThisTag;
use Piwik\Plugins\TagManager\Template\Tag\AxeptioTag;
use Piwik\Plugins\TagManager\Template\Tag\BingUETTag;
use Piwik\Plugins\TagManager\Template\Tag\BugsnagTag;
use Piwik\Plugins\TagManager\Template\Tag\CookieYesTag;
use Piwik\Plugins\TagManager\Template\Tag\CookiebotTag;
use Piwik\Plugins\TagManager\Template\Tag\DriftTag;
use Piwik\Plugins\TagManager\Template\Tag\EmarsysTag;
use Piwik\Plugins\TagManager\Template\Tag\EtrackerTag;
use Piwik\Plugins\TagManager\Template\Tag\FacebookPixelTag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleAdsConversionTag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleAnalytics4Tag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleAnalyticsUniversalTag;
use Piwik\Plugins\TagManager\Template\Tag\GoogleTagTag;
use Piwik\Plugins\TagManager\Template\Tag\HoneybadgerTag;
use Piwik\Plugins\TagManager\Template\Tag\HotjarTag;
use Piwik\Plugins\TagManager\Template\Tag\LinkedinInsightTag;
use Piwik\Plugins\TagManager\Template\Tag\LivezillaDynamicTag;
use Piwik\Plugins\TagManager\Template\Tag\OneTrustTag;
use Piwik\Plugins\TagManager\Template\Tag\PingdomRUMTag;
use Piwik\Plugins\TagManager\Template\Tag\RaygunTag;
use Piwik\Plugins\TagManager\Template\Tag\SentryRavenTag;
use Piwik\Plugins\TagManager\Template\Tag\ShareaholicTag;
use Piwik\Plugins\TagManager\Template\Tag\TawkToTag;
use Piwik\Plugins\TagManager\Template\Tag\VisualWebsiteOptimizerTag;
use Piwik\Plugins\TagManager\Template\Tag\ZendeskChatTag;
use Piwik\Validators\Exception as ValidatorException;

/**
 * The Tag Manager tag templates a user without the `unfiltered_html` WordPress capability may not
 * add to a container, and may not change in a container that already uses them.
 *
 * These templates are blocked for one or more of the following reasons:
 * - the template can load arbitrary JavaScript from another domain
 * - the template loads fixed JavaScript from another domain, but that JavaScript is used to
 *   collect data about the website's visitors
 * - the template interacts with another tag resulting in visitor data being sent to a 3rd party
 *   site/account (GoogleAdsConversionTag)
 *
 * Deciding which other services a site talks to or integrates with is a decision that belongs to
 * whoever is responsible for the site.
 */
class BlockedTemplates
{
    /**
     * @var class-string[]
     */
    public const TAGS = [
        AddThisTag::class,
        AxeptioTag::class,
        BingUETTag::class,
        BugsnagTag::class,
        CookieYesTag::class,
        CookiebotTag::class,
        DriftTag::class,
        EmarsysTag::class,
        EtrackerTag::class,
        FacebookPixelTag::class,
        GoogleAdsConversionTag::class,
        GoogleAnalytics4Tag::class,
        GoogleAnalyticsUniversalTag::class,
        GoogleTagTag::class,
        HoneybadgerTag::class,
        HotjarTag::class,
        LinkedinInsightTag::class,
        LivezillaDynamicTag::class,
        OneTrustTag::class,
        PingdomRUMTag::class,
        RaygunTag::class,
        SentryRavenTag::class,
        ShareaholicTag::class,
        TawkToTag::class,
        VisualWebsiteOptimizerTag::class,
        ZendeskChatTag::class,
    ];

    /**
     * @var string[]|null
     */
    private static $tagTypes;

    /**
     * The `type` column value of each blocked tag, which is what a stored tag and the API refer to
     * a template by.
     *
     * Read off the templates rather than listed a second time, since a template is free to decide
     * its own ID and some of these do.
     *
     * @return string[]
     */
    public static function getTagTypes()
    {
        if (!isset(self::$tagTypes)) {
            self::$tagTypes = [];

            foreach (self::TAGS as $className) {
                if (class_exists($className)) {
                    self::$tagTypes[] = StaticContainer::get($className)->getId();
                }
            }
        }

        return self::$tagTypes;
    }

    /**
     * @param string $tagType the `type` of the tag being written
     * @throws ValidatorException if the current user is not allowed to use this tag
     */
    public static function checkTagTypeIsAllowed($tagType)
    {
        if (!SecuredTemplateConstraints::areRequired()) {
            return;
        }

        if (in_array($tagType, self::getTagTypes(), true)) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerThirdPartyTagNotAllowed'));
        }
    }
}
