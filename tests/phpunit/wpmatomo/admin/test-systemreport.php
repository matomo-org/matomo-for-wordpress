<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\SystemReport;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

// phpcs:ignore WordPress.NamingConventions
$piwik_minimumPHPVersion = '7.2.5';

/**
 * We want a real data, not something coming from cache
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 *
 * We cannot use parameters of statements as this is the table names we build
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
class AdminSystemReportTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @see SystemReport::get_errors_present_cache_key(), which is private
	 */
	const ERRORS_PRESENT_CACHE_KEY = 'matomo_system_report_has_errors';

	/**
	 * @var SystemReport
	 */
	private $report;

	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * Required for test_get_missing_tables
	 *
	 * @see AdminSystemReportTest::test_get_missing_tables()
	 * @var bool
	 */
	protected $disable_temp_tables = true;

	public function setUp(): void {
		parent::setUp();
		$this->settings = new Settings();
		$this->report   = new SystemReport( $this->settings );
		if ( is_multisite() ) {
			// the main difference in behavior is more like whether it is network enabled or not ...
			// and not so much if it is multisite or not
			$this->settings->set_assume_is_network_enabled_in_tests( true );
		}

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_renders_ui() {
		$this->create_set_super_admin();

		ob_start();
		$this->report->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'WordPress Plugins', $output );
	}

	/**
	 * @dataProvider get_trouble_shooting_data
	 */
	public function test_show_executes_troubleshooting_with_no_error( $method ) {
		$this->create_set_super_admin();

		$this->fake_request( $method );

		ob_start();
		$show = $this->report->show();
		ob_end_clean();
		$this->assertNull( $show );
	}

	public function get_trouble_shooting_data() {
		if ( is_multisite() ) {
			return array(
				array( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES ),
				array( SystemReport::TROUBLESHOOT_SYNC_ALL_USERS ),
				array( SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ),
			);
		} else {
			return array(
				array( SystemReport::TROUBLESHOOT_SYNC_USERS ),
				array( SystemReport::TROUBLESHOOT_SYNC_SITE ),
				array( SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ),
			);
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_sync_all_blogs_should_return_false_for_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// they are the super user of the Matomo belonging to their own blog, which these two
		// actions reach far beyond
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin( get_current_user_id() ) );

		$this->assertFalse( $this->report->can_user_sync_all_blogs() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_sync_all_blogs_should_return_true_for_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->create_set_super_admin();

		$this->assertTrue( $this->report->can_user_sync_all_blogs() );
	}

	public function test_can_user_sync_all_blogs_should_return_false_when_the_network_is_not_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		$this->create_set_super_admin();

		// with a single blog these actions do no more than the per blog sync buttons beside them,
		// and where blogs activate Matomo individually syncing sites would install it onto blogs
		// that deliberately do not have it
		$this->assertFalse( $this->report->can_user_sync_all_blogs() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_refuse_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin( get_current_user_id() ) );

		$this->expectException( WPDieException::class );

		$this->render_troubleshooting();
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_not_run_a_troubleshooting_action_for_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->fake_request( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES );

		// refused before execute_troubleshoot_if_needed() gets to look at the request
		$this->expectException( WPDieException::class );

		$this->render_troubleshooting();
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_offer_the_sync_all_blogs_actions_to_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->create_set_super_admin();

		$output = $this->render_troubleshooting();

		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES, $output );
		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_SYNC_ALL_USERS, $output );
	}

	public function test_show_should_not_offer_the_sync_all_blogs_actions_when_the_network_is_not_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		$this->create_set_super_admin();

		$output = $this->render_troubleshooting();

		$this->assertStringNotContainsString( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES, $output );
		$this->assertStringNotContainsString( SystemReport::TROUBLESHOOT_SYNC_ALL_USERS, $output );

		// the actions that sync the blog the page is shown for stay
		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_SYNC_SITE, $output );
		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_SYNC_USERS, $output );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_update_geoip_db_should_return_false_for_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// the geolocation database is downloaded once for the whole install, not once per blog
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin( get_current_user_id() ) );

		$this->assertFalse( $this->report->can_user_update_geoip_db() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_update_geoip_db_should_return_true_for_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->create_set_super_admin();

		$this->assertTrue( $this->report->can_user_update_geoip_db() );
	}

	public function test_can_user_update_geoip_db_should_return_true_when_the_network_is_not_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// there is only one Matomo install to download it for, and it is theirs
		$this->assertTrue( $this->report->can_user_update_geoip_db() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_offer_the_geoip_db_action_to_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->create_set_super_admin();

		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_UPDATE_GEOIP_DB, $this->render_troubleshooting() );
	}

	public function test_show_should_offer_the_geoip_db_action_when_the_network_is_not_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertStringContainsString( SystemReport::TROUBLESHOOT_UPDATE_GEOIP_DB, $this->render_troubleshooting() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_refuse_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin( get_current_user_id() ) );

		$this->assertFalse( $this->report->can_user_manage() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_allow_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->create_set_super_admin();

		$this->assertTrue( $this->report->can_user_manage() );
	}

	public function test_can_user_manage_should_allow_a_matomo_super_user_when_the_network_is_not_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		$this->create_set_super_admin();

		$this->assertTrue( $this->report->can_user_manage() );
	}

	public function test_can_user_manage_should_refuse_a_user_without_matomo_super_user_access() {
		$this->settings->set_assume_is_network_enabled_in_tests( false );

		( new Roles( $this->settings ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( array( 'role' => Roles::ROLE_ADMIN ) ) );

		$this->assertTrue( current_user_can( Capabilities::KEY_ADMIN ) );
		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertFalse( $this->report->can_user_manage() );
	}

	public function test_errors_present_should_prefer_the_answer_recorded_for_this_blog_over_one_recorded_for_the_network() {
		set_site_transient( self::ERRORS_PRESENT_CACHE_KEY, 1, WEEK_IN_SECONDS );
		set_transient( self::ERRORS_PRESENT_CACHE_KEY, 0, WEEK_IN_SECONDS );

		$this->assertFalse( $this->report->errors_present() );

		set_site_transient( self::ERRORS_PRESENT_CACHE_KEY, 0, WEEK_IN_SECONDS );
		set_transient( self::ERRORS_PRESENT_CACHE_KEY, 1, WEEK_IN_SECONDS );

		$this->assertTrue( $this->report->errors_present() );
	}

	/**
	 * @group ms-required
	 */
	public function test_errors_present_should_not_answer_with_what_was_recorded_for_another_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$other_blog_id = self::factory()->blog->create();

		// what one site transient for the whole network would have left behind
		set_site_transient( self::ERRORS_PRESENT_CACHE_KEY, 0, WEEK_IN_SECONDS );

		set_transient( self::ERRORS_PRESENT_CACHE_KEY, 1, WEEK_IN_SECONDS );

		switch_to_blog( $other_blog_id );
		try {
			set_transient( self::ERRORS_PRESENT_CACHE_KEY, 0, WEEK_IN_SECONDS );

			$this->assertFalse( ( new SystemReport( new Settings() ) )->errors_present() );
		} finally {
			restore_current_blog();
		}

		// this blog has errors and the other one does not, and it stays that way
		$this->assertTrue( $this->report->errors_present() );
	}

	public function test_errors_present_should_answer_with_what_the_report_found_rather_than_with_the_cache_it_missed() {
		delete_transient( self::ERRORS_PRESENT_CACHE_KEY );

		add_filter(
			'matomo_systemreport_tables',
			function () {
				return [
					[
						'title' => 'Test',
						'rows'  => [
							[
								'name'     => 'Something broken',
								'value'    => '',
								'is_error' => true,
							],
						],
					],
				];
			}
		);

		$this->assertTrue( $this->report->errors_present() );
		$this->assertSame( 1, (int) get_transient( self::ERRORS_PRESENT_CACHE_KEY ) );
	}

	public function test_not_compatible_plugins_are_mentioned_in_faq() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$contents = file_get_contents( 'https://matomo.org/faq/wordpress/which-plugins-is-matomo-for-wordpress-known-to-be-not-compatible-with/' );

		foreach ( $this->report->get_not_compatible_plugins() as $not_compatible_plugin ) {
			$this->assertStringContainsString( $not_compatible_plugin, $contents );
		}
	}

	private function fake_request( $field ) {
		$_POST[ $field ]        = 1;
		$_REQUEST['_wpnonce']   = wp_create_nonce( SystemReport::NONCE_NAME );
		$_SERVER['REQUEST_URI'] = home_url();
	}

	public function test_get_missing_tables_should_return_empty_array_when_all_tables_exist() {
		$this->assertSame( array(), $this->report->get_missing_tables() );
	}

	public function test_get_missing_tables_should_return_the_missing_tables() {
		global $wpdb;
		$old_table_name = $this->report->db_settings->prefix_table_name( 'site' );
		$new_table_name = $old_table_name . '_bkp';
		$wpdb->query( "ALTER TABLE $old_table_name RENAME $new_table_name" );

		$missing_tables = $this->report->get_missing_tables();
		$this->assertCount( 1, $missing_tables );
		$this->assertSame( array( $old_table_name ), array_values( $missing_tables ) );

		$wpdb->query( "ALTER TABLE $new_table_name RENAME $old_table_name" );
	}

	private function render_troubleshooting() {
		$_GET['tab'] = 'troubleshooting';

		ob_start();

		try {
			$this->report->show();
		} finally {
			$output = ob_get_clean();

			unset( $_GET['tab'] );
		}

		return $output;
	}
}
