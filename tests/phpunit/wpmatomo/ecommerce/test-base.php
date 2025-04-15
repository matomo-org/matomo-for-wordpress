<?php

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\AjaxTracker;
use WpMatomo\Ecommerce\MatomoTestEcommerce;
use WpMatomo\Settings;

class BaseTest extends MatomoAnalytics_TestCase {
	/**
	 * @var Settings
	 */
	protected $settings;

	private $ajax_tracker;

	/**
	 * @var MatomoTestEcommerce
	 */
	protected $base;

	public function setUp(): void {
		parent::setUp();
		$this->settings = new Settings();

		$this->ajax_tracker = new class( $this->settings ) extends AjaxTracker {
			public $tracked_urls = [];

			public $force_failure = false;

			protected function sendRequest( $url, $method = 'GET', $data = null, $force = false ) {
				if ( $this->force_failure ) {
					throw new \Exception( 'forced error' );
				}

				$this->tracked_urls[] = $method . ' ' . $url;
			}
		};

		/*
		 * use a custom object which provide public methods of the Base class
		 */
		$this->base = new MatomoTestEcommerce( $this->ajax_tracker, $this->settings );
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
		if ( getenv( 'WORDPRESS_VERSION' ) && ( getenv( 'WORDPRESS_VERSION' ) !== 'latest' && version_compare( getenv( 'WORDPRESS_VERSION' ), '6.4', '<' ) ) ) {
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

	public function test_wrap_script_does_server_side_tracking_when_should_track_in_background() {
		$this->base->set_force_track_in_background( true );

		$tracking_code = '';

		$tracking_code .= $this->base->make_matomo_js_tracker_call(
			[
				'addEcommerceItem',
				'sku1',
				'title',
				[ 'cat1', 'cat2' ],
				123,
				2,
			]
		);

		$tracking_code .= $this->base->make_matomo_js_tracker_call(
			[
				'trackEcommerceCartUpdate',
				234,
			]
		);

		$tracking_code .= $this->base->make_matomo_js_tracker_call(
			[
				'addEcommerceItem',
				'sku1',
				'title',
				[ 'cat1', 'cat2' ],
				123,
				3,
			]
		);

		$tracking_code .= $this->base->make_matomo_js_tracker_call(
			[
				'trackEcommerceOrder',
				'order1',
				356,
			]
		);

		$result = $this->base->wrap_script( $tracking_code );

		$this->assertIsString( $result );
		$this->assertEquals( '', $result );

		$expected_requests = [
			'GET http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&url=&urlref=&idgoal=0&revenue=234&ec_items=%5B%5B%22sku1%22%2C%22title%22%2C%5B%22cat1%22%2C%22cat2%22%5D%2C%22123%22%2C2%5D%5D',
			'GET http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&url=&urlref=&idgoal=0&revenue=356&ec_items=%5B%5B%22sku1%22%2C%22title%22%2C%5B%22cat1%22%2C%22cat2%22%5D%2C%22123%22%2C3%5D%5D&ec_id=order1',
		];

		$actual_urls = $this->remove_random_params_from( $this->ajax_tracker->tracked_urls );

		$this->assertEquals( $expected_requests, $actual_urls );
	}

	public function test_wrap_script_ignores_server_side_tracking_errors_for_non_order_tracking_methods() {
		$this->base->set_force_track_in_background( true );
		$this->ajax_tracker->force_failure = true;

		$tracking_code = $this->base->make_matomo_js_tracker_call(
			[
				'addEcommerceItem',
				'sku1',
				'title',
				[ 'cat1', 'cat2' ],
				123,
				2,
			]
		);

		$result = $this->base->wrap_script( $tracking_code );
		$this->assertIsString( $result );
		$this->assertEquals( '', $result );

		$this->assertEmpty( $this->ajax_tracker->tracked_urls );

		$tracking_code = $this->base->make_matomo_js_tracker_call(
			[
				'trackEcommerceCartUpdate',
				234,
			]
		);

		$result = $this->base->wrap_script( $tracking_code );
		$this->assertIsString( $result );
		$this->assertEquals( '', $result );

		$this->assertEmpty( $this->ajax_tracker->tracked_urls );
	}

	public function test_wrap_script_notifies_caller_of_failed_server_side_order_tracking() {
		$this->base->set_force_track_in_background( true );
		$this->ajax_tracker->force_failure = true;

		$tracking_code = $this->base->make_matomo_js_tracker_call(
			[
				'addEcommerceItem',
				'sku1',
				'title',
				[ 'cat1', 'cat2' ],
				123,
				2,
			]
		);

		$result = $this->base->wrap_script( $tracking_code );
		$this->assertIsString( $result );
		$this->assertEquals( '', $result );

		$this->assertEmpty( $this->ajax_tracker->tracked_urls );

		$tracking_code .= $this->base->make_matomo_js_tracker_call(
			[
				'trackEcommerceOrder',
				'order1',
				356,
			]
		);

		$result = $this->base->wrap_script( $tracking_code );
		$this->assertFalse( $result );

		$this->assertEmpty( $this->ajax_tracker->tracked_urls );
	}

	private function remove_random_params_from( $urls ) {
		foreach ( $urls as &$url ) {
			$params_to_remove = [ 'r', '_id', '_idts' ];
			foreach ( $params_to_remove as $param ) {
				$url = preg_replace( '/&' . preg_quote( $param, '/' ) . '=[^&]+/', '', $url );
			}
		}
		return $urls;
	}
}
