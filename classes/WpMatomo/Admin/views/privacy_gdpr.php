<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * @package WP_Matomo
 * https://github.com/braekling/matomo
 *
 */

use Piwik\Piwik;
use WpMatomo\Admin\ExclusionSettings;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\PrivacySettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $was_updated */
/** @var string $current_ip */
/** @var string $excluded_ips */
/** @var string $excluded_user_agents */
/** @var string $excluded_query_params */
/** @var bool|string|int $keep_url_fragments */

?>

<h2>Matomo ensures the privacy of your users and analytics data! YOU keep control of your data.</h2>

<blockquote class="matomo-blockquote">One of Matomo's guiding principles: respecting privacy</blockquote>
<p>
    Matomo Analytics is privacy by design. All data collected is stored only within your own MySQL database, no other
    business (or Matomo team member) can access any of this information, and logs or report data will never be sent to
    other servers by Matomo.

    The source code of the software is open-source so hundreds of people have reviewed it to ensure it is <a
            href="https://matomo.org/security/" rel="noreferrer noopener">secure</a> and keeps your data private.
</p>
<h2>
    Ways Matomo protects the privacy of your users and customers
</h2>
<p>Although Matomo Analytics is a web analytics software that has a purpose to track user activity on your website, we
    take privacy very seriously.</p>
<p>Privacy is a fundamental right so by using Matomo you can rest assured you have 100% control over that data and can
    protect your user’s privacy as it’s on your own server.</p>

<ul class="matomo-list">
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_ANONYMIZE_DATA ); ?>">Anonymise data and
            IP addresses</a></li>
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_DATA_RETENTION ); ?>">Configure data
            retention</a></li>
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_OPTOUT ); ?>">Matomo has an opt-out
            mechanism which lets users opt-out of web analytics tracking</a> (see below for the shortcode)
    </li>
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_ASK_CONSENT ); ?>">Asking for consent</a>
    </li>
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_GDPR_OVERVIEW ); ?>">GDPR overview</a>
    </li>
    <li><a href="<?php echo Menu::get_matomo_action_url( Menu::REPORTING_GOTO_GDPR_TOOLS ); ?>">GDPR tools</a></li>
</ul>
<h2>
    Let users opt-out of tracking
</h2>
<p>
    Use the short code <code><?php echo PrivacySettings::EXAMPLE_MINIMAL ?></code> to embed the opt out iframe into your
    website.<br/>
    You can use these short code options:
</p>
<ul class="matomo-list">
    <li>language - eg de, en, fr, ... by default the language is detected automatically based on the user's browser</li>
    <li>background_color - eg black or #000</li>
    <li>font_color - eg black or #000</li>
    <li>font_size - eg 15px</li>
    <li>font_family - eg Arial or Verdana</li>
    <li>width - eg 600, 600px or 100%</li>
    <li>height - eg 200, 200px or 20%</li>
</ul>
<p>Example: <code><?php echo PrivacySettings::EXAMPLE_FULL ?></code></p>
<h2>You earned it!</h2>
<p>
    Use the shortcode <code>[matomo_privacy_badge size=120]</code> to show that your website respects your visitors'
    privacy.
	<?php echo do_shortcode( '[matomo_privacy_badge size=120 align=left]' ); ?>
</p>