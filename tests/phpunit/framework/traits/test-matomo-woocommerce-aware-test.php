<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

trait MatomoWooCommerceAwareTest {

	public function manually_load_woocommerce() {
		require_once ABSPATH . 'wp-content/plugins/woocommerce/woocommerce.php';

		WC()->frontend_includes();
		WC()->include_template_functions();
		WC_Install::install();
		\Automattic\WooCommerce\Blocks\Package::init();
	}

	public function disable_woocommerce_cookies() {
		add_filter(
			'woocommerce_set_cookie_enabled',
			function () {
				return false;
			}
		);
	}

	public function initialize_wc_session() {
		WC()->initialize_session();
		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	public function unset_wc_session() {
		WC()->session = null;
	}
}
