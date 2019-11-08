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
 * https://github.com/braekling/WP-Matomo
 *
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Paths;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var \WpMatomo\Settings $settings */
/** @var bool $was_updated */
/** @var array $containers */
/** @var array $track_modes */

$form  = new \WpMatomo\Admin\TrackingSettings\Forms( $settings );
$paths = new Paths();
?>

<?php if ( $was_updated ) {
	include 'update_notice_clear_cache.php';
} ?>
<form method="post">
	<?php wp_nonce_field( TrackingSettings::NONCE_NAME ); ?>

    <p><?php _e( 'Configure the tracking to your liking.', 'matomo' ); ?></p>
    <table class="matomo-tracking-form widefat">
        <tbody>

		<?php
		// Tracking Configuration
		$isNotTracking = $settings->get_global_option( 'track_mode' ) == TrackingSettings::TRACK_MODE_DISABLED;

		$isNotGeneratedTracking     = $isNotTracking || $settings->get_global_option( 'track_mode' ) == TrackingSettings::TRACK_MODE_MANUALLY;
		$fullGeneratedTrackingGroup = 'matomo-track-option matomo-track-option-default  ';

		$description = sprintf( '%s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s<br /><strong>%s:</strong> %s', __( 'You can choose between four tracking code modes:', 'matomo' ), __( 'Disabled', 'matomo' ), __( 'matomo will not add the tracking code. Use this, if you want to add the tracking code to your template files or you use another plugin to add the tracking code.', 'matomo' ), __( 'Default tracking', 'matomo' ), __( 'matomo will use Matomo\'s standard tracking code.', 'matomo' ), __( 'Enter manually', 'matomo' ), __( 'Enter your own tracking code manually. You can choose one of the prior options, pre-configure your tracking code and switch to manually editing at last.', 'matomo' ) . ( $settings->is_network_enabled() ? ' ' . __( 'Use the placeholder {ID} to add the Matomo site ID.', 'matomo' ) : '' ) , __( 'Tag Manager', 'matomo' ), __( 'If you have created containers in the Tag Manager, you can select one of them and it will embed the code for the container automatically.', 'matomo' ));
		$form->show_select( 'track_mode', __( 'Add tracking code', 'matomo' ), $track_modes, $description, 'jQuery(\'tr.matomo-track-option\').addClass(\'hidden\'); jQuery(\'tr.matomo-track-option-\' + jQuery(\'#track_mode\').val()).removeClass(\'hidden\'); jQuery(\'#tracking_code, #noscript_code\').prop(\'readonly\', jQuery(\'#track_mode\').val() != \'manually\');' );

		if (!empty($containers)) {
		    echo '<tr class="matomo-track-option matomo-track-option-tagmanager ' . ( $isNotTracking ? ' hidden' : '' ) . '">';
		    echo '<th scope="row"><label for="tagmanger_container_ids">' . __( 'Add these Tag Manager containers', 'matomo' ) . '</label>:</th><td>';
		    $selected_container_ids = $settings->get_global_option( 'tagmanger_container_ids' );
		    foreach ( $containers as $container_id => $container_name ) {
			    echo '<input type="checkbox" ' . ( isset ( $selected_container_ids [ $container_id ] ) && $selected_container_ids [ $container_id ] ? 'checked="checked" ' : '' ) . 'value="1" name="matomo[tagmanger_container_ids][' . $container_id . ']" /> ID:' . esc_html($container_id) . ' Name: ' . esc_html($container_name) . ' &nbsp; <br />';
		    }
		    echo '<br /><br /><a href="'.menu_page_url(\WpMatomo\Admin\Menu::SLUG_TAGMANAGER, false).'" rel="noreferrer noopener" target="_blank">Edit containers <span class="dashicons-before dashicons-external"></span></a>';
		    echo '</td></tr>';
		}

		$form->show_textarea( 'tracking_code', __( 'Tracking code', 'matomo' ), 15, 'This is a preview of your current tracking code. If you choose to enter your tracking code manually, you can change it here. Have a look at the system report to get a list of all available JS tracker and tracking API endpoints.', $isNotTracking, 'matomo-track-option matomo-track-option-default matomo-track-option-tagmanager  matomo-track-option-manually', true, '', ( $settings->get_global_option( 'track_mode' ) != 'manually' ), false );

		$form->show_select( 'track_codeposition', __( 'JavaScript code position', 'matomo' ), array(
			'footer' => __( 'Footer', 'matomo' ),
			'header' => __( 'Header', 'matomo' )
		), __( 'Choose whether the JavaScript code is added to the footer or the header.', 'matomo' ), '', $isNotTracking, 'matomo-track-option matomo-track-option-default  matomo-track-option-tagmanager matomo-track-option-manually' );

		$form->show_textarea( 'noscript_code', __( 'Noscript code', 'matomo' ), 2, 'This is a preview of your &lt;noscript&gt; code which is part of your tracking code.', $isNotTracking, 'matomo-track-option matomo-track-option-default  matomo-track-option-manually', true, '', ( $settings->get_global_option( 'track_mode' ) != 'manually' ), false );

		$form->show_checkbox( 'track_noscript', __( 'Add &lt;noscript&gt;', 'matomo' ), __( 'Adds the &lt;noscript&gt; code to your footer.', 'matomo' ), $isNotTracking, 'matomo-track-option matomo-track-option-default  matomo-track-option-manually' );

		$form->show_select( 'track_api_endpoint', __( 'Endpoint for HTTP Tracking API', 'matomo' ), array(
			'default' => __( 'Default', 'matomo' ),
			'restapi' => __( 'Through WordPress Rest API', 'matomo' ),
		), __( 'By default the HTTP Tracking API points to your Matomo plugin directory "' . esc_html( $paths->get_tracker_api_url_in_matomo_dir() ) . '". You can choose to use the WP Rest API (' . esc_html( $paths->get_tracker_api_rest_api_endpoint() ) . ') instead for example to hide matomo.php or if the other URL doesn\'t work for you. Note: If the tracking mode "Tag Manager" is selected, then this URL currently only applies to the feed tracking.', 'matomo' ), '', $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager' );

		$form->show_select( 'track_js_endpoint', __( 'Endpoint for JavaScript tracker', 'matomo' ), array(
			'default' => __( 'Default', 'matomo' ),
			'restapi' => __( 'Through WordPress Rest API (slower)', 'matomo' ),
		), __( 'By default the JS tracking code will be loaded from "' . esc_html( $paths->get_js_tracker_url_in_matomo_dir() ) . '". You can choose to serve the JS file through the WP Rest API (' . esc_html( $paths->get_js_tracker_rest_api_endpoint() ) . ') for example to hide matomo.js. Please note that this means every request to the JavaScript file will launch WordPress PHP and therefore will be slower compared to your webserver serving the JS file directly.', 'matomo' ), '', $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_select( 'track_content', __( 'Enable content tracking', 'matomo' ), array(
			'disabled' => __( 'Disabled', 'matomo' ),
			'all'      => __( 'Track all content blocks', 'matomo' ),
			'visible'  => __( 'Track only visible content blocks', 'matomo' )
		), __( 'Content tracking allows you to track interaction with the content of a web page or application.' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://developer.matomo.org/guides/content-tracking" target="_BLANK">', '</a>' ), '', $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'track_ecommerce', __( 'Enable ecommerce', 'matomo' ), __( 'Matom can track Ecommerce orders, abandoned carts and product views for WooCommerce, Easy Digital Analytics, MemberPress, and more. Disabling this feature will also remove Ecommerce reports from the Matomo UI.' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager' );

		$form->show_checkbox( 'track_search', __( 'Track search', 'matomo' ), __( 'Use Matomo\'s advanced Site Search Analytics feature.' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://matomo.org/docs/site-search/#track-site-search-using-the-tracking-api-advanced-users-only" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager' );

		$form->show_checkbox( 'track_404', __( 'Track 404', 'matomo' ), __( 'Matomo can automatically add a 404-category to track 404-page-visits.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo FAQ%s.', 'matomo' ), '<a href="https://matomo.org/faq/how-to/faq_60/" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually' );

		echo '<tr class="' . $fullGeneratedTrackingGroup . ' matomo-track-option-manually' . ( $isNotTracking ? ' hidden' : '' ) . '">';
		echo '<th scope="row"><label for="add_post_annotations">' . __( 'Add annotation on new post of type', 'matomo' ) . '</label>:</th><td>';
		$filter = $settings->get_global_option( 'add_post_annotations' );
		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			echo '<input type="checkbox" ' . ( isset ( $filter [ $post_type->name ] ) && $filter [ $post_type->name ] ? 'checked="checked" ' : '' ) . 'value="1" name="matomo[add_post_annotations][' . $post_type->name . ']" /> ' . $post_type->label . ' &nbsp; ';
		}
		echo '<span class="dashicons dashicons-editor-help" style="cursor: pointer;" onclick="jQuery(\'#add_post_annotations-desc\').toggleClass(\'hidden\');"></span> <p class="description hidden" id="add_post_annotations-desc">' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://matomo.org/docs/annotations/" target="_BLANK">', '</a>' ) . '</p></td></tr>';

		$form->show_input( 'add_download_extensions', __( 'Add new file types for download tracking', 'matomo' ), __( 'Add file extensions for download tracking, divided by a vertical bar (&#124;).', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://developer.matomo.org/guides/tracking-javascript-guide#tracking-file-downloads" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'disable_cookies', __( 'Disable cookies', 'matomo' ), __( 'Disable all tracking cookies for a visitor.', 'matomo' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'limit_cookies', __( 'Limit cookie lifetime', 'matomo' ), __( 'You can limit the cookie lifetime to avoid tracking your users over a longer period as necessary.', 'matomo' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup, true, 'jQuery(\'tr.matomo-cookielifetime-option\').toggleClass(\'matomo-hidden\');' );

		$form->show_input( 'limit_cookies_visitor', __( 'Visitor timeout (seconds)', 'matomo' ), false, $isNotGeneratedTracking || ! $settings->get_global_option( 'limit_cookies' ), $fullGeneratedTrackingGroup . ' matomo-cookielifetime-option' . ( $settings->get_global_option( 'limit_cookies' ) ? '' : ' matomo-hidden' ) );

		$form->show_input( 'limit_cookies_session', __( 'Session timeout (seconds)', 'matomo' ), false, $isNotGeneratedTracking || ! $settings->get_global_option( 'limit_cookies' ), $fullGeneratedTrackingGroup . ' matomo-cookielifetime-option' . ( $settings->get_global_option( 'limit_cookies' ) ? '' : ' matomo-hidden' ) );

		$form->show_input( 'limit_cookies_referral', __( 'Referral timeout (seconds)', 'matomo' ), false, $isNotGeneratedTracking || ! $settings->get_global_option( 'limit_cookies' ), $fullGeneratedTrackingGroup . ' matomo-cookielifetime-option' . ( $settings->get_global_option( 'limit_cookies' ) ? '' : ' matomo-hidden' ) );

		$form->show_checkbox( 'track_admin', __( 'Track admin pages', 'matomo' ), __( 'Enable to track users on admin pages (remember to configure the tracking filter appropriately).', 'matomo' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager' );

		$form->show_checkbox( 'track_across', __( 'Track subdomains in the same website', 'matomo' ), __( 'Adds *.-prefix to cookie domain.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://developer.matomo.org/guides/tracking-javascript-guide#tracking-subdomains-in-the-same-website" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'track_across_alias', __( 'Do not count subdomains as outlink', 'matomo' ), __( 'Adds *.-prefix to tracked domain.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://developer.matomo.org/guides/tracking-javascript-guide#outlink-tracking-exclusions" target="_BLANK">', '</a>' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'track_crossdomain_linking', __( 'Enable cross domain linking', 'matomo' ), __( 'When enabled, it will make sure to use the same visitor ID for the same visitor across several domains. This works only when this feature is enabled because the visitor ID is stored in a cookie and cannot be read on the other domain by default. When this feature is enabled, it will append a URL parameter "pk_vid" that contains the visitor ID when a user clicks on a URL that belongs to one of your domains. For this feature to work, you also have to configure which domains should be treated as local in your Matomo website settings.', 'matomo' ), $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_checkbox( 'track_feed', __( 'Track RSS feeds', 'matomo' ), __( 'Enable to track posts in feeds via tracking pixel.', 'matomo' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager' );

		$form->show_checkbox( 'track_feed_addcampaign', __( 'Track RSS feed links as campaign', 'matomo' ), __( 'This will add Matomo campaign parameters to the RSS feed links.' . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://matomo.org/docs/tracking-campaigns/" target="_BLANK">', '</a>' ), 'matomo' ), $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-manually matomo-track-option-tagmanager', true, 'jQuery(\'tr.matomo-feed_campaign-option\').toggle(\'hidden\');' );

		$form->show_input( 'track_feed_campaign', __( 'RSS feed campaign', 'matomo' ), __( 'Keyword: post name.', 'matomo' ), $isNotGeneratedTracking || ! $settings->get_global_option( 'track_feed_addcampaign' ), $fullGeneratedTrackingGroup . ' matomo-feed_campaign-option matomo-track-option-tagmanager' );

		$form->show_input( 'track_heartbeat', __( 'Enable heartbeat timer', 'matomo' ), __( 'Enable a heartbeat timer to get more accurate visit lengths by sending periodical HTTP ping requests as long as the site is opened. Enter the time between the pings in seconds (Matomo default: 15) to enable or 0 to disable this feature. <strong>Note:</strong> This will cause a lot of additional HTTP requests on your site.', 'matomo' ), $isNotGeneratedTracking, $fullGeneratedTrackingGroup );

		$form->show_select( 'track_user_id', __( 'User ID Tracking', 'matomo' ), array(
			'disabled'    => __( 'Disabled', 'matomo' ),
			'uid'         => __( 'WP User ID', 'matomo' ),
			'email'       => __( 'Email Address', 'matomo' ),
			'username'    => __( 'Username', 'matomo' ),
			'displayname' => __( 'Display Name (Not Recommended!)', 'matomo' )
		), __( 'When a user is logged in to WordPress, track their &quot;User ID&quot;. You can select which field from the User\'s profile is tracked as the &quot;User ID&quot;. When enabled, Tracking based on Email Address is recommended.', 'matomo' ), '', $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-tagmanager' );

		$form->show_checkbox( 'track_datacfasync', __( 'Add data-cfasync=false', 'matomo' ), __( 'Adds data-cfasync=false to the script tag, e.g., to ask Rocket Loader to ignore the script.' . ' ' . sprintf( __( 'See %sCloudFlare Knowledge Base%s.', 'matomo' ), '<a href="https://support.cloudflare.com/hc/en-us/articles/200169436-How-can-I-have-Rocket-Loader-ignore-my-script-s-in-Automatic-Mode-" target="_BLANK">', '</a>' ), 'matomo' ), $isNotTracking, $fullGeneratedTrackingGroup . '  matomo-track-option-tagmanager' );

		$submitButton = '<tr><td colspan="2"><p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . esc_attr__( 'Save Changes' ) . '" /></p></td></tr>';

		$form->show_input( 'set_download_extensions', __( 'Define all file types for download tracking', 'matomo' ), __( 'Replace Matomo\'s default file extensions for download tracking, divided by a vertical bar (&#124;). Leave blank to keep Matomo\'s default settings.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo documentation%s.', 'matomo' ), '<a href="https://developer.matomo.org/guides/tracking-javascript-guide#file-extensions-for-tracking-downloads" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_input( 'set_download_classes', __( 'Set classes to be treated as downloads', 'matomo' ), __( 'Set classes to be treated as downloads (in addition to piwik_download), divided by a vertical bar (&#124;). Leave blank to keep Matomo\'s default settings.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo JavaScript Tracking Client reference%s.', 'matomo' ), '<a href="https://developer.matomo.org/api-reference/tracking-javascript" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_input( 'set_link_classes', __( 'Set classes to be treated as outlinks', 'matomo' ), __( 'Set classes to be treated as outlinks (in addition to piwik_link), divided by a vertical bar (&#124;). Leave blank to keep Matomo\'s default settings.', 'matomo' ) . ' ' . sprintf( __( 'See %sMatomo JavaScript Tracking Client reference%s.', 'matomo' ), '<a href="https://developer.matomo.org/api-reference/tracking-javascript" target="_BLANK">', '</a>' ), $isNotTracking, $fullGeneratedTrackingGroup );

		$form->show_select( 'force_protocol', __( 'Force Matomo to use a specific protocol', 'matomo' ), array(
			'disabled' => __( 'Disabled (default)', 'matomo' ),
			'https'    => __( 'https (SSL)', 'matomo' )
		), __( 'Choose if you want to explicitly want to force Matomo to use HTTP or HTTPS. Does not work with a CDN URL.', 'matomo' ), '', $isNotTracking, $fullGeneratedTrackingGroup . ' matomo-track-option-tagmanager' );

		echo $submitButton; ?>

        </tbody>
    </table>
</form>
