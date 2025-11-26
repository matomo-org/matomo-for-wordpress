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

	private static $request_start_time_ms;

	private static $extensions_to_track = [
		'', // no extension

		'html',
		'html',
	];

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var AjaxTracker
	 */
	private $tracker;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->tracker  = new AjaxTracker( $settings );
	}

	public function register_hooks() {
		add_action( 'wp_footer', [ $this, 'do_ai_bot_tracking' ], 999999 );
	}

	public function do_ai_bot_tracking() {
		if ( ! $this->should_track_current_page() ) {
			return;
		}

		if ( ! AjaxTracker::isUserAgentAIBot( $this->tracker->userAgent ) ) {
			return;
		}

		if ( ! $this->settings->is_ai_bot_tracking_enabled() ) {
			return;
		}

		$response_code      = http_response_code();
		$request_elapsed_ms = $this->get_request_elapsed_time();

		if ( empty( $response_code ) ) {
			$response_code = 200;
		}

		// TODO: response size and source, unsure what to put here
		$this->tracker->doTrackPageViewIfAIBot( $response_code, null, $request_elapsed_ms );
	}

	public function should_track_current_page() {
		if ( is_admin() ) {
			return false;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		// TODO: test on advanced-cache cache miss
		// TODO: test with html files created

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );

		if ( $this->is_request_for_file( $request_path ) ) {
			return false;
		}

		if ( $this->is_request_for_robots_txt( $request_path ) ) {
			return false;
		}

		if ( $this->is_request_for_sitemap_xml( $request_path ) ) {
			return false;
		}

		return true;
	}

	private function is_request_for_file( $request_path ) {
		$extension = pathinfo( $request_path, PATHINFO_EXTENSION );
		return ! in_array( $extension, self::$extensions_to_track, true );
	}

	private function is_request_for_robots_txt( $request_path ) {
		return preg_match( '%/robots\.txt$%', $request_path );
	}

	private function is_request_for_sitemap_xml( $request_path ) {
		return preg_match( '%/sitemap\.xml$%', $request_path );
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
}

AIBotTracking::record_request_start_time();
