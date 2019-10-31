<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
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

    <h1>Start getting a full picture of your visitors</h1>

	<?php if ( $was_updated ) {
		include 'update_notice_clear_cache.php';
	} ?>

	<?php if ( $settings->is_tracking_enabled() ) { ?>
        <h2>1. Tracking is enabled <span class="dashicons dashicons-yes"></span></h2>
        <p><a href="<?php echo AdminSettings::make_url( AdminSettings::TAB_TRACKING ) ?>">Click here to configure your
                tracking code.</a></p>

	<?php } else { ?>
        <h2>1. Enable tracking</h2>

        <form method="post">Tracking is currently disabled <?php wp_nonce_field( GetStarted::NONCE_NAME ); ?>
            <input type="hidden" name="<?php echo GetStarted::FORM_NAME ?>[track_mode]"
                   value="<?php echo esc_attr( TrackingSettings::TRACK_MODE_DEFAULT ) ?>">
            <input type="submit" class="button-primary" value="Enable tracking now">
        </form>
	<?php } ?>

    <h2>2. Update your privacy page</h2>
    Give your users the chance to opt-out of tracking by adding the shortcode <code>[matomo_opt_out]</code> to your
    privacy page. You can <a href="<?php echo AdminSettings::make_url( AdminSettings::TAB_PRIVACY ) ?>">tweak the
        opt-out to your liking - see the Privacy Settings</a>.
    You may also need to mention that you are using Matomo Analytics on your website.
    <h2>3. Done</h2>
    <form method="post">
		<?php wp_nonce_field( GetStarted::NONCE_NAME ); ?>
        <input type="hidden" name="<?php echo GetStarted::FORM_NAME ?>[show_get_started_page]"
               value="no">
        <input type="submit" class="button-primary" value="Don't show this page anymore">
    </form>
    <p>
        <br/>
    </p>

	<?php include 'info_shared.php'; ?>
	<?php include 'info_help.php'; ?>
</div>
