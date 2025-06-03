<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

// for @runInSeparateProcess annotation used below
require_once __DIR__ . '/../../framework/traits/test-matomo-woocommerce-aware-test.php';

/**
 * @package matomo
 */
class WoocommerceTest extends MatomoAnalytics_TestCase {

	use MatomoWooCommerceAwareTest;

	private $product_id;

	private $test_instance;

	private $settings;

	private $tracker;

	public function setUp(): void {
		parent::setUp();

		$this->clear_superglobals();

		$this->manually_load_woocommerce();
		$this->disable_woocommerce_cookies();

		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		do_action( 'init' ); // setup woocommerce

		$this->initialize_wc_session();
		$this->add_test_product();
		$this->setup_payment_gateway();
	}

	public function tearDown(): void {
		$this->unset_wc_session();
		$this->clear_superglobals();
		parent::tearDown();
	}

	public function capture_url( $url ) {
		$this->captured_urls[] = $url;
		return $url;
	}

	protected function assert_post_conditions() {
		// do nothing instead of checking for deprecated function usage
		// (woocommerce has many deprecated function uses)
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_when_expected_user_flow() {
		$visitor_id               = '0123456789abcdef';
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		$this->make_test_instance();

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->simulate_order_received_page_visit();

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Woocommerce::DELAYED_TRACKING_EVENT_NAME );

		$this->assertEquals(
			[
				// ecommerce cart tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&bots=1',
				// ecommerce order tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_st=24&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ec_id=11&bots=1',
			],
			$this->tracker->captured_urls
		);
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_when_order_processed_before_order_received() {
		$visitor_id               = '0123456789abcdef';
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		$this->make_test_instance();

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->mark_order_processing();

		$this->assertEquals(
			[
				// only ecommerce cart tracking request since we haven't visited order received yet
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&bots=1',
			],
			$this->tracker->captured_urls
		);

		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Woocommerce::DELAYED_TRACKING_EVENT_NAME );

		$this->simulate_order_received_page_visit();

		// set different visitor ID to simulate the event executing during another visitor's request
		$this->tracker->forcedVisitorId = '4444456789abcdef';

		$this->execute_next_scheduled_event( \WpMatomo\Ecommerce\Woocommerce::DELAYED_TRACKING_EVENT_NAME );

		$this->assertEquals(
			[
				// ecommerce cart tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&bots=1',
				// ecommerce order tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_st=24&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ec_id=11&bots=1',
			],
			$this->tracker->captured_urls
		);

		// check that the detected visitor ID was set back to what it was before tracking was executed
		$this->assertEquals( '4444456789abcdef', $this->tracker->forcedVisitorId );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_order_processed_after_order_received() {
		$visitor_id               = '0123456789abcdef';
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		$this->make_test_instance();

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->simulate_order_received_page_visit();

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Woocommerce::DELAYED_TRACKING_EVENT_NAME );

		$this->mark_order_processing();

		// if order status is changed after order received visited, the delayed tracking event should never be scheduled
		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Woocommerce::DELAYED_TRACKING_EVENT_NAME );

