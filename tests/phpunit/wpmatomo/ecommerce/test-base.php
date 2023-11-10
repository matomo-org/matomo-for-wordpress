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
	/**
	 * @var MatomoTestEcommerce
	 */
	protected $base;

	public function setUp(): void {
		parent::setUp();
		$this->settings = new Settings();

		/*
		 * use a custom object which provide public methods of the Base class
		 */
		$this->base = new MatomoTestEcommerce( new AjaxTracker( $this->settings ) );
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

		$this->assertSame(
			'<script ' . $this->get_type_attribute() . '>' . PHP_EOL .
			'window._paq = window._paq || []; window._paq.push(["setEcommerceView","sku","product-title",[],50]);' . PHP_EOL .
			'</script>' . PHP_EOL,
			$this->base->wrap_script( $this->base->make_matomo_js_tracker_call( $params ) )
		);
	}
}
