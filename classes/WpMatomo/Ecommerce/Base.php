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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Base {
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

	private $ajax_tracker_calls = [];

	public function __construct( AjaxTracker $tracker ) {
		$this->logger  = new Logger();
		$this->tracker = $tracker;

		// by using prefix we make sure it will be removed on unistall and make sure it's clear it belongs to us
		$this->key_order_tracked = Settings::OPTION_PREFIX . $this->key_order_tracked;
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
			   || WpMatomo::$settings->get_global_option( 'track_mode' ) === TrackingSettings::TRACK_MODE_TAGMANAGER;
	}

	protected function make_matomo_js_tracker_call( $params ) {
		if ( $this->should_track_background() ) {
			$this->ajax_tracker_calls[] = $params;
		}

		return sprintf( 'window._paq = window._paq || []; window._paq.push(%s);', wp_json_encode( $params ) );
	}

	protected function wrap_script( $script ) {
		if ( $this->should_track_background() ) {
			foreach ( $this->ajax_tracker_calls as $call ) {
				$methods = [
					'addEcommerceItem'         => 'addEcommerceItem',
					'trackEcommerceOrder'      => 'doTrackEcommerceOrder',
					'trackEcommerceCartUpdate' => 'doTrackEcommerceCartUpdate',
				];
				if ( ! empty( $call[0] ) && ! empty( $methods[ $call[0] ] ) ) {
					try {
						$tracker_method = $methods[ $call[0] ];
						array_shift( $call );
						call_user_func_array( [ $this->tracker, $tracker_method ], $call );
					} catch ( Exception $e ) {
						$this->logger->log_exception( $call[0], $e );
					}
				}
			}
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
}
