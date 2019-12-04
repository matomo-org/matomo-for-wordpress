<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * https://github.com/braekling/matomo
 *
 */

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

<h2><?php esc_html_e( 'Matomo ensures the privacy of your users and analytics data! YOU keep control of your data.', 'matomo' ); ?></h2>

<blockquote
	class="matomo-blockquote"><?php esc_html_e( 'One of Matomo\'s guiding principles: respecting privacy', 'matomo' ); ?></blockquote>
<p>
	<?php esc_html_e( 'Matomo Analytics is privacy by design. All data collected is stored only within your own MySQL database, no other business (or Matomo team member) can access any of this information, and logs or report data will never be sent to other servers by Matomo', 'matomo' ); ?>
	.

	<?php
	echo sprintf(
		__( 'The source code of the software is open-source so hundreds of people have reviewed it to ensure it is %1$ssecure%2$s and keeps your data private.', 'matomo' ),
		'<a href="https://matomo.org/security/" rel="noreferrer noopener">',
		'</a>'
	);
	?>
</p>
<h2>
	<?php esc_html_e( 'Ways Matomo protects the privacy of your users and customers', 'matomo' ); ?>
</h2>
<p><?php esc_html_e( 'Although Matomo Analytics is a web analytics software that has a purpose to track user activity on your website, we take privacy very seriously.', 'matomo' ); ?></p>
<p><?php esc_html_e( 'Privacy is a fundamental right so by using Matomo you can rest assured you have 100% control over that data and can protect your user\'s privacy as it\'s on your own server.', 'matomo' ); ?></p>

<ul class="matomo-list">
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_ANONYMIZE_DATA ); ?>"><?php esc_html_e( 'Anonymise data and IP addresses', 'matomo' ); ?></a>
	</li>
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_DATA_RETENTION ); ?>"><?php esc_html_e( 'Configure data retention', 'matomo' ); ?></a>
	</li>
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_OPTOUT ); ?>"><?php esc_html_e( 'Matomo has an opt-out mechanism which lets users opt-out of web analytics tracking', 'matomo' ); ?></a>
		(<?php esc_html_e( 'see below for the shortcode', 'matomo' ); ?>)
	</li>
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_ASK_CONSENT ); ?>"><?php esc_html_e( 'Asking for consent', 'matomo' ); ?></a>
	</li>
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_GDPR_OVERVIEW ); ?>"><?php esc_html_e( 'GDPR overview', 'matomo' ); ?></a>
	</li>
	<li>
		<a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_GDPR_TOOLS ); ?>"><?php esc_html_e( 'GDPR tools', 'matomo' ); ?></a>
	</li>
</ul>
<h2>
	<?php esc_html_e( 'Let users opt-out of tracking', 'matomo' ); ?>
</h2>
<p>
	<?php
	echo sprintf(
		__( 'Use the short code %1$s to embed the opt out iframe into your website.', 'matomo' ),
		'<code>' . esc_html( PrivacySettings::EXAMPLE_MINIMAL ) . '</code>'
	);
	?>
		<br/>
	<?php esc_html_e( 'You can use these short code options:', 'matomo' ); ?>
</p>
<ul class="matomo-list">
	<li>language - eg de or
		en. <?php esc_html_e( 'By default the language is detected automatically based on the user\'s browser', 'matomo' ); ?></li>
	<li>background_color - eg black or #000</li>
	<li>font_color - eg black or #000</li>
	<li>font_size - eg 15px</li>
	<li>font_family - eg Arial or Verdana</li>
	<li>width - eg 600, 600px or 100%</li>
	<li>height - eg 200, 200px or 20%</li>
</ul>
<p><?php esc_html_e( 'Example', 'matomo' ); ?>: <code><?php echo esc_html( PrivacySettings::EXAMPLE_FULL ); ?></code></p>
