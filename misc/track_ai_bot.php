<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

// TODO: docs on what this script does

function matomo_track_if_ai_bot() {
	global $wpdb;

	if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
		return; // advanced-cache.php not in use
	}

	require_once __DIR__ . '/../app/vendor/matomo/matomo-php-tracker/MatomoTracker.php';

	// check user agent is AI bot first thing, so if it is a normal request, we do
	// as little extra work as possible
	$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : false;
	if ( ! MatomoTracker::isUserAgentAIBot( $user_agent ) ) {
		return;
	}

	$GLOBALS['wp_plugin_paths'] = [];

	require_once ABSPATH . WPINC . '/class-wp-list-util.php';
	require_once ABSPATH . WPINC . '/class-wp-token-map.php';
	require_once ABSPATH . WPINC . '/formatting.php';
	require_once ABSPATH . WPINC . '/functions.php';
	require_once ABSPATH . WPINC . '/link-template.php';
	require_once ABSPATH . WPINC . '/general-template.php';
	require_once ABSPATH . WPINC . '/http.php';
	require_once ABSPATH . WPINC . '/class-wp-http.php';
	require_once ABSPATH . WPINC . '/class-wp-http-streams.php';
	require_once ABSPATH . WPINC . '/class-wp-http-curl.php';
	require_once ABSPATH . WPINC . '/class-wp-http-proxy.php';
	require_once ABSPATH . WPINC . '/class-wp-http-cookie.php';
	require_once ABSPATH . WPINC . '/class-wp-http-encoding.php';
	require_once ABSPATH . WPINC . '/class-wp-http-response.php';
	require_once ABSPATH . WPINC . '/class-wp-http-requests-response.php';
	require_once ABSPATH . WPINC . '/class-wp-http-requests-hooks.php';

	require_once __DIR__ . '/../classes/WpMatomo/Logger.php';
	require_once __DIR__ . '/../classes/WpMatomo/Site.php';
	require_once __DIR__ . '/../classes/WpMatomo/Paths.php';
	require_once __DIR__ . '/../classes/WpMatomo/Admin/CookieConsent.php';
	require_once __DIR__ . '/../classes/WpMatomo/Settings.php';
	require_once __DIR__ . '/../classes/WpMatomo/TrackingCode/GeneratorOptions.php';
	require_once __DIR__ . '/../classes/WpMatomo/TrackingCode/TrackingCodeGenerator.php';
	require_once __DIR__ . '/../classes/WpMatomo/AjaxTracker.php';
	require_once __DIR__ . '/../classes/WpMatomo/AIBotTracking.php';

	if ( empty( $wpdb ) ) {
		require_wp_db();
		wp_set_wpdb_vars();
	}

	wp_start_object_cache();

	if ( ! defined( 'WPMU_PLUGIN_DIR' ) ) {
		wp_plugin_directory_constants();
	}

	$settings        = new \WpMatomo\Settings();
	$ai_bot_tracking = new \WpMatomo\AIBotTracking( $settings );
	$ai_bot_tracking->do_ai_bot_tracking();
}

register_shutdown_function('matomo_track_if_ai_bot');
