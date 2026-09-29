<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

// sets up woocommerce via PHP so we don't have to set it up via browser automation
// use with eval-file: `wp eval-file .../tests/e2e/resources/woocommerce-setup.php --user=<admin>`
call_user_func(
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			throw new Exception( 'WooCommerce is not active.' );
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown
			throw new Exception( 'This script must be run as an administrator, use --user.' );
		}

		$store_country = 'US';
		$store_state   = 'CA';

		$routes = rest_get_server()->get_routes();

		$request = function ( $method, $route, $body = [] ) {
			$request = new WP_REST_Request( $method, $route );
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );

			$response = rest_do_request( $request );
			if ( $response->is_error() ) {
				throw new Exception(
					"$method $route failed: " . wp_json_encode( $response->as_error()->get_error_messages() )
				);
			}
		};

		// the wizard's usage tracking opt-in. the e2e tests should never send data to WooCommerce.
		update_option( 'woocommerce_allow_tracking', 'no' );

		// only exists in the core profiler (WooCommerce 8+). the older profile wizard has no equivalent.
		if ( isset( $routes['/wc-admin/onboarding/profile/progress/core-profiler/complete'] ) ) {
			$request(
				'POST',
				'/wc-admin/onboarding/profile/progress/core-profiler/complete',
				[ 'step' => 'intro-opt-in' ]
			);
		}

		// this is what stops WooCommerce from redirecting wp-admin to the setup wizard
		$request( 'POST', '/wc-admin/onboarding/profile', [ 'skipped' => true ] );

		update_option( 'woocommerce_default_country', "$store_country:$store_state" );

		// only exists in the core profiler (WooCommerce 8+), where the skip flow calls it after setting the country
		if ( isset( $routes['/wc-admin/onboarding/profile/update-store-currency-and-measurement-units'] ) ) {
			$request(
				'POST',
				'/wc-admin/onboarding/profile/update-store-currency-and-measurement-units',
				[ 'country_code' => $store_country ]
			);
		}

		$request( 'PUT', '/wc/v3/payment_gateways/cod', [ 'enabled' => true ] );

		// "Launch your store" only exists in newer WooCommerce versions, which create new stores in
		// coming soon mode
		if ( 'yes' === get_option( 'woocommerce_coming_soon' ) ) {
			update_option( 'woocommerce_coming_soon', 'no' );
		}

		echo "WooCommerce set up.\n";
	}
);
