<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

class SettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->create_set_super_admin();

		$this->settings = $this->make_settings();
	}

	private function make_settings() {
		return new Settings();
	}

	public function test_should_disable_addhandler() {
		$this->assertFalse( $this->settings->should_disable_addhandler() );
	}

	public function test_should_disable_addhandler_forced() {
		$this->settings->force_disable_addhandler = true;
		$disabled                                 = $this->settings->should_disable_addhandler();
		$this->settings->force_disable_addhandler = false;
		$this->assertTrue( $disabled );
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
		$this->assume_network_enabled( $this->settings );
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
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => $test_value,
			)
		);
		$this->assertSame( $test_value, $this->settings->get_global_option( 'js_manually' ) );

		// to be sure we're testing the functionality correctly we're setting different tracking_code
		$this->settings->set_option( 'tracking_code', 'baz' );
		$this->assertSame( $test_value, $this->settings->get_js_tracking_code() );
	}

	public function test_get_noscript_tracking_code_returns_noscript_tracking_code_network_enabled() {
		$this->assume_network_enabled( $this->settings );
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
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'noscript_code' => $test_value,
			)
		);

		$this->assertSame( $test_value, $this->settings->get_global_option( 'noscript_manually' ) );

		// to be sure we're testing the functionality of noscript correctly we're setting different noscript_code
		$this->settings->set_option( 'noscript_code', 'baz' );
		$this->assertSame( $test_value, $this->settings->get_noscript_tracking_code() );
	}

	public function test_apply_changes_should_keep_the_manual_tracking_code_when_no_tracking_code_is_supplied() {
		$this->assume_network_enabled( $this->settings );

		$manual_js       = 'var manual = "js";';
		$manual_noscript = '<noscript>manual</noscript>';

		$this->settings->apply_changes(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => $manual_js,
				'noscript_code' => $manual_noscript,
			]
		);

		$this->assertSame( $manual_js, $this->settings->get_global_option( 'js_manually' ) );
		$this->assertSame( $manual_noscript, $this->settings->get_global_option( 'noscript_manually' ) );

		// this blog's own copy drifts away from the network wide one
		$this->settings->set_option( 'tracking_code', 'code of this blog only' );
		$this->settings->set_option( 'noscript_code', 'noscript of this blog only' );
		$this->settings->save();

		// save a setting, without saving a new tracking or noscript code
		$this->settings->apply_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_MANUALLY ] );

		// check that the system wide tracking code has not been changed
		$this->assertSame( $manual_js, $this->settings->get_global_option( 'js_manually' ) );
		$this->assertSame( $manual_noscript, $this->settings->get_global_option( 'noscript_manually' ) );
	}

	/**
	 * @dataProvider get_test_data_for_get_matomo_major_version
	 */
	public function test_get_matomo_major_version( $core_version, $expected_major ) {
		$this->settings->set_global_option( 'core_version', $core_version );

		$actual = $this->settings->get_matomo_major_version();

		$this->assertEquals( $expected_major, $actual );
	}

	public function get_test_data_for_get_matomo_major_version() {
		return [
			[ '5.1.3', 5 ],
			[ '4.3.2-b1', 4 ],
			[ '5.2.0-rc3', 5 ],
			[ '', 0 ],
			[ null, 0 ],
		];
	}

	public function test_excluded_user_agent_with_comma_can_be_saved_through_mwp() {
		$user_agent        = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36';
		$simple_user_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.7; rv:128.0) Gecko/20100101 Firefox/128.0';

		$this->settings->set_global_user_agent_exclusions( [ $user_agent, $simple_user_agent ] );
		$this->settings->save();

		$settings2         = new Settings();
		$saved_user_agents = $settings2->get_global_user_agent_exclusions();
		$this->assertEquals( [ $user_agent, $simple_user_agent ], $saved_user_agents );
	}

	public function test_get_global_user_agent_exclusions_prioritizes_mwp_stored_user_agents() {
		$user_agents = [ 'testuseragent', 'anothertestuseragent' ];
		$user_agents = implode( ',', $user_agents );

		\Piwik\Plugins\SitesManager\API::getInstance()->setGlobalExcludedUserAgents( $user_agents );

		$other_user_agents = [ 'someotheruseragent', 'yetanotheruseragent' ];
		$this->settings->set_global_user_agent_exclusions( $other_user_agents );
		$this->settings->save();

		$settings          = new Settings();
		$saved_user_agents = $settings->get_global_user_agent_exclusions();

		$this->assertEquals( $other_user_agents, $saved_user_agents );
	}

	public function test_get_global_user_agent_exclusions_defaults_to_matomo_stored_user_agents() {
		$user_agents = [ 'testuseragent', 'anothertestuseragent' ];
		$user_agents = implode( ',', $user_agents );

		\Piwik\Plugins\SitesManager\API::getInstance()->setGlobalExcludedUserAgents( $user_agents );

		$settings          = new Settings();
		$saved_user_agents = $settings->get_global_user_agent_exclusions();

		$this->assertEquals( [ 'testuseragent', 'anothertestuseragent' ], $saved_user_agents );
	}

	public function test_excluded_user_agents_in_mwp_are_added_to_tracker_cache_general() {
		$user_agents = [ 'testuseragent', 'anothertestuseragent' ];
		$user_agents = implode( ',', $user_agents );

		\Piwik\Plugins\SitesManager\API::getInstance()->setGlobalExcludedUserAgents( $user_agents );

		$tracker_cache = \Piwik\Tracker\Cache::getCacheGeneral();
		$this->assertEquals( [ 'testuseragent', 'anothertestuseragent' ], $tracker_cache['global_excluded_user_agents'] );

		$other_user_agents = [ 'someotheruseragent', 'yetanotheruseragent' ];
		$this->settings->set_global_user_agent_exclusions( $other_user_agents );
		$this->settings->save();

		WpMatomo::$settings->init_settings(); // force static Settings instance to reload data

		// sanity check
		$matomo_user_agents = \Piwik\Plugins\SitesManager\API::getInstance()->getExcludedUserAgentsGlobal();
		$this->assertEquals( $user_agents, $matomo_user_agents );

		$tracker_cache = \Piwik\Tracker\Cache::getCacheGeneral();
		$this->assertEquals( $other_user_agents, $tracker_cache['global_excluded_user_agents'] );
	}

	public function test_get_global_user_agent_exclusions_should_fall_back_to_the_value_stored_before_it_became_a_per_blog_setting() {
		$this->settings->set_global_option( Settings::GLOBAL_USER_AGENT_EXCLUSIONS, [ 'agent stored before the upgrade' ] );
		$this->settings->save();

		$this->assertSame( [ 'agent stored before the upgrade' ], $this->make_settings()->get_global_user_agent_exclusions() );

		$this->settings->set_global_user_agent_exclusions( [ 'agent stored after the upgrade' ] );
		$this->settings->save();

		$this->assertSame( [ 'agent stored after the upgrade' ], $this->make_settings()->get_global_user_agent_exclusions() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_global_user_agent_exclusions_should_return_the_current_blogs_own_user_agents_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		$this->settings->set_global_user_agent_exclusions( [ 'agent of the first blog' ] );
		$this->settings->save();

		switch_to_blog( $blogid );

		try {
			$other_settings = $this->assume_network_enabled( new Settings() );
			$other_settings->set_global_user_agent_exclusions( [ 'agent of the second blog' ] );
			$other_settings->save();

			$this->assertSame( [ 'agent of the second blog' ], $this->settings->get_global_user_agent_exclusions() );
		} finally {
			restore_current_blog();
		}

		$this->assertSame( [ 'agent of the first blog' ], $this->settings->get_global_user_agent_exclusions() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_global_user_agent_exclusions_should_fall_back_to_the_network_wide_user_agents_a_blog_has_not_replaced() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		// where the setting was stored before it became a per blog one
		$this->settings->set_global_option( Settings::GLOBAL_USER_AGENT_EXCLUSIONS, [ 'agent of the network' ] );
		$this->settings->save();

		$blogid = self::factory()->blog->create();

		switch_to_blog( $blogid );

		try {
			$other_settings = $this->assume_network_enabled( new Settings() );

			$this->assertSame( [ 'agent of the network' ], $other_settings->get_global_user_agent_exclusions() );

			// a blog that saves a list of its own stops inheriting
			$other_settings->set_global_user_agent_exclusions( [] );
			$other_settings->save();

			$this->assertSame( [], $other_settings->get_global_user_agent_exclusions() );
			$this->assertSame( [], $this->assume_network_enabled( new Settings() )->get_global_user_agent_exclusions() );
		} finally {
			restore_current_blog();
		}

		// the blogs that never saved one still have what the network configured. read through a new
		// instance, so that this is what storage holds rather than what was loaded before the switch
		$this->assertSame(
			[ 'agent of the network' ],
			$this->assume_network_enabled( new Settings() )->get_global_user_agent_exclusions()
		);
	}

	public function test_get_stealth_roles_should_return_the_network_wide_roles_when_the_network_is_not_enabled() {
		$this->assertSame( [], $this->settings->get_stealth_roles() );

		$this->settings->apply_changes( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );

		$this->assertSame( [ 'editor' => true ], $this->make_settings()->get_stealth_roles() );
	}

	public function test_get_stealth_roles_should_ignore_a_role_that_is_stored_but_not_excluded() {
		$this->settings->apply_changes(
			[
				Settings::OPTION_KEY_STEALTH => [
					'editor' => '1',
					'author' => '',
				],
			]
		);

		$this->assertSame( [ 'editor' => true ], $this->make_settings()->get_stealth_roles() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_stealth_roles_should_merge_the_network_wide_roles_with_the_current_blogs_own() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$this->settings->apply_changes(
			[
				Settings::OPTION_KEY_STEALTH      => [ 'editor' => '1' ],
				Settings::OPTION_KEY_STEALTH_BLOG => [ 'author' => '1' ],
			]
		);

		$this->assertSame(
			[
				'editor' => true,
				'author' => true,
			],
			$this->assume_network_enabled( new Settings() )->get_stealth_roles()
		);

		$blogid = self::factory()->blog->create();

		switch_to_blog( $blogid );

		try {
			// the network's roles reach every blog, the first blog's own reach only itself
			$this->assertSame(
				[ 'editor' => true ],
				$this->assume_network_enabled( new Settings() )->get_stealth_roles()
			);
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_get_stealth_roles_should_not_let_a_blog_track_a_role_the_network_excluded() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$this->settings->apply_changes( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );
		$this->assertSame(
			[ 'editor' => true ],
			$this->assume_network_enabled( new Settings() )->get_stealth_roles()
		);

		// try to unset the network wide setting via the blog specific one, and check that
		// it doesn't change the get_stealth_roles() output.
		$this->settings->apply_changes( [ Settings::OPTION_KEY_STEALTH_BLOG => [] ] );
		$this->assertSame(
			[ 'editor' => true ],
			$this->assume_network_enabled( new Settings() )->get_stealth_roles()
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_save_should_not_fire_actions_for_changes_discarded_after_a_blog_switch() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		$action_fired = false;
		$on_change    = function () use ( &$action_fired ) {
			$action_fired = true;
		};
		add_action( 'matomo_setting_change_noscript_code', $on_change );

		try {
			$this->settings->set_option( 'noscript_code', 'pending change' );

			switch_to_blog( $blogid );

			$this->settings->save();

			$this->assertFalse( $action_fired );
			$this->assertSame( '', $this->settings->get_option( 'noscript_code' ) );
		} finally {
			restore_current_blog();
			remove_action( 'matomo_setting_change_noscript_code', $on_change );
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_apply_changes_should_not_overwrite_the_network_manual_tracking_code_when_another_blog_is_current() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$manual_code = '<script>NETWORK_WIDE</script>';
		$this->settings->apply_changes(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => $manual_code,
			]
		);

		$this->assertSame( $manual_code, $this->settings->get_global_option( 'js_manually' ) );

		switch_to_blog( $blogid );

		try {
			// what Site\Sync does when only a blog's metadata changed: no tracking code supplied
			$this->settings->apply_tracking_related_changes( [] );

			$this->assertSame( $manual_code, $this->settings->get_global_option( 'js_manually' ) );

			$stored = get_site_option( Settings::OPTION_GLOBAL, [] );
			$this->assertSame( $manual_code, $stored['js_manually'] );
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_get_option_should_return_the_current_blogs_value_after_a_blog_switch() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		$this->settings->set_option( 'tracking_code', 'code of the first blog' );
		$this->settings->save();

		switch_to_blog( $blogid );

		try {
			$other_settings = $this->assume_network_enabled( new Settings() );
			$other_settings->set_option( 'tracking_code', 'code of the second blog' );
			$other_settings->save();

			$this->assertSame( 'code of the second blog', $this->settings->get_option( 'tracking_code' ) );
		} finally {
			restore_current_blog();
		}

		$this->assertSame( 'code of the first blog', $this->settings->get_option( 'tracking_code' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_set_option_should_write_to_the_current_blog_after_a_blog_switch() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		$this->settings->set_option( 'tracking_code', 'code of the first blog' );
		$this->settings->save();

		switch_to_blog( $blogid );

		try {
			$this->settings->set_option( 'tracking_code', 'code of the second blog' );
			$this->settings->save();

			$stored_second = get_option( Settings::OPTION, [] );
		} finally {
			restore_current_blog();
		}

		$stored_first = get_option( Settings::OPTION, [] );

		$this->assertSame( 'code of the second blog', $stored_second['tracking_code'] );
		$this->assertSame( 'code of the first blog', $stored_first['tracking_code'] );
	}

	/**
	 * @group ms-required
	 */
	public function test_save_should_still_persist_global_changes_after_a_blog_switch_when_network_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_network_enabled( $this->settings );

		$blogid = self::factory()->blog->create();

		$this->settings->set_global_option( 'track_codeposition', 'header' );

		switch_to_blog( $blogid );

		try {
			$this->settings->save();

			$stored = get_site_option( Settings::OPTION_GLOBAL, [] );
			$this->assertArrayHasKey( 'track_codeposition', $stored );
			$this->assertSame( 'header', $stored['track_codeposition'] );
			$this->assertSame( 'header', $this->settings->get_global_option( 'track_codeposition' ) );
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_get_option_should_not_reload_settings_again_while_they_are_being_loaded() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blogid = self::factory()->blog->create();

		$settings = $this->settings;

		$times_filtered = 0;
		$re_entered     = false;

		$filter = function ( $value ) use ( &$times_filtered, &$re_entered, $settings ) {
			++$times_filtered;

			if ( ! $re_entered ) {
				// self guarded so a regression cannot blow the stack and take out the whole run
				$re_entered = true;

				// recurse
				$settings->get_option( 'tracking_code' );
			}

			return $value;
		};

		switch_to_blog( $blogid );

		try {
			// the option filter only runs for an option that exists
			update_option( Settings::OPTION, [ 'tracking_code' => 'some code' ] );

			add_filter( 'option_' . Settings::OPTION, $filter );

			$this->assertSame( 'some code', $settings->get_option( 'tracking_code' ) );

			$this->assertTrue( $re_entered, 'the filter did not re-enter, so nothing was tested' );
			$this->assertSame( 1, $times_filtered );
		} finally {
			remove_filter( 'option_' . Settings::OPTION, $filter );
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_get_option_should_not_reload_settings_again_while_checking_whether_the_plugin_is_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$settings = $this->settings;

		$settings->set_option( 'tracking_code', 'code of the first blog' );
		$settings->save();

		$blogid = self::factory()->blog->create();

		$blog_option_reads = 0;
		$re_entered        = false;
		$re_entered_value  = null;

		$count_blog_option_reads = function ( $value ) use ( &$blog_option_reads ) {
			++$blog_option_reads;

			return $value;
		};

		$re_enter = function ( $value ) use ( &$re_entered, &$re_entered_value, $settings ) {
			if ( ! $re_entered ) {
				// guard against infinite recursion in this test
				$re_entered       = true;
				$re_entered_value = $settings->get_option( 'tracking_code' );
			}

			return $value;
		};

		switch_to_blog( $blogid );

		try {
			update_option( Settings::OPTION, [ 'tracking_code' => 'code of the second blog' ] );

			add_filter( 'option_' . Settings::OPTION, $count_blog_option_reads );
			add_filter( 'site_option_active_sitewide_plugins', $re_enter );

			$this->assertSame( 'code of the second blog', $settings->get_option( 'tracking_code' ) );

			$this->assertTrue( $re_entered, 'the filter did not re-enter, so nothing was tested' );
			$this->assertSame( 1, $blog_option_reads );

			// whatever the re-entrant read got, it must not be the blog that was left behind
			$this->assertNotSame( 'code of the first blog', $re_entered_value );
		} finally {
			remove_filter( 'site_option_active_sitewide_plugins', $re_enter );
			remove_filter( 'option_' . Settings::OPTION, $count_blog_option_reads );
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_save_should_not_persist_the_emptied_settings_when_it_is_called_while_they_are_being_loaded() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$settings = $this->settings;

		$blogid = self::factory()->blog->create();

		$saved_during_load = false;

		$save_from_filter = function ( $value ) use ( &$saved_during_load, $settings ) {
			if ( ! $saved_during_load ) {
				// guard against infinite recursion in this test
				$saved_during_load = true;
				$settings->save();
			}

			return $value;
		};

		$settings->set_option( 'noscript_code', 'pending change of the first blog' );

		$blog_settings = [ 'tracking_code' => 'code of the second blog' ];

		switch_to_blog( $blogid );

		try {
			update_option( Settings::OPTION, $blog_settings );

			// whichever of the two the load reaches first, depending on whether the plugin is
			// network activated
			add_filter( 'site_option_active_sitewide_plugins', $save_from_filter );
			add_filter( 'option_' . Settings::OPTION, $save_from_filter );

			$actual_value = $settings->get_option( 'tracking_code' );
			$this->assertSame( 'code of the second blog', $actual_value );

			$this->assertTrue( $saved_during_load, 'the filter did not run, so nothing was tested' );
			$this->assertSame( $blog_settings, get_option( Settings::OPTION, [] ) );
		} finally {
			remove_filter( 'option_' . Settings::OPTION, $save_from_filter );
			remove_filter( 'site_option_active_sitewide_plugins', $save_from_filter );
			restore_current_blog();
		}
	}

	/**
	 * @param Settings $settings
	 * @return Settings
	 */
	private function assume_network_enabled( Settings $settings ) {
		$settings->set_assume_is_network_enabled_in_tests();
		$settings->init_settings();
		return $settings;
	}
}
