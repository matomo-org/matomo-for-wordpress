<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

class SettingsTest extends MatomoUnit_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings = $this->make_settings();
	}

	private function make_settings() {
		return new Settings();
	}

	public function test_is_multi_site() {
		$this->assertSame( MULTISITE, $this->settings->is_multisite() );
	}

	public function test_is_network_enabled() {
		$this->assertFalse( $this->settings->is_network_enabled() );
	}

	public function test_get_global_option_returns_default_value_when_no_value_is_set() {
		$this->assertSame( 'disabled', $this->settings->get_global_option( 'track_mode' ) );
	}

	public function test_set_global_option_get_global_option() {
		$this->settings->set_global_option( 'track_mode', 'manually' );
		$this->assertSame( 'manually', $this->settings->get_global_option( 'track_mode' ) );
	}

	public function test_set_global_option_does_not_persist_change_unless_saved() {
		$this->settings->set_global_option( 'track_mode', 'manually' );

		$this->assertEquals( 'disabled', $this->make_settings()->get_global_option( 'track_mode' ) );

		$this->settings->save();

		$this->assertEquals( 'manually', $this->make_settings()->get_global_option( 'track_mode' ) );
	}

	public function test_set_global_option_converts_type() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_ecommerce'         => '0',
				'track_search'            => 1,
				'track_404'               => '',
				'limit_cookies_visitor'   => '3434343',
				'tagmanger_container_ids' => '',
				'add_post_annotations'    => array( 'foo' ),
			)
		);

		$this->assertSame( false, $this->settings->get_global_option( 'track_ecommerce' ) );
		$this->assertSame( true, $this->settings->get_global_option( 'track_search' ) );
		$this->assertSame( false, $this->settings->get_global_option( 'track_404' ) );
		$this->assertSame( 3434343, $this->settings->get_global_option( 'limit_cookies_visitor' ) );
		$this->assertSame( array(), $this->settings->get_global_option( 'tagmanger_container_ids' ) );
		$this->assertSame( array( 'foo' ), $this->settings->get_global_option( 'add_post_annotations' ) );
	}

	public function test_set_option_converts_type() {
		$this->settings->apply_changes(
			array(
				'noscript_code'                            => 2392,
				Settings::OPTION_LAST_TRACKING_CODE_UPDATE => '3493939',
			)
		);

		$this->assertSame( '2392', $this->settings->get_option( 'noscript_code' ) );
		$this->assertSame( 3493939, $this->settings->get_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE ) );
	}

	public function test_get_customised_global_settings_nothing_customised() {
		$settings = $this->settings->get_customised_global_settings();
		unset( $settings['core_version'] ); // always changes every time we update core so we dont want to look at exact value
		unset( $settings['version_history'] ); // always changes every time we update core so we dont want to look at exact value

		$this->assertSame( array(), $settings );
	}

	public function test_get_customised_global_settings_some_customised() {
		$this->settings->set_global_option( 'track_mode', 'manually' );
		$this->settings->set_global_option( 'track_ecommerce', '0' );

		$settings = $this->settings->get_customised_global_settings();
		unset( $settings['core_version'] ); // always changes every time we update core so we dont want to look at exact value
		unset( $settings['version_history'] ); // always changes every time we update core so we dont want to look at exact value

		$this->assertEquals(
			array(
				'track_mode'      => 'manually',
				'track_ecommerce' => 0,
			),
			$settings
		);
	}

	public function test_get_option_returns_default_value_when_no_value_is_set() {
		$this->assertSame( 0, $this->settings->get_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE ) );
	}

	public function test_save_when_nothing_changed() {
		$update_option_triggered = false;
		add_action(
			'pre_update_option',
			function () use ( &$update_option_triggered ) {
				$update_option_triggered = true;
			}
		);

		$this->settings->save();
		$this->assertFalse( $update_option_triggered );

		$this->settings->set_global_option( 'track_ecommerce', false );
		$this->settings->save();
		$this->assertTrue( $update_option_triggered );
	}

	public function test_save_triggers_event_only_for_changed_fields() {
		$this->settings->set_global_option( 'track_mode', 'disabled' );
		$this->settings->save();

		$ecommerce_triggered = false;
		add_action(
			'matomo_setting_change_track_ecommerce',
			function () use ( &$ecommerce_triggered ) {
				$ecommerce_triggered = true;
			}
		);

		$track_mode_triggered = false;
		add_action(
			'matomo_setting_change_track_mode',
			function () use ( &$track_mode_triggered ) {
				$track_mode_triggered = true;
			}
		);

		$this->settings->set_global_option( 'track_ecommerce', false );
		$this->settings->set_global_option( 'track_mode', 'disabled' );

		$this->settings->save();

		$this->assertTrue( $ecommerce_triggered );
		$this->assertFalse( $track_mode_triggered );
	}

	public function test_set_option_get_option() {
		$test_value = 'var foo = "bar";';
		$this->settings->set_option( 'tracking_code', $test_value );
		$this->assertSame( $test_value, $this->settings->get_option( 'tracking_code' ) );
	}

	public function test_set_option_does_not_persist_change_unless_saved() {
		$test_value = 'var foo = "bar";';
		$this->settings->set_option( 'tracking_code', $test_value );

		$this->assertEquals( '', $this->make_settings()->get_option( 'tracking_code' ) );

		$this->settings->save();

		$this->assertEquals( $test_value, $this->make_settings()->get_option( 'tracking_code' ) );
	}

	public function test_apply_tracking_related_changes_updates_last_tracking_setting_change() {
		$this->assertSame( 0, $this->settings->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );
		$this->assertSame( 0, $this->settings->get_global_option( 'last_settings_update' ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_tracking_related_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		$this->assertGreaterThanOrEqual( time() - 2, $this->settings->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );
		$this->assertGreaterThanOrEqual( time() - 2, $this->settings->get_global_option( 'last_settings_update' ) );
	}

	public function test_apply_tracking_related_changes_persists_changes() {
		$this->assertSame( 0, $this->settings->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_tracking_related_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		$this->assertGreaterThanOrEqual( time() - 2, $this->make_settings()->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );
		$this->assertEquals( $test_value, $this->make_settings()->get_option( 'tracking_code' ) );
	}

	public function test_apply_changes_updates_last_setting_change_time() {
		$this->assertSame( 0, $this->settings->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );
		$this->assertSame( 0, $this->settings->get_global_option( 'last_settings_update' ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		$this->assertGreaterThanOrEqual( time() - 2, $this->settings->get_global_option( 'last_settings_update' ) );
		// tracking settings should remain unchanged
		$this->assertGreaterThanOrEqual( 0, $this->settings->get_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE ) );
	}

	public function test_apply_changes_persists_changes() {
		$this->assertSame( 0, $this->settings->get_global_option( 'last_settings_update' ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		$this->assertEquals( $test_value, $this->make_settings()->get_option( 'tracking_code' ) );
	}

	public function test_get_js_tracking_code_returns_js_tracking_code() {
		$this->assertSame( '', $this->settings->get_js_tracking_code() );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		$this->assertSame( $test_value, $this->settings->get_js_tracking_code() );
	}

	public function test_get_tracking_cookie_domain_no_cookie_domain() {
		$this->assertSame( '', $this->settings->get_tracking_cookie_domain() );
	}

	public function test_get_tracking_cookie_domain_returns_cookie_domain() {
		$this->settings->set_global_option( 'track_across', true );
		$this->assertSame( '*.example.org', $this->settings->get_tracking_cookie_domain() );
	}

	public function test_get_noscript_tracking_code_returns_noscript_tracking_code() {
		$this->assertSame( '', $this->settings->get_noscript_tracking_code() );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'noscript_code' => $test_value,
			)
		);

		$this->assertSame( $test_value, $this->settings->get_noscript_tracking_code() );
	}

	public function test_get_js_tracking_code_returns_js_tracking_code_network_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests();
		$this->assertSame( '', $this->settings->get_js_tracking_code() );
		$this->assertSame( '', $this->settings->get_global_option( 'js_manually' ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'tracking_code' => $test_value,
			)
		);

		// it was not yet set to be manually
		$this->assertSame( '', $this->settings->get_global_option( 'js_manually' ) );
		$this->assertSame( $test_value, $this->settings->get_js_tracking_code() );

		$this->settings->apply_changes(
			array(
				'track_mode' => TrackingSettings::TRACK_MODE_MANUALLY,
			)
		);
		$this->assertSame( $test_value, $this->settings->get_global_option( 'js_manually' ) );

		// to be sure we're testing the functionality correctly we're setting different tracking_code
		$this->settings->set_option( 'tracking_code', 'baz' );
		$this->assertSame( $test_value, $this->settings->get_js_tracking_code() );
	}

	public function test_get_noscript_tracking_code_returns_noscript_tracking_code_network_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests();
		$this->assertSame( '', $this->settings->get_noscript_tracking_code() );
		$this->assertSame( '', $this->settings->get_global_option( 'noscript_manually' ) );

		$test_value = 'var foo = "bar";';
		$this->settings->apply_changes(
			array(
				'noscript_code' => $test_value,
			)
		);

		// it was not yet set to be manually
		$this->assertSame( '', $this->settings->get_global_option( 'noscript_manually' ) );
		$this->assertSame( $test_value, $this->settings->get_noscript_tracking_code() );

		$this->settings->apply_changes(
			array(
				'track_mode' => TrackingSettings::TRACK_MODE_MANUALLY,
			)
		);

		$this->assertSame( $test_value, $this->settings->get_global_option( 'noscript_manually' ) );

		// to be sure we're testing the functionality of noscript correctly we're setting different noscript_code
		$this->settings->set_option( 'noscript_code', 'baz' );
		$this->assertSame( $test_value, $this->settings->get_noscript_tracking_code() );
	}
}
