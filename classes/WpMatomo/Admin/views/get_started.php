<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\AdminSettings;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Admin\GetStarted;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var \WpMatomo\Settings $settings */
/** @var bool $can_user_edit */
/** @var bool $was_updated */
/** @var bool $show_this_page */

if ( empty( $show_this_page ) ) {
	echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( menu_page_url( Menu::SLUG_ABOUT, false ) ) . '" />';
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

	<?php if ( $settings->is_tracking_enabled() ) { ?>
		<h2>1. <?php esc_html_e( 'Tracking is enabled', 'matomo' ); ?> <span class="dashicons dashicons-yes"></span></h2>
		<p><a href="<?php echo AdminSettings::make_url( AdminSettings::TAB_TRACKING ); ?>"><?php esc_html_e( 'Click here to configure your tracking code.', 'matomo' ); ?></a></p>

	<?php } else { ?>
		<h2>1. <?php esc_html_e( 'Enable tracking', 'matomo' ); ?></h2>

		<form
			method="post"><?php esc_html_e( 'Tracking is currently disabled', 'matomo' ); ?> <?php wp_nonce_field( GetStarted::NONCE_NAME ); ?>
			<input type="hidden" name="<?php echo GetStarted::FORM_NAME; ?>[track_mode]"
				   value="<?php echo esc_attr( TrackingSettings::TRACK_MODE_DEFAULT ); ?>">
			<input type="submit" class="button-primary" value="<?php esc_html_e( 'Enable tracking now', 'matomo' ); ?>">
		</form>
	<?php } ?>

	<h2>2. <?php esc_html_e( 'Update your privacy page', 'matomo' ); ?></h2>

	<?php echo sprintf( esc_html__( 'Give your users the chance to opt-out of tracking by adding the shortcode %1$s to your privacy page. You can %2$stweak the opt-out to your liking - see the Privacy Settings%3$s.', 'matomo' ), '<code>[matomo_opt_out]</code>', '<a href="' . AdminSettings::make_url( AdminSettings::TAB_PRIVACY ) . '">', '</a>' ); ?>

	<?php esc_html_e( 'You may also need to mention that you are using Matomo Analytics on your website.', 'matomo' ); ?>

	<h2>3. <?php esc_html_e( 'Done', 'matomo' ); ?></h2>
	<form method="post">
		<?php wp_nonce_field( GetStarted::NONCE_NAME ); ?>
		<input type="hidden" name="<?php echo esc_attr( GetStarted::FORM_NAME ); ?>[show_get_started_page]"
			   value="no">
		<input type="submit" class="button-primary" value="<?php esc_html_e( 'Don\'t show this page anymore', 'matomo' ); ?>">
	</form>
	<p>
		<br/>
	</p>

	<?php require 'info_shared.php'; ?>
	<?php
	$show_troubleshooting_link = false;
	require 'info_help.php'; ?>
</div>
