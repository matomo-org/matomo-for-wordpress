<?php
/**
 * @package matomo
 */

use WpMatomo\Settings;
use WpMatomo\NetworkActivationMigration;

class TrackingFilterMigrationTest extends MatomoUnit_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var NetworkActivationMigration
	 */
	private $migration;

	public function setUp(): void {
		parent::setUp();

		$this->settings  = new Settings();
		$this->migration = new NetworkActivationMigration( $this->settings );
	}

	public function test_is_active_should_be_true_outside_the_admin_for_wp_cli() {
		$this->assertFalse( is_admin() );

		$this->assertTrue( $this->migration->is_active() );
	}

	public function test_migrate_current_blog_should_leave_the_settings_it_was_given_and_reread_the_new_value() {
		// loaded before the migration writes the option, the way the plugin's shared instance is
		$this->assertSame( [], $this->settings->get_option( Settings::OPTION_KEY_STEALTH_BLOG ) );

		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->assertTrue( $this->migration->migrate_current_blog() );

		$this->assertSame( [ 'editor' => true ], $this->settings->get_option( Settings::OPTION_KEY_STEALTH_BLOG ) );

		// left stale, a save would write the copy taken before the migration
		$this->settings->set_option( 'tracking_code', '<!-- tracking code -->' );
		$this->settings->save();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );
	}

	public function test_migrate_current_blog_should_copy_the_tracking_filter_into_the_blogs_own_setting() {
		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->assertTrue( $this->migration->migrate_current_blog() );

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );

		$this->assertSame( [ 'editor' => '1' ], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_should_do_nothing_when_the_blog_excluded_no_role() {
		$this->assertFalse( $this->migration->migrate_current_blog() );

		$this->assertSame( [], $this->get_stored_blog_tracking_filter() );
	}

	public function test_migrate_current_blog_should_ignore_a_role_that_is_stored_but_not_excluded() {
		$this->store_tracking_filter(
			[
				'editor' => '1',
				'author' => '',
			]
		);

		$this->assertTrue( $this->migration->migrate_current_blog() );

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );
	}

	public function test_migrate_current_blog_should_keep_a_role_the_blog_had_already_excluded_of_its_own() {
		$this->store_tracking_filter( [ 'editor' => '1' ] );
		$this->store_blog_tracking_filter( [ 'author' => true ] );

		$this->assertTrue( $this->migration->migrate_current_blog() );

		$this->assertSame(
			[
				'author' => true,
				'editor' => true,
			],
			$this->get_stored_blog_tracking_filter()
		);
	}

	public function test_migrate_current_blog_should_carry_nothing_over_the_second_time_it_runs() {
		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->assertTrue( $this->migration->migrate_current_blog() );
		$this->assertFalse( $this->migration->migrate_current_blog() );

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );
	}

	public function test_migrate_current_blog_should_leave_the_other_settings_of_the_blog_alone() {
		( new Settings() )->apply_changes( [ 'tracking_code' => '<!-- tracking code -->' ] );
		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->assertTrue( $this->migration->migrate_current_blog() );

		$this->assertSame( '<!-- tracking code -->', ( new Settings() )->get_option( 'tracking_code' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_migrate_all_blogs_should_carry_the_filter_of_every_blog_over() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$blog_id = self::factory()->blog->create();

		switch_to_blog( $blog_id );
		try {
			$this->store_tracking_filter( [ 'author' => '1' ] );
		} finally {
			restore_current_blog();
		}

		$this->migration->migrate_all_blogs();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );

		switch_to_blog( $blog_id );
		try {
			$this->assertSame( [ 'author' => true ], $this->get_stored_blog_tracking_filter() );
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_migrate_all_blogs_should_leave_a_blog_of_another_network_alone() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$other_blog_id = $this->create_blog_on_another_network();

		switch_to_blog( $other_blog_id );
		try {
			$this->store_tracking_filter( [ 'author' => '1' ] );
		} finally {
			restore_current_blog();
		}

		$this->migration->migrate_all_blogs();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_blog_tracking_filter() );

		switch_to_blog( $other_blog_id );
		try {
			// the other blog did not activate the plugin, so the setting should not have changed
			$this->assertSame( [], $this->get_stored_blog_tracking_filter() );
		} finally {
			restore_current_blog();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_migrate_all_blogs_back_should_leave_a_blog_of_another_network_alone() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$other_blog_id = $this->create_blog_on_another_network();

		switch_to_blog( $other_blog_id );
		try {
			$this->store_blog_tracking_filter( [ 'author' => true ] );
		} finally {
			restore_current_blog();
		}

		$this->store_network_wide_tracking_filter( [ 'editor' => '1' ] );

		$this->migration->migrate_all_blogs_back();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );

		switch_to_blog( $other_blog_id );
		try {
			// the other blog did not deactivate the plugin, so the setting should not have changed
			$this->assertSame( [], $this->get_stored_tracking_filter() );
		} finally {
			restore_current_blog();
		}
	}

	public function test_migrate_all_blogs_should_do_nothing_outside_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}

		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->migration->migrate_all_blogs();

		$this->assertSame( [], $this->get_stored_blog_tracking_filter() );
	}

	/**
	 * @group ms-required
	 */
	public function test_on_plugin_activated_should_keep_a_blogs_filter_applying_once_the_network_is_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->migration->on_plugin_activated( true );
		$this->activate_matomo_plugin();

		$this->assertSame( [ 'editor' => true ], ( new Settings() )->get_stealth_roles() );
	}

	public function test_on_plugin_activated_should_do_nothing_when_a_single_blog_activates_the_plugin() {
		$this->store_tracking_filter( [ 'editor' => '1' ] );

		$this->migration->on_plugin_activated( false );

		$this->assertSame( [], $this->get_stored_blog_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_copy_the_network_wide_filter_into_the_blogs_own_setting() {
		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_copy_the_blogs_own_network_mode_filter_over_too() {
		$this->store_blog_tracking_filter( [ 'author' => true ] );

		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame(
			[
				'editor' => true,
				'author' => true,
			],
			$this->get_stored_tracking_filter()
		);
	}

	public function test_migrate_current_blog_back_should_keep_a_role_the_blog_excluded_before_the_network_took_over() {
		$this->store_tracking_filter( [ 'contributor' => '1' ] );

		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame(
			[
				'contributor' => '1',
				'editor'      => true,
			],
			$this->get_stored_tracking_filter()
		);
	}

	public function test_migrate_current_blog_back_should_ignore_a_role_that_is_stored_but_not_excluded() {
		$this->assertTrue(
			$this->migration->migrate_current_blog_back(
				[
					'editor' => '1',
					'author' => '',
				]
			)
		);

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_do_nothing_when_no_role_is_excluded_anywhere() {
		$this->assertFalse( $this->migration->migrate_current_blog_back( [] ) );

		$this->assertSame( [], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_carry_nothing_over_the_second_time_it_runs() {
		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );
		$this->assertFalse( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_leave_the_settings_it_was_given_and_reread_the_new_value() {
		// loaded before the migration writes the option, the way the plugin's shared instance is
		$this->assertSame( [], $this->settings->get_global_option( Settings::OPTION_KEY_STEALTH ) );

		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame( [ 'editor' => true ], $this->settings->get_global_option( Settings::OPTION_KEY_STEALTH ) );

		// left stale, a save would write the copy taken before the migration
		$this->settings->set_global_option( 'track_mode', 'disabled' );
		$this->settings->save();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );
	}

	public function test_migrate_current_blog_back_should_leave_the_other_settings_of_the_blog_alone() {
		( new Settings() )->apply_changes( [ 'tracking_code' => '<!-- tracking code -->' ] );

		$this->assertTrue( $this->migration->migrate_current_blog_back( [ 'editor' => '1' ] ) );

		$this->assertSame( '<!-- tracking code -->', ( new Settings() )->get_option( 'tracking_code' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_migrate_all_blogs_back_should_carry_the_filter_back_into_every_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = self::factory()->blog->create();

		switch_to_blog( $blog_id );
		try {
			$this->store_blog_tracking_filter( [ 'author' => true ] );
		} finally {
			restore_current_blog();
		}

		$this->store_network_wide_tracking_filter( [ 'editor' => '1' ] );

		$this->migration->migrate_all_blogs_back();

		$this->assertSame( [ 'editor' => true ], $this->get_stored_tracking_filter() );

		switch_to_blog( $blog_id );
		try {
			$this->assertSame(
				[
					'editor' => true,
					'author' => true,
				],
				$this->get_stored_tracking_filter()
			);
		} finally {
			restore_current_blog();
		}
	}

	public function test_migrate_all_blogs_back_should_do_nothing_outside_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}

		$this->store_blog_tracking_filter( [ 'editor' => true ] );

		$this->migration->migrate_all_blogs_back();

		$this->assertSame( [], $this->get_stored_tracking_filter() );
	}

	/**
	 * @group ms-required
	 */
	public function test_on_plugin_deactivated_should_keep_the_filter_applying_once_the_network_is_deactivated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->store_network_wide_tracking_filter( [ 'editor' => '1' ] );
		$this->activate_matomo_plugin();
		$this->store_blog_tracking_filter( [ 'author' => true ] );

		$this->assertSame(
			[
				'editor' => true,
				'author' => true,
			],
			( new Settings() )->get_stealth_roles()
		);

		// WordPress fires the deactivation hook before it records the deactivation
		$this->migration->on_plugin_deactivated( true );
		$this->deactivate_matomo_plugin();

		$this->assertSame(
			[
				'editor' => true,
				'author' => true,
			],
			( new Settings() )->get_stealth_roles()
		);
	}

	public function test_on_plugin_deactivated_should_do_nothing_when_a_single_blog_deactivates_the_plugin() {
		$this->store_blog_tracking_filter( [ 'editor' => true ] );

		$this->migration->on_plugin_deactivated( false );

		$this->assertSame( [], $this->get_stored_tracking_filter() );
	}

	private function create_blog_on_another_network() {
		// cast because the network factory returns the id as a string before WordPress 5.5
		$network_id = (int) self::factory()->network->create();

		$this->assertNotSame( get_current_network_id(), $network_id );

		// site_id rather than network_id: the blog factory only learned network_id in WordPress 5.5
		// and silently falls back to the current network without it, while every version since maps
		// site_id onto it. asserted below, to ensure the test is correct.
		$blog_id = self::factory()->blog->create( [ 'site_id' => $network_id ] );
		$this->assertSame( $network_id, get_site( $blog_id )->network_id );

		return $blog_id;
	}

	private function store_tracking_filter( $roles ) {
		$settings = new Settings();

		$this->assertFalse( $settings->is_network_enabled() );

		$settings->apply_changes( [ Settings::OPTION_KEY_STEALTH => $roles ] );
	}

	private function store_blog_tracking_filter( $roles ) {
		( new Settings() )->apply_changes( [ Settings::OPTION_KEY_STEALTH_BLOG => $roles ] );
	}

	private function store_network_wide_tracking_filter( $roles ) {
		$network_settings = get_site_option( Settings::OPTION_GLOBAL, [] );
		$network_settings = is_array( $network_settings ) ? $network_settings : [];

		$network_settings[ Settings::OPTION_KEY_STEALTH ] = $roles;

		update_site_option( Settings::OPTION_GLOBAL, $network_settings );
	}

	private function deactivate_matomo_plugin() {
		update_site_option( 'active_sitewide_plugins', [] );

		$this->assertFalse( ( new Settings() )->is_network_enabled() );
	}

	private function get_stored_tracking_filter() {
		$blog_global_settings = get_option( Settings::OPTION_GLOBAL, [] );

		return isset( $blog_global_settings[ Settings::OPTION_KEY_STEALTH ] )
			? $blog_global_settings[ Settings::OPTION_KEY_STEALTH ]
			: [];
	}

	private function get_stored_blog_tracking_filter() {
		return ( new Settings() )->get_option( Settings::OPTION_KEY_STEALTH_BLOG );
	}
}
