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
require_once __DIR__ . '/../../framework/mocks/mock-ajax-tracker.php';

class TestWoocommerce extends \WpMatomo\Ecommerce\Woocommerce {

	public $should_track_background = false;

	public function setTracker( $tracker ) {
		$this->tracker = $tracker;
	}

	public function getTracker() {
		return $this->tracker;
	}

	protected function should_track_background() {
		return $this->should_track_background;
	}
}

/**
 * @package matomo
 */
class WoocommerceTest extends MatomoAnalytics_TestCase {

	use MatomoWooCommerceAwareTest;

	private $product_id;

	/**
	 * @var TestWoocommerce
	 */
	private $test_instance;

	private $settings;

	private $tracker;

	public function setUp(): void {
		parent::setUp();

		if ( ! $this->is_test_case_runnable() ) {
			$this->markTestSkipped( 'cannot run test in current environment' );
		}

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
		$this->make_test_instance();
		$this->set_visitor_id_cookie( '0123456789abcdef' );

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();

		$session_data = WC()->session->get( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_SESSION_KEY );
		$this->assertNotEmpty( $session_data );

		foreach ( $session_data as &$entry ) {
			$tracking_time = $entry['tracking_time'];
			$delayed_time  = $entry['delayed_time'];

			$this->assertEquals( $tracking_time + 180, $delayed_time );

			unset( $entry['tracking_time'] );
			unset( $entry['delayed_time'] );
		}

		$this->assertEquals(
			[
				[
					'calls'      => [
						[
							'addEcommerceItem',
							10,
							'a tiny hat',
							[ 'Uncategorized' ],
							12,
							2,
						],
						[
							'trackEcommerceCartUpdate',
							24,
						],
					],
					'visitor_id' => '0123456789abcdef',
					'ip'         => '127.0.0.1',
				],
			],
			$session_data
		);

		$tracking_code = $this->simulate_order_received_page_visit();

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$session_data = WC()->session->get( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_SESSION_KEY );
		$this->assertEmpty( $session_data );

		$cdata_start = "\n/* <![CDATA[ */";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$script_type = $this->get_type_attribute();

		$expected_code = <<<EOF
<script $script_type>$cdata_start
window._paq = window._paq || []; window._paq.push(["addEcommerceItem","10","a tiny hat",["Uncategorized"],12,2]);window._paq = window._paq || []; window._paq.push(["trackEcommerceCartUpdate","24.00"]);
$cdata_end</script>

EOF;

		$this->assertEquals( $expected_code, $tracking_code );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_when_order_processed_before_order_received() {
		$this->make_test_instance();
		$this->set_visitor_id_cookie( '0123456789abcdef' );

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->mark_order_processing();

		// two events scheduled, one for add to cart, another for the ecommerce order
		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, 2 );

		$tracking_code = $this->simulate_order_received_page_visit();

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$cdata_start = "\n/* <![CDATA[ */";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$script_type = $this->get_type_attribute();

		$expected_code = <<<EOF
<script $script_type>$cdata_start
window._paq = window._paq || []; window._paq.push(["addEcommerceItem","10","a tiny hat",["Uncategorized"],12,2]);window._paq = window._paq || []; window._paq.push(["trackEcommerceCartUpdate","24.00"]);
$cdata_end</script>
<script $script_type>$cdata_start
window._paq = window._paq || []; window._paq.push(["addEcommerceItem","10","a tiny hat",["Uncategorized"],12,2]);window._paq = window._paq || []; window._paq.push(["trackEcommerceOrder","11","24.00",24,"0","0",0]);
$cdata_end</script>

EOF;

		$this->assertEquals( $expected_code, $tracking_code );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_order_with_late_order_received_visited() {
		$this->make_test_instance();
		$this->set_visitor_id_cookie( '0123456789abcdef' );

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->mark_order_processing();

		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, 2 );

		$this->execute_scheduled_event( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, true );

		$captured_urls = array_map(
			function ( $url ) {
				return preg_replace( '/&cdt=[^&]+/', '&cdt=REMOVED', $url );
			},
			$this->tracker->captured_urls
		);

		$this->assertEquals(
			[
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=REMOVED&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ip_nonce=REMOVED&bots=1',
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=REMOVED&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=24.00&ec_st=24&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ec_id=11&ip_nonce=REMOVED&bots=1',
			],
			$captured_urls
		);

		$tracking_code = $this->simulate_order_received_page_visit();
		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );
		$this->assertEmpty( $tracking_code );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_order_tracking_delayed_with_no_visitorid() {
		// no cookie when user adding to cart

		$this->make_test_instance();

		$this->simulate_add_to_cart();
		$this->simulate_payment_complete_with_pending_status();
		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->mark_order_processing();
		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, 2 );

		$this->set_visitor_id_cookie( '0123456789abcdef' );