		$this->assertEquals(
			[
				// ecommerce cart tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&bots=1',
				// ecommerce order tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_st=24&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ec_id=11&bots=1',
			],
			$this->tracker->captured_urls
		);
	}

	private function add_test_product() {
		$product = new WC_Product_Simple();
		$product->set_name( 'a tiny hat' );
		$product->set_slug( 'a-truly-tiny-hat' );
		$product->set_regular_price( 12 );
		$product->set_description( 'so very tiny. so very hat. you know you want it.' );
		$product->save();

		$this->product_id = $product->get_id();
	}

	private function simulate_add_to_cart() {
		$this->doing_ajax();

		try {
			$_POST['product_id'] = $this->product_id;
			$_POST['quantity']   = 2;

			try {
				WC_AJAX::add_to_cart();
			} catch ( \WPDieException $ex ) {
				// ignore
			}
			ob_end_clean(); // the ajax method calls ob_start() at the beginning
		} finally {
			$this->stopped_doing_ajax();
		}
	}

	private function simulate_payment_complete_with_pending_status() {
		$this->doing_ajax();

		ob_start();
		add_filter( 'woocommerce_order_needs_payment', '__return_false' );
		$complete_order_status_cb = function () {
			return 'pending';
		};
		add_filter( 'woocommerce_payment_complete_order_status', $complete_order_status_cb, 9999 );
		try {
			$_REQUEST['_wpnonce'] = wp_create_nonce( 'woocommerce-process_checkout' );

			$_POST['billing_first_name'] = 'Alistair';
			$_POST['billing_last_name']  = 'McGroooovy';
			$_POST['billing_country']    = 'US';
			$_POST['billing_address_1']  = '375 11th St';
			$_POST['billing_city']       = 'San Francisco';
			$_POST['billing_state']      = 'CA';
			$_POST['billing_postcode']   = '94103-4313';
			$_POST['billing_email']      = 'alistair.mcgroooovy@myemail.com';
			$_POST['payment_method']     = 'cod';

			try {
				WC_AJAX::checkout();
			} catch ( \WPDieException $ex ) {
				// ignore
			}
			ob_end_flush();
		} finally {
			remove_filter( 'woocommerce_order_needs_payment', '__return_false' );
			remove_filter( 'woocommerce_payment_complete_order_status', $complete_order_status_cb );
			$this->stopped_doing_ajax();
			ob_end_flush();
		}

		$order = $this->get_order();
		$this->assertEquals( 'pending', $order->get_status() );
		exit;
	}

	private function simulate_order_received_page_visit() {
		global $wp;

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		try {
			$this->assertTrue( is_order_received_page() );

			$order    = $this->get_order();
			$order_id = $order->get_id();

			$wp->query_vars['order-received'] = $order_id;

			ob_start();
			try {
				// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_head' );
			} finally {
				ob_end_clean();
			}
		} finally {
			remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

			unset( $wp->query_vars['order-received'] );
		}
	}

	private function mark_order_processing() {
		$order = $this->get_order();
		$this->assertTrue( $order->update_status( 'processing' ) );
	}

	private function execute_next_scheduled_event( $event_name ) {
		$events = $this->get_events_scheduled( $event_name );
		$event  = reset( $events );

		// phpcs:disable PHPCompatibility.LanguageConstructs.NewLanguageConstructs.t_ellipsisFound
		// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
		do_action( $event_name, ...$event['args'] );
	}

	private function get_order() {
		$orders = wc_get_orders( [] );
		$this->assertCount( 1, $orders );
		$order = reset( $orders );
		return $order;
	}

	private function clear_superglobals() {
		$_COOKIE  = [];
		$_GET     = [];
		$_POST    = [];
		$_REQUEST = [];
	}

	private function setup_payment_gateway() {
		$options = get_option( 'woocommerce_cod_settings' );
		if ( ! is_array( $options ) ) {
			$options = [];
		}
		$options['enabled'] = 'yes';
		update_option( 'woocommerce_cod_settings', $options );
	}

	private function assert_event_not_scheduled( $event_name ) {
		$events = $this->get_events_scheduled( $event_name );
		$this->assertCount( 0, $events );
	}

	private function assert_event_scheduled( $event_name ) {
		$events = $this->get_events_scheduled( $event_name );
		$this->assertCount( 1, $events );
	}

	private function get_events_scheduled( $event_name ) {
		$result = [];

		$cron = _get_cron_array();
		foreach ( $cron as $cronhooks ) {
			if ( isset( $cronhooks[ $event_name ] ) ) {
				$result = array_merge( $result, $cronhooks[ $event_name ] );
			}
		}

		return $result;
	}


	private function make_test_instance() {
		// NOTE: this can't be put into the setup, since AjaxTracker loads the visitor ID cookie during
		// construction, and we want to change it during tests
		$this->settings = new \WpMatomo\Settings();
		$this->tracker  = new class( $this->settings ) extends \WpMatomo\AjaxTracker {
			public $captured_urls = [];

			protected function wp_remote_request( $url, $args ) {
				// remove random query params
				$url = preg_replace( '/&_id=[^&]+/', '', $url );
				$url = preg_replace( '/&r=[^&]+/', '', $url );
				$url = preg_replace( '/&_idts=[^&]+/', '', $url );
				$url = preg_replace( '/&pv_id=[^&]+/', '', $url );

				$this->captured_urls[] = $url;
			}
		};

		$this->test_instance = new class( $this->tracker, $this->settings ) extends \WpMatomo\Ecommerce\Woocommerce {
			protected function should_track_background() {
				return true;
			}
		};
		$this->test_instance->register_hooks();
	}
}
