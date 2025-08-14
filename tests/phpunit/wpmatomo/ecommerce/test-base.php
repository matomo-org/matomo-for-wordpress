<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Ecommerce\MatomoTestEcommerce;
use WpMatomo\Settings;

require_once __DIR__ . '/../../framework/mocks/mock-ajax-tracker.php';

/**
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 */
class BaseTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Settings
	 */
	protected $settings;

	/**
	 * @var TestAjaxTracker
	 */
	protected $test_tracker;

	/**
	 * @var \WpMatomo\Site\Sync\SyncConfig
	 */
	private $sync_config;

	/**
	 * @var MatomoTestEcommerce
	 */
	protected $base;

	private $old_remote_addr;

	public function setUp(): void {
		parent::setUp();

		$_COOKIE               = [];
		$this->old_remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : null;

		$this->settings     = new Settings();
		$this->test_tracker = new TestAjaxTracker( $this->settings );
		$this->sync_config  = new \WpMatomo\Site\Sync\SyncConfig( $this->settings );

		/*
		 * use a custom object which provide public methods of the Base class
		 */
		$this->base = new MatomoTestEcommerce( $this->test_tracker, $this->settings, $this->sync_config );
		$this->base->register_hooks();
	}

	public function tearDown(): void {
		if ( $this->old_remote_addr ) {
			$_SERVER['REMOTE_ADDR'] = $this->old_remote_addr;
		} else {
			unset( $_SERVER['REMOTE_ADDR'] );
		}

		parent::tearDown();
	}

	public function test_wrap_script_on_set_ecommerce_view() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'      => TrackingSettings::TRACK_MODE_DEFAULT,
				'track_ecommerce' => true,
			)
		);

		$params = array(
			'setEcommerceView',
			'sku',
			'product-title',
			array(),
			50,
		);

		$cdata_start = "/* <![CDATA[ */\n";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$this->assertSame(
			'<script ' . $this->get_type_attribute() . ">\n$cdata_start" .
			'window._paq = window._paq || []; window._paq.push(["setEcommerceView","sku","product-title",[],50]);' . PHP_EOL .
			"$cdata_end</script>" . PHP_EOL,
			$this->base->wrap_script( $this->base->make_matomo_js_tracker_call( $params ) )
		);
	}

	public function test_wrap_script_on_set_ecommerce_view_if_cookies_disabled() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'      => TrackingSettings::TRACK_MODE_DEFAULT,
				'track_ecommerce' => true,
				'disable_cookies' => true,
			)
		);

		$params = array(
			'setEcommerceView',
			'sku',
			'product-title',
			array(),
			50,
		);

		$cdata_start = "/* <![CDATA[ */\n";
		$cdata_end   = "/* ]]> */\n";
		if ( getenv( 'WORDPRESS_VERSION' ) && ( getenv( 'WORDPRESS_VERSION' ) !== 'latest' && version_compare( getenv( 'WORDPRESS_VERSION' ), '6.4', '<' ) ) ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$this->assertSame(
			'<script ' . $this->get_type_attribute() . ">\n$cdata_start" .
			'window._paq = window._paq || []; if (!window._paq.find || !window._paq.find(function (m) { return m[0] === "disableCookies"; })) {
	window._paq.push(["disableCookies"]);
} window._paq.push(["setEcommerceView","sku","product-title",[],50]);' . PHP_EOL .
			"$cdata_end</script>" . PHP_EOL,
			$this->base->wrap_script( $this->base->make_matomo_js_tracker_call( $params ) )
		);
	}

	public function test_wrap_script_outputs_code_when_not_tracking_in_background() {
		$this->base->should_track_background   = false;
		$this->base->supports_delayed_tracking = true;

		$script  = '';
		$script .= $this->base->make_matomo_js_tracker_call( [ 'trackEcommerceCartUpdate', 400 ] );
		$script .= $this->base->make_matomo_js_tracker_call( [ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ] );

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$script = $this->base->wrap_script( $script );

		$cdata_start = "\n/* <![CDATA[ */";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$script_type = $this->get_type_attribute();

		$expected = <<<EOF
<script $script_type>$cdata_start
window._paq = window._paq || []; window._paq.push(["trackEcommerceCartUpdate",400]);window._paq = window._paq || []; window._paq.push(["trackEcommerceOrder","orderid",300,200,40,60,0]);
$cdata_end</script>

EOF;

		$this->assertEquals( $expected, $script );

		$this->assertEmpty( $this->test_tracker->captured_urls );
		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );
		$this->assertEmpty( $this->base->session_data );

		$this->assertTrue( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	public function test_wrap_script_delays_background_tracking_via_event() {
		$this->base->should_track_background   = true;
		$this->base->supports_delayed_tracking = true;

		$this->base->make_matomo_js_tracker_call( [ 'trackEcommerceCartUpdate', 100 ] );
		$this->base->make_matomo_js_tracker_call( [ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ] );

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$script = $this->base->wrap_script( '' );
		$this->assertEmpty( $script );

		$this->assertEmpty( $this->test_tracker->captured_urls );
		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$tracking_time = $this->base->session_data['ajax_calls'][0]['tracking_time'];

		$session_data = $this->check_and_remove_delayed_tracking_times();

		$this->assertEquals(
			[
				[
					'calls'      => [
						[ 'trackEcommerceCartUpdate', 100 ],
						[ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ],
					],
					'visitor_id' => false,
					'ip'         => '127.0.0.1',
				],
			],
			$session_data
		);

		// check the event works properly
		$this->execute_scheduled_event( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->assertEquals(
			[
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=' . $tracking_time . '&_id=REMOVED&url=&urlref=&idgoal=0&revenue=100&ip_nonce=REMOVED&bots=1',
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=' . $tracking_time . '&_id=REMOVED&url=&urlref=&idgoal=0&revenue=300&ec_st=200&ec_tx=40&ec_sh=60&ec_id=orderid&ip_nonce=REMOVED&bots=1',
			],
			$this->test_tracker->captured_urls
		);

		$this->assertTrue( $this->base->has_order_been_tracked_already( 'orderid' ) );

		$this->base->should_track_background = false;
		$this->base->maybe_do_delayed_tracking_early();

		$this->assertEquals(
			[
				'ajax_calls' => [],
			],
			$this->base->session_data
		);
	}

	public function test_wrap_script_tracks_immediately_in_background_if_delaying_unsupported() {
		$this->base->should_track_background   = true;
		$this->base->supports_delayed_tracking = false;

		$this->base->make_matomo_js_tracker_call( [ 'trackEcommerceCartUpdate', 100 ] );
		$this->base->make_matomo_js_tracker_call( [ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ] );

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$script = $this->base->wrap_script( '' );
		$this->assertEmpty( $script );

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->assertEquals(
			[
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&_id=REMOVED&url=&urlref=&idgoal=0&revenue=100&bots=1',
				'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&_id=REMOVED&url=&urlref=&idgoal=0&revenue=300&ec_st=200&ec_tx=40&ec_sh=60&ec_id=orderid&bots=1',
			],
			$this->test_tracker->captured_urls
		);

		$this->assertEmpty( $this->base->session_data );

		$this->assertTrue( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	public function test_background_tracking_forwards_visitor_information_detected() {
		$visitor_id = '0123456789abcdef';
		$ip         = '2.3.4.5';

		$_SERVER['REMOTE_ADDR']   = $ip;
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		// recreate tracker since it detects cookies on construction
		$this->test_tracker = new TestAjaxTracker( $this->settings );
		$this->base         = new MatomoTestEcommerce( $this->test_tracker, $this->settings, $this->sync_config );
		$this->base->register_hooks();

		$this->base->should_track_background   = true;
		$this->base->supports_delayed_tracking = true;

		$this->base->make_matomo_js_tracker_call( [ 'trackEcommerceCartUpdate', 100 ] );

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$script = $this->base->wrap_script( '' );
		$this->assertEmpty( $script );

		$this->assertEmpty( $this->test_tracker->captured_urls );
		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$tracking_time = $this->base->session_data['ajax_calls'][0]['tracking_time'];

		$session_data = $this->check_and_remove_delayed_tracking_times();

		$this->assertEquals(
			[
				[
					'calls'      => [
						[
							'trackEcommerceCartUpdate',
							100,
						],
					],
					'visitor_id' => $visitor_id,
					'ip'         => $ip,
				],
			],
			$session_data
		);

		$this->assertFalse( $this->base->has_order_been_tracked_already( 'orderid' ) );

		// check the event works properly
		$this->execute_scheduled_event( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->assertEquals(
			[
				[
					'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&cdt=' . $tracking_time . '&cid=0123456789abcdef&url=&urlref=&idgoal=0&revenue=100&ip_nonce=REMOVED&bots=1',
					[
						'method'  => 'GET',
						'headers' => [
							\WpMatomo\AjaxTracker::IP_ADDRESS_FORWARDING_HEADER => $ip,
						],
					],
				],
			],
			$this->test_tracker->captured_requests
		);

		// no ecommerce order, just cart update
		$this->assertFalse( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	public function test_maybe_do_delayed_tracking_early_does_nothing_if_delayed_tracking_unsupported() {
		$this->base->supports_delayed_tracking = false;

		$this->base->session_data = [
			'ajax_calls' => [
				'calls' => [
					[ 'trackEcommerceCartUpdate', 100 ],
					[ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ],
				],
			],
		];

		ob_start();
		$this->base->maybe_do_delayed_tracking_early();
		$output = ob_get_clean();

		$this->assertEmpty( $output );

		$this->assertFalse( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	public function test_maybe_do_delayed_tracking_early_does_nothing_if_the_current_request_requires_background_tracking() {
		$this->base->supports_delayed_tracking = true;
		$this->base->should_track_background   = true;

		$this->base->session_data = [
			'ajax_calls' => [
				[
					'calls' => [
						[ 'trackEcommerceCartUpdate', 100 ],
						[ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ],
					],
				],
			],
		];

		ob_start();
		$this->base->maybe_do_delayed_tracking_early();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEmpty( $output );

		$this->assertFalse( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	public function test_maybe_do_delayed_tracking_uses_detected_visitor_information_in_session() {
		$this->base->supports_delayed_tracking = true;

		$calls = [
			'delayed_time' => time() + 180,
			'calls'        => [
				[ 'trackEcommerceCartUpdate', 100 ],
				[ 'trackEcommerceOrder', 'orderid', 300, 200, 40, 60, 0 ],
			],
		];

		wp_schedule_single_event( $calls['delayed_time'], \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK, [ $calls ] );

		$this->assert_event_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->base->session_data = [ 'ajax_calls' => [ $calls ] ];

		ob_start();
		$this->base->maybe_do_delayed_tracking_early();
		$output = ob_get_contents();
		ob_end_clean();

		$cdata_start = "\n/* <![CDATA[ */";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$script_type = $this->get_type_attribute();

		$expected_tracking_code = <<<EOF
<script $script_type>$cdata_start
window._paq = window._paq || []; window._paq.push(["trackEcommerceCartUpdate",100]);window._paq = window._paq || []; window._paq.push(["trackEcommerceOrder","orderid",300,200,40,60,0]);
$cdata_end</script>

EOF;

		$this->assertEquals( $expected_tracking_code, $output );

		$this->assertEquals(
			[
				'ajax_calls' => [],
			],
			$this->base->session_data
		);

		$this->assert_event_not_scheduled( \WpMatomo\Ecommerce\Base::DELAYED_SERVER_SIDE_TRACKING_HOOK );

		$this->assertTrue( $this->base->has_order_been_tracked_already( 'orderid' ) );
	}

	private function check_and_remove_delayed_tracking_times() {
		$ajax_calls = $this->base->session_data['ajax_calls'];
		foreach ( $ajax_calls as &$call ) {
			$delayed_time  = $call['delayed_time'];
			$tracking_time = $call['tracking_time'];

			$this->assertEquals( $tracking_time + 180, $delayed_time );

			unset( $call['delayed_time'] );
			unset( $call['tracking_time'] );
		}
		return $ajax_calls;
	}
}
