<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

/**
 * TODO: docs including note about not using many dependencies
 *
 * TODO: tests
 *
 * TODO: after proven to work, merge matomo-php-tracker PR and update app/vendor
 */
class AIBotTracking {

	// TODO: can use timer_float() instead
	private static $request_start_time_ms;

	private static $ai_bot_tracked = false;

	private static $extensions_to_track = [
		'', // no extension

		'htm',
		'html',
		'php',
	];

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var AjaxTracker
	 */
	private $tracker;

	/**
	 * @param Settings       $settings
	 * @param ?AIBotTracking $tracker
	 */
	public function __construct( $settings, $tracker = null ) {
		$this->settings = $settings;
		$this->tracker  = isset( $tracker ) ? $tracker : new AjaxTracker( $settings );
		$this->tracker->setRequestTimeout( 1 );
	}

	public function register_hooks() {
		add_action( 'wp_footer', [ $this, 'do_ai_bot_tracking' ], 999999 );
	}

	public function do_ai_bot_tracking() {
		if ( self::$ai_bot_tracked ) {
			return;
		}

		self::$ai_bot_tracked = true;

		if ( ! $this->should_track_current_page() ) {
			return;
		}

		if ( $this->is_js_execution_detected() ) {
			return;
		}

		if ( ! AjaxTracker::isUserAgentAIBot( $this->tracker->userAgent ) ) {
			return;
		}

		if (
			! $this->settings->is_ai_bot_tracking_enabled()
			|| ! $this->settings->is_tracking_enabled()
		) {
			return;
		}

		// TODO: manual track code may not set elapsed time correctly. should be able to set start time via query param
		if ( $this->is_using_litespeed_cache() && ! defined( 'MATOMO_IN_LITESPEED_ESI' ) ) {
			// TODO: openlitespeed does not support esi, so it won't work there. must display warning in this case.
			$track_script_url = plugins_url( '/misc/track_ai_bot.php', MATOMO_ANALYTICS_FILE );
			echo '<esi:include src="' . esc_attr( $track_script_url ) . '" cache-control="no-cache" />';
			return;
		}

		$response_code      = http_response_code();
		$request_elapsed_ms = $this->get_request_elapsed_time();

		if ( empty( $response_code ) ) {
			$response_code = 200;
		}

		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		$source = 'wordpress';

		// TODO: response size and source, unsure what to put here
		$this->tracker->doTrackPageViewIfAIBot( $response_code, null, $request_elapsed_ms, $source );
	}

	public function should_track_current_page() {
		if ( is_admin() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );

		if ( preg_match( '/matomo\.php$/', $request_path ) ) {
			return false;
		}

		if ( $this->is_request_for_file( $request_path ) ) {
			return false;
		}

		return true;
	}

	private function is_request_for_file( $request_path ) {
		if ( ! is_file( $_SERVER['DOCUMENT_ROOT'] . $request_path ) ) {
			return false;
		}

		$extension = pathinfo( $request_path, PATHINFO_EXTENSION );
		return ! in_array( $extension, self::$extensions_to_track, true );
	}

	private function get_request_elapsed_time() {
		return self::get_current_time_ms() - self::$request_start_time_ms;
	}

	public static function record_request_start_time() {
		self::$request_start_time_ms = self::get_current_time_ms();
	}

	private static function get_current_time_ms() {
		return (int) ( microtime( true ) * 1000 );
	}

	public static function set_is_ai_bot_tracked( $is_tracked ) {
		self::$ai_bot_tracked = $is_tracked;
	}

	public function is_js_execution_detected() {
		return ! empty( $_COOKIE['matomo_has_js'] )
			&& $_COOKIE['matomo_has_js'] === '1';
	}

	public function is_using_litespeed_cache() {
		return php_sapi_name() === 'litespeed';
	}
}

AIBotTracking::record_request_start_time();
