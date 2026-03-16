<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */
/**
 * phpcs considers all of our variables as global and want them prefixed with matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
use WpMatomo\Admin\AdminSettings;
use WpMatomo\Admin\GetStarted;
use WpMatomo\Admin\MarketplaceSetupWizardBody;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var Settings $settings */
/** @var bool $can_user_edit */
/** @var bool $was_updated */
/** @var bool $show_this_page */
/** @var MarketplaceSetupWizardBody $matomo_marketplace_setup_wizard_body */
/** @var bool $matomo_is_marketplace_active */

if ( empty( $show_this_page ) ) {
	echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( menu_page_url( Menu::SLUG_REPORT_SUMMARY, false ) ) . '" />';
}
?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>

	<h1><?php esc_html_e( 'Start getting a full picture of your visitors', 'matomo' ); ?></h1>

	<?php
	if ( $was_updated ) {
		include 'update_notice_clear_cache.php';
	}
	?>

	<p>
	<?php require 'info_matomo_desc.php'; ?>
	</p>

	<hr/>

	<h1 style="font-size:1.4em">
		<?php esc_html_e( 'To start getting relevant reports and insights, complete these steps:', 'matomo' ); ?>
	</h1>

	<h2>1. <?php esc_html_e( 'Update your privacy page and review consent requirements', 'matomo' ); ?></h2>

	<p>
		<?php
			echo sprintf(
				esc_html__( 'Before tracking any data, consider how you will respect your users’ privacy and comply with applicable laws, such as %1$sthe GDPR, ePrivacy Directive (and national implementations)%2$s, or other privacy regulations that apply to you.', 'matomo' ),
				'<a href="https://matomo.org/faq/new-to-piwik/is-matomo-analytics-gdpr-compliant/" rel="noreferrer noopener" target="_blank">',
				'</a>'
			);
			?>
	</p>

	<p>
		<?php
			echo sprintf(
				esc_html__( 'Depending on your configuration and the laws applicable to your website, you may be required to obtain %1$sprior consent%2$s before collecting your website analytics data.', 'matomo' ),
				'<strong>',
				'</strong>'
			);
			?>
	</p>

	<p>
		<?php esc_html_e( 'If you are permitted to track without prior consent, you may still need to provide a method to opt out of tracking.', 'matomo' ); ?>
		<?php
			echo sprintf(
				esc_html__( 'Add the shortcode %1$s to your privacy page or insert the %2$sMatomo opt-out%3$s block.', 'matomo' ),
				'<code>[matomo_opt_out]</code>',
				'<strong>',
				'</strong>'
			);
			?>
		<?php
			echo sprintf(
				esc_html__( 'Read how to customise and configure the opt-out configuration in %1$sPrivacy Settings%2$s.', 'matomo' ),
				'<a href="https://matomo.org/faq/general/faq_20000/" target="_blank" rel="noreferrer noopener">',
				'</a>'
			);
			?>
	</p>

	<p>
		<?php
			echo sprintf(
				esc_html__( 'The use of Matomo Analytics should be disclosed in your %1$swebsite’s privacy notice%2$s and, if applicable, in your cookie notice.', 'matomo' ),
				'<a href="https://matomo.org/faq/how-to/how-to-write-a-gdpr-compliant-privacy-notice/" target="_blank" rel="noreferrer noopener">',
				'</a>'
			);
			?>
	</p>

	<?php if ( $settings->is_tracking_enabled() ) { ?>
		<h2>
			2. <?php esc_html_e( 'Tracking is enabled', 'matomo' ); ?> <span class="dashicons dashicons-yes" style="color: green;"></span>
		</h2>
		<p><?php esc_html_e( 'Tracking should be working now and you don\'t have to do anything else to set up tracking.', 'matomo' ); ?>
			<a href="<?php echo esc_url( AdminSettings::make_url( AdminSettings::TAB_TRACKING ) ); ?>"><?php esc_html_e( 'Click here to optionally configure the tracking code to your liking (not required).', 'matomo' ); ?></a>
		</p>
	<?php } else { ?>
		<h2>2. <?php esc_html_e( 'Enable tracking', 'matomo' ); ?></h2>

		<p><?php esc_html_e( 'Enable tracking using the default configuration by clicking this button', 'matomo' ); ?>:</p>

		<form method="post">
			<?php wp_nonce_field( GetStarted::NONCE_NAME ); ?>
			<input type="hidden" name="<?php echo esc_attr( GetStarted::FORM_NAME ); ?>[track_mode]"
				   value="<?php echo esc_attr( TrackingSettings::TRACK_MODE_DEFAULT ); ?>">
			<input type="submit" class="button-primary" id="matomo-enable-tracking" value="<?php esc_html_e( 'Enable tracking now', 'matomo' ); ?>">
		</form>
	<?php } ?>

	<h2>
		3. <?php esc_html_e( 'Setup the Matomo Marketplace', 'matomo' ); ?>
		<?php if ( $matomo_is_marketplace_active ) { ?>
			<span class="dashicons dashicons-yes" style="color: green;"></span>
		<?php } ?>
	</h2>

	<div style="max-width: 700px;">
		<?php $matomo_marketplace_setup_wizard_body->show(); ?>
	</div>

	<p>
		<br/>
	</p>
</div>
