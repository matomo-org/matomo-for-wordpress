<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Ecommerce;

use Exception;
use WpMatomo;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\AjaxTracker;
use WpMatomo\Logger;
use WpMatomo\Settings;
use WpMatomo\Site\Sync\SyncConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Base {
	const DELAYED_SERVER_SIDE_TRACKING_HOOK        = 'matomo_delayed_tracking';
	const DELAYED_SERVER_SIDE_TRACKING_SESSION_KEY = 'matomo_delayed_tracking_data';

	protected $key_order_tracked = 'order-tracked';

	/**
	 * @var Logger
	 */
	protected $logger;

	/**
	 * @var AjaxTracker
	 */
	protected $tracker;

	/**
	 * We can't echo cart updates directly as we wouldn't know where in the template rendering stage we are and whether
	 * we're supposed to print or not etc. Also there might be multiple cart updates triggered during one page load so
	 * we want to make sure to print only the most recent tracking code
	 *
	 * @var string
	 */
	protected $cart_update_queue = '';

	/**
	 * @var Settings
	 */
	protected $settings;

	private $ajax_tracker_calls = [];

	/**
	 * @var SyncConfig
	 */
	private $config;

	public function __construct( AjaxTracker $tracker, Settings $settings, SyncConfig $config ) {
		$this->logger   = new Logger();
		$this->tracker  = $tracker;
		$this->settings = $settings;
		$this->config   = $config;

		// by using prefix we make sure it will be removed on unistall and make sure it's clear it belongs to us
		$this->key_order_tracked = Settings::OPTION_PREFIX . $this->key_order_tracked;

		add_action( self::DELAYED_SERVER_SIDE_TRACKING_HOOK, 'do_delayed_tracking' );
		add_action( 'wp_footer', 'maybe_do_delayed_tracking_early' );
	}

	public function register_hooks() {
		if ( ! is_admin() ) {
			add_action( 'wp_footer', [ $this, 'on_print_queues' ], 99999, 0 );
		}
	}

	public function on_print_queues() {
		// we need to queue in case there are multiple cart updates within one page load
		if ( ! empty( $this->cart_update_queue ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->cart_update_queue;
		}
	}

	protected function has_order_been_tracked_already( $order_id ) {
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
		return get_post_meta( $order_id, $this->key_order_tracked, true ) == 1;
	}

	protected function set_order_been_tracked( $order_id ) {
		update_post_meta( $order_id, $this->key_order_tracked, 1 );
	}

	protected function should_track_background() {
		return ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			   || ( defined( 'REST_REQUEST' ) && REST_REQUEST )
			   || ( defined( 'MATOMO_TRACK_ECOMMERCE_SERVER_SIDE' ) && MATOMO_TRACK_ECOMMERCE_SERVER_SIDE )
			   || did_action( 'wp_footer' )
			   || $this->settings->get_global_option( 'track_mode' ) === TrackingSettings::TRACK_MODE_TAGMANAGER;
	}

	protected function make_matomo_js_tracker_call( $params ) {
		if ( $this->should_track_background() ) {
			$this->ajax_tracker_calls[] = $params;
		}

		$code = 'window._paq = window._paq || [];';
		if ( $this->settings->get_global_option( 'disable_cookies' ) ) {
			$code .= ' ' . WpMatomo\TrackingCode\TrackingCodeGenerator::get_disable_cookies_partial();
		}
		$code .= sprintf( ' window._paq.push(%s);', wp_json_encode( $params ) );

		return $code;
	}

	protected function wrap_script( $script ) {
		if ( $this->should_track_background() ) {
			if ( $this->should_delay_server_side_tracking() ) {
				$this->delay_background_tracking();
				return false;
			}

			$this->track_in_background( $this->ajax_tracker_calls );

			$this->ajax_tracker_calls = [];

			return '';
		}

		if ( empty( $script ) ) {
			return '';
		}

		if ( function_exists( 'wp_get_inline_script_tag' ) ) {
			$script = wp_get_inline_script_tag( $script );
		} else {
			// line feed is required to match the wp_get_inline_script_tag output
			$script = '<script >' . PHP_EOL . $script . PHP_EOL . '</script>' . PHP_EOL;
		}

		return $script;
	}

	private function track_in_background( $ajax_tracker_calls, $visitor_id = null, $tracking_time = null, $ip = null ) {
		$original_visitor_id = $this->tracker->forcedVisitorId;
		if ( ! empty( $visitor_id ) ) {
			$this->tracker->set_visitor_id_safe( $visitor_id );
		}

		if ( ! empty( $tracking_time ) ) {
			$this->tracker->setForceVisitDateTime( $tracking_time );
		}

		if ( ! empty( $ip ) ) {
			$this->tracker->setIp( $ip );
		}

		try {
			foreach ( $ajax_tracker_calls as $call ) {
				$methods = [
					'addEcommerceItem'         => 'addEcommerceItem',
					'trackEcommerceOrder'      => 'doTrackEcommerceOrder',
					'trackEcommerceCartUpdate' => 'doTrackEcommerceCartUpdate',
				];
				if ( ! empty( $call[0] ) && ! empty( $methods[ $call[0] ] ) ) {
					try {
						$tracker_method = $methods[ $call[0] ];
						array_shift( $call );
						$response = call_user_func_array( [ $this->tracker, $tracker_method ], $call );

						if (
							'doTrackEcommerceOrder' === $tracker_method
							&& $this->tracker->is_success_response( $response )
						) {
							$order_id = reset( $call );
							$this->set_order_been_tracked( $order_id );
						}
					} catch ( Exception $e ) {
						$this->logger->log_exception( $call[0], $e );
					}
				}
			}
		} finally {
			$this->tracker->forcedVisitorId = $original_visitor_id;
			$this->tracker->forcedDatetime  = false;
		}
	}

	protected function delay_background_tracking() {
		$delay_time    = $this->get_seconds_to_delay_tracking();
		$tracking_time = time() + $delay_time;

		$client_headers = $this->config->get_config_value( 'General', 'proxy_client_headers' );
		foreach ( $client_headers as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$ip = wp_unslash( $_SERVER[ $header ] );
			}
		}
		if ( empty( $ip ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : null;
		}

		$tracking_data = [
			'calls'         => $this->ajax_tracker_calls,
			'delayed_time'  => $tracking_time,
			'visitor_id'    => $this->tracker->forcedVisitorId,
			'tracking_time' => time(),
			'ip'            => $ip,
		];

		$this->save_ajax_calls_in_session( $tracking_data );

		wp_schedule_single_event( $tracking_time, self::DELAYED_SERVER_SIDE_TRACKING_HOOK, $tracking_data );
	}

	protected function should_delay_server_side_tracking() {
		return $this->supports_delayed_tracking();
		// TODO: do we want this to be enabled by default or not? it would be better if so as it's
		// pretty hard to tell when its needed.
	}

	protected function get_seconds_to_delay_tracking() {
		$delay = $this->settings->get_option( Settings::SERVER_SIDE_TRACKING_DELAY_SECS );
		if ( $delay <= 0 ) {
			$delay = 180;
		}
		return $delay;
	}

	protected function maybe_do_delayed_tracking_early() {
		if (
			! $this->supports_delayed_tracking()
			|| $this->should_track_background()
		) {
			return;
		}

		$tracking_data = $this->get_ajax_calls_in_session();
		if ( ! empty( $tracking_data ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->make_matomo_js_tracker_call( $tracking_data['calls'] );

			wp_unschedule_event( $tracking_data['delayed_time'], self::DELAYED_SERVER_SIDE_TRACKING_HOOK, $tracking_data );
		}
	}

	protected function do_delayed_tracking( $tracking_info ) {
		$calls         = isset( $tracking_info['calls'] ) ? $tracking_info['calls'] : [];
		$visitor_id    = isset( $tracking_info['visitor_id'] ) ? $tracking_info['visitor_id'] : null;
		$tracking_time = isset( $tracking_info['tracking_time'] ) ? $tracking_info['tracking_time'] : null;
		$ip            = isset( $tracking_info['ip'] ) ? $tracking_info['ip'] : null;

		$this->track_in_background( $calls, $visitor_id, $tracking_time, $ip );

		$this->save_ajax_calls_in_session( [] );
	}

	/**
	 * TODO: documentation
	 *
	 * @return false
	 */
	protected function supports_delayed_tracking() {
		return false;
	}

	/**
	 * TODO: documentation
	 *
	 * @param array $data
	 * @return void
	 */
	protected function save_ajax_calls_in_session( $data ) {
		// empty
	}

	/**
	 * TODO: documentation
	 *
	 * @return array
	 */
	protected function get_ajax_calls_in_session() {
		return [];
	}
}
