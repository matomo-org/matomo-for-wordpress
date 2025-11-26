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
	require_once __DIR__ . '/../app/vendor/matomo/matomo-php-tracker/MatomoTracker.php';

	// check user agent is AI bot first thing, so if it is a normal request, we do
	// as little extra work as possible
	$user_agent = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : false;
	if ( ! MatomoTracker::isUserAgentAIBot( $user_agent ) ) {
		return;
	}

	require_once __DIR__ . '/../classes/WpMatomo/Logger.php';
	require_once __DIR__ . '/../classes/WpMatomo/Site.php';
	require_once __DIR__ . '/../classes/WpMatomo/Paths.php';
	require_once __DIR__ . '/../classes/WpMatomo/Settings.php';
	require_once __DIR__ . '/../classes/WpMatomo/TrackingCode/GeneratorOptions.php';
	require_once __DIR__ . '/../classes/WpMatomo/TrackingCode/TrackingCodeGenerator.php';
	require_once __DIR__ . '/../classes/WpMatomo/AjaxTracker.php';
	require_once __DIR__ . '/../classes/WpMatomo/AIBotTracking.php';

	$settings        = new \WpMatomo\Settings();
	$ai_bot_tracking = new \WpMatomo\AIBotTracking( $settings );

	$ai_bot_tracking->do_ai_bot_tracking();
}

register_shutdown_function('matomo_track_if_ai_bot');
