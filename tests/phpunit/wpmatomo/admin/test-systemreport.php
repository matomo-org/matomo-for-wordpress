<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\SystemReport;
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
class AdminSystemReportTest extends MatomoAnalytics_TestCase {

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

		// the built report tables are cached in a static property; reset it so every test
		// renders the report from the current state
		$matomo_tables_property = new ReflectionProperty( SystemReport::class, 'matomo_tables' );
		$matomo_tables_property->setAccessible( true );
		$matomo_tables_property->setValue( null, null );

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
		ob_start();
		$this->report->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'WordPress Plugins', $output );
	}

	public function test_show_does_not_mention_a_regenerated_salt_by_default() {
		ob_start();
		$this->report->show();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'salt was regenerated', $output );
	}

	public function test_show_warns_about_a_regenerated_salt_after_a_config_recovery() {
		$this->settings->set_time_salt_was_regenerated( time() );

		ob_start();
		$this->report->show();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Matomo salt was regenerated during a config file recovery', $output );
		$this->assertStringContainsString( 'opted out of tracking', $output );
	}

	public function test_show_dismisses_the_salt_regeneration_warning_on_request() {
		// the troubleshooting actions require super admin permissions
		$this->create_set_super_admin();

		$this->settings->set_time_salt_was_regenerated( time() );

		$this->fake_request( SystemReport::TROUBLESHOOT_DISMISS_SALT_REGENERATED );

		ob_start();
		$this->report->show();
		$output = ob_get_clean();

		$this->assertSame( 0, $this->settings->get_time_salt_was_regenerated() );
		$this->assertStringNotContainsString( 'salt was regenerated', $output );
	}

	/**
	 * @dataProvider get_trouble_shooting_data
	 */
	public function test_show_executes_troubleshooting_with_no_error( $method ) {
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
}
