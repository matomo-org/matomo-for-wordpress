<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use LiteSpeed\ESI;

/**
 * Performs server side tracking for AI bots.
 *
 * Normal server side tracking: when no caching plugins or CDNs are being
 * used, this class will send tracking requests in the wp_footer hook.
 *
 * When advanced-cache.php is used: when a caching plugin creates an
 * advanced-cache.php file that WordPress uses, tracking must be done
 * through the standalone script in misc/track_ai_bot.php. This script
 * must be manually added to a user's wp-config.php file, right after
 * ABSPATH is defined.
 *
 * When .htaccess is used: when a caching plugin modifies the .htaccess
 * to serve cached files directly, AI bot tracking is not possible.
 *
 * When a CDN is used: when a CDN is used to serve cached content, AI
 * bot tracking can be accomplished through the use of ESI (Edge Side Includes).
 * In this case, this class outputs an `<esi:include>` directive that
 * loads the misc/track_ai_bot.php script. This technique will only work
 * for CDNs that support ESI.
 *
 * The misc/track_ai_bot.php script uses this class without all of WordPress loaded.
 * It can also be loaded outside of WordPress. Because of this, it is important
 * that the script and this class use as few total dependencies as possible. Otherwise,
 * AI bot tracking reduce the performance of requests to cached content.
 */
class AIBotTracking {

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
		$this->tracker  = $tracker;
	}

	public function register_hooks() {
		add_action( 'litespeed_init', [ $this, 'litespeed_init' ] );
		add_action( 'wp_footer', [ $this, 'do_ai_bot_tracking' ], 999999 );
	}

	public function litespeed_init() {
		if ( class_exists( ESI::class )
			&& $this->settings->is_tracking_ai_bots_via_esi_includes()
		) {
			ESI::set_has_esi();
		}
	}

	public function do_ai_bot_tracking( $url = null ) {
		// track AI bots only once per request
		if ( self::$ai_bot_tracked ) {
			return;
		}

		self::$ai_bot_tracked = true;

		// if using ESI to track, and not within the track_ai_bot.php script, output the appropriate ESI tag
		$is_using_esi_to_track = $this->settings->is_tracking_ai_bots_via_esi_includes();
		if (
			$is_using_esi_to_track
			&& empty( $GLOBALS['MATOMO_IN_AI_ESI'] )
		) {
			$track_script_url = plugins_url( '/misc/track_ai_bot.php', MATOMO_ANALYTICS_FILE ) . '?mtm_esi=1&mtm_url=' . rawurlencode( AjaxTracker::getCurrentUrl() );
			echo '<esi:include src="' . esc_attr( $track_script_url ) . '" cache-control="no-cache" />';
			return;
		}

		if ( ! $this->is_doing_ai_bot_tracking_this_request() ) {
			return;
		}

		$response_code      = http_response_code();
		$request_elapsed_ms = null;

		if (
			empty( $GLOBALS['MATOMO_IN_AI_ESI'] )
			&& array_key_exists( 'REQUEST_TIME_FLOAT', $_SERVER )
		) {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$request_elapsed_ms = (int) ( ( microtime( true ) - $_SERVER['REQUEST_TIME_FLOAT'] ) * 1000 );
		}

		if ( empty( $response_code ) ) {
			$response_code = 200;
		}

		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		$source = 'wordpress';

		if ( empty( $url ) ) {
			$url = AjaxTracker::getCurrentUrl();
		}

		$tracker = $this->get_tracker();
		$tracker->setUrl( $url );

		// cannot count bytes echo'd so no response size tracked
		$tracker->doTrackPageViewIfAIBot( $response_code, null, $request_elapsed_ms, $source );
	}

	public function should_track_current_page() {
		if ( is_admin() ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return false;
		}

		if (
			! array_key_exists( 'REQUEST_URI', $_SERVER )
			|| null === $_SERVER['REQUEST_URI']
		) {
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
		if (
			! empty( $_SERVER['DOCUMENT_ROOT'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			&& is_dir( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) . $request_path )
		) {
			return false;
		}

		$extension = pathinfo( $request_path, PATHINFO_EXTENSION );
		return ! in_array( $extension, self::$extensions_to_track, true );
	}

	public static function set_is_ai_bot_tracked( $is_tracked ) {
		self::$ai_bot_tracked = $is_tracked;
	}

	public function is_js_execution_detected() {
		return ! empty( $_COOKIE['matomo_has_js'] )
			&& '1' === $_COOKIE['matomo_has_js'];
	}

	private function is_doing_ai_bot_tracking_this_request() {
		if ( ! empty( $GLOBALS['MATOMO_IN_AI_ESI'] ) ) {
			return true;
		}

		if ( ! $this->should_track_current_page() ) {
			return false;
		}

		if ( $this->is_js_execution_detected() ) {
			return false;
		}

		$tracker = $this->get_tracker();

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( ! AjaxTracker::isUserAgentAIBot( $tracker->userAgent ) ) {
			return false;
		}

		if (
			! $this->settings->is_ai_bot_tracking_enabled()
			|| ! $this->settings->is_tracking_enabled()
		) {
			return false;
		}

		return true;
	}

	private function get_tracker() {
		if ( empty( $this->tracker ) ) {
			$this->tracker = new AjaxTracker( $this->settings );
			$this->tracker->setRequestTimeout( 1 );
		}

		return $this->tracker;
	}
}