		$this->execute_scheduled_event( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, true );

		$tracking_code = $this->simulate_order_received_page_visit();
		$this->assertEmpty( $tracking_code );

		$captured_urls = array_map(
			function ( $url ) {
				return preg_replace( '/&cdt=[^&]+/', '&cdt=REMOVED', $url );
			},
			$this->tracker->captured_urls
		);

		// check that there is no cid= parameter which would be set when detecting a visitor ID cookie,
		// just an _id= parameter
		$this->assertEquals(
			[
				// ecommerce cart tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=REMOVED&_id=REMOVED&url=&urlref=&idgoal=0&revenue=24.00&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ip_nonce=REMOVED&bots=1',
				// ecommerce order tracking request
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=REMOVED&_id=REMOVED&url=&urlref=&idgoal=0&revenue=24.00&ec_st=24&ec_items=%5B%5B%2210%22%2C%22a+tiny+hat%22%2C%5B%22Uncategorized%22%5D%2C%2212%22%2C2%5D%5D&ec_id=11&ip_nonce=REMOVED&bots=1',
			],
			$captured_urls
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
		$this->test_instance->should_track_background = true;

		$this->doing_ajax();

		try {
			$_POST['product_id'] = $this->product_id;
			$_POST['quantity']   = 2;

			try {
				WC_AJAX::add_to_cart();
			} catch ( \WPDieException $ex ) {
				// ignore
			}
			ob_get_clean(); // the ajax method calls ob_start() at the beginning
		} finally {
			$this->stopped_doing_ajax();
		}
	}

	private function simulate_payment_complete_with_pending_status() {
		$this->test_instance->should_track_background = true;

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
			$_POST['billing_phone']      = '123-456-7890';
			$_POST['payment_method']     = 'cod';

			try {
				WC_AJAX::checkout();
			} catch ( \WPDieException $ex ) {
				// ignore
			}
		} finally {
			remove_filter( 'woocommerce_order_needs_payment', '__return_false' );
			remove_filter( 'woocommerce_payment_complete_order_status', $complete_order_status_cb );
			$this->stopped_doing_ajax();
			ob_end_clean();
		}

		$order = $this->get_order();
		$this->assertEquals( 'pending', $order->get_status() );
	}

	private function simulate_order_received_page_visit() {
		global $wp;

		add_filter( 'woocommerce_is_order_received_page', '__return_true' );

		try {
			$this->test_instance->should_track_background = false;

			$this->assertTrue( is_order_received_page() );

			$order    = $this->get_order();
			$order_id = $order->get_id();

			$wp->query_vars['order-received'] = $order_id;

			ob_start();
			try {
				// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				do_action( 'wp_footer' );
			} finally {
				$result = ob_get_clean();
			}

			// remove script added by woocommerce in wp_footer event
			$result = preg_replace( '%<script type="application/ld\+json">.*?</script>%', '', $result );

			return $result;
		} finally {
			remove_filter( 'woocommerce_is_order_received_page', '__return_true' );

			unset( $wp->query_vars['order-received'] );
		}
	}

	private function mark_order_processing() {
		$this->test_instance->should_track_background = true;

		$order = $this->get_order();
		$this->assertTrue( $order->update_status( 'processing' ) );
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

	private function make_test_instance() {
		// NOTE: this can't be put into the setup, since AjaxTracker loads the visitor ID cookie during
		// construction, and we want to change it during tests
		$this->settings    = new \WpMatomo\Settings();
		$this->tracker     = new TestAjaxTracker( $this->settings );
		$this->sync_config = new \WpMatomo\Site\Sync\SyncConfig( $this->settings );

		$this->test_instance = new TestWoocommerce( $this->tracker, $this->settings, $this->sync_config );
		$this->test_instance->register_hooks();
	}

	private function is_test_case_runnable() {
		$wordpress_version = getenv( 'WORDPRESS_VERSION' );
		return version_compare( $wordpress_version, '5.3', '>' )
			|| 'trunk' === $wordpress_version
			|| 'latest' === $wordpress_version;
	}

	private function set_visitor_id_cookie( $visitor_id ) {
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		// recreate the tracker so the tracker will read the new visitor ID
		$captured_urls                = $this->tracker->captured_urls;
		$this->tracker                = new TestAjaxTracker( $this->settings );
		$this->tracker->captured_urls = $captured_urls;

		$this->test_instance->setTracker( $this->tracker );
		$this->assertEquals( $visitor_id, $this->test_instance->getTracker()->forcedVisitorId );
	}

	/**
	 * @return string
	 */
	protected function get_type_attribute() {
		$type = '';
		if ( function_exists( 'wp_get_inline_script_tag' ) && ! is_admin() && ! current_theme_supports( 'html5', 'script' ) ) {
			$type = 'type="text/javascript"';
		}
		return $type;
	}
}
