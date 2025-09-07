<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model as SitesModel;
use Piwik\Plugins\UsersManager\Model as UsersModel;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Paths;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;

class InstallTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;
	/**
	 * @var Installer
	 */
	private $installer;
	/**
	 * @var Uninstaller
	 */
	private $uninstaller;

	/**
	 * @var array
	 */
	private $original_plugins;

	public function setUp(): void {
		parent::setUp();

		$this->settings    = new Settings();
		$this->installer   = $this->make_installer();
		$this->uninstaller = new Uninstaller();

		$this->original_plugins = isset( $GLOBALS['MATOMO_PLUGIN_FILES'] ) ? $GLOBALS['MATOMO_PLUGIN_FILES'] : [];
	}

	public function tearDown(): void {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = $this->original_plugins;

		parent::tearDown();
	}

	private function make_installer() {
		return new Installer( $this->settings );
	}

	public function test_looks_like_it_is_installed_returns_true_when_installed() {
		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, wp_json_encode( [ 'core' => 1 ] ) );
		$this->settings->save();

		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		$this->assertTrue( Installer::is_intalled() );
	}

	public function test_can_be_installed() {
		$this->assertTrue( $this->installer->can_be_installed() );
	}

	public function test_install_adds_sites_and_users() {
		Bootstrap::set_not_bootstrapped();

		$this->installer->install();

		$sites_model = new SitesModel();
		$all_sites   = $sites_model->getAllSites();

		$install_date = get_option( Installer::OPTION_NAME_INSTALL_DATE );

		// sets install date
		$this->assertTrue( time() - 600 < $install_date );
		$this->assertTrue( time() >= $install_date );

		unset( $all_sites[0]['ts_created'] );
		$this->assertEquals(
			array(
				array(
					'idsite'                         => 1,
					'name'                           => 'Test Blog',
					'main_url'                       => 'http://example.org',
					'ecommerce'                      => 1,
					'sitesearch'                     => 1,
					'sitesearch_keyword_parameters'  => '',
					'sitesearch_category_parameters' => '',
					'timezone'                       => 'UTC',
					'currency'                       => 'USD',
					'exclude_unknown_urls'           => 0,
					'excluded_ips'                   => '',
					'excluded_parameters'            => '',
					'excluded_referrers'             => '',
					'excluded_user_agents'           => '',
					'group'                          => '',
					'type'                           => 'website',
					'keep_url_fragment'              => 0,
					'creator_login'                  => 'super user was set',
				),
			),
			$all_sites
		);

		$users_model = new UsersModel();
		$all_users   = $users_model->getUsers( array() );

		foreach ( array( 'password', 'date_registered', 'ts_password_modified' ) as $field ) {
			$this->assertNotEmpty( $all_users[0][ $field ] );
			unset( $all_users[0][ $field ] );
		}
		$this->assertEquals(
			array(
				array(
					'login'                  => 'admin',
					'email'                  => 'admin@example.org',
					'twofactor_secret'       => '',
					'superuser_access'       => '1',
					'idchange_last_viewed'   => null,
					'invited_by'             => null,
					'invite_token'           => null,
					'invite_expired_at'      => null,
					'invite_accept_at'       => null,
					'invite_link_token'      => null,
					'ts_changes_shown'       => null,
					'ts_last_seen'           => null,
					'ts_inactivity_notified' => null,
				),
			),
			$all_users
		);
	}

	public function test_install_can_run_multiple_times() {
		$this->uninstaller->uninstall( true );
		$this->assertFalse( $this->installer->looks_like_it_is_installed() );
		$this->assertFalse( Installer::is_intalled() );

		$this->matomo_fixture->reset_config_for_install();

		Bootstrap::set_not_bootstrapped();
		$this->assertTrue( $this->installer->install() );
		$this->assertFalse( $this->installer->install() );

		Bootstrap::set_not_bootstrapped();
		$this->assertFalse( $this->installer->install() );
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		$this->assertTrue( Installer::is_intalled() );
	}

	/**
	 * @group ms-required
	 */
	public function test_install_also_installs_on_other_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blogid1 = self::factory()->blog->create();
		switch_to_blog( $blogid1 );

		// we trigger install manually... we could listen to an action like "wp_initialize_site" and then install
		// automatically... but bit scared of "fatal errors etc" and breaking anything in WordPress... instead
		// the site sync will install it and/or when someone visits that site
		Bootstrap::set_not_bootstrapped();

		$this->installer->install();

		$blogid = get_current_blog_id();

		$paths = new Paths();
		$this->assertStringContainsString( 'wp-content/uploads/sites/' . $blogid . '/matomo/config/config.ini.php', $paths->get_config_ini_path() );
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );

		$sites_model = new SitesModel();
		$all_sites   = $sites_model->getAllSites();

		wpmu_delete_blog( $blogid1 );

		$this->assertCount( 1, $all_sites );
	}

	/**
	 * @preserveGlobalState disabled
	 * @runInSeparateProcess
	 */
	public function test_get_db_infos_respects_charset_overrides() {
		global $wpdb;

		$db_config = Installer::get_db_infos();

		$this->assertEquals( $wpdb->charset ? $wpdb->charset : 'utf8', $db_config['charset'] );
		$this->assertEquals( $wpdb->collate ? $wpdb->collate : 'utf8mb4_general_ci', $db_config['collation'] );

		define( 'MATOMO_DB_CHARSET', 'dummycharset' );
		define( 'MATOMO_DB_COLLATE', 'dummycollate' );

		$db_config = Installer::get_db_infos();

		$this->assertEquals( 'dummycharset', $db_config['charset'] );
		$this->assertEquals( 'dummycollate', $db_config['collation'] );
	}

	public function test_is_current_instance_installed_returns_false_if_core_not_installed() {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = [
			ABSPATH . '/wp-content/plugins/matomo/matomo.php',
			ABSPATH . '/wp-content/plugins/SomePlugin/SomePlugin.php',
		];

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, wp_json_encode( [ 'SomePlugin' => 1 ] ) );
		$this->settings->save();

		$is_installed = $this->installer->is_current_instance_installed();
		$this->assertFalse( $is_installed );
	}

	public function test_is_current_instance_installed_returns_false_if_non_core_plugin_not_installed() {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = [
			ABSPATH . '/wp-content/plugins/matomo/matomo.php',
			ABSPATH . '/wp-content/plugins/SomePlugin/SomePlugin.php',
			ABSPATH . '/wp-content/plugins/AnotherPlugin/AnotherPlugin.php',
		];

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, wp_json_encode( [ 'core' => 1 ] ) );
		$this->settings->save();

		$is_installed = $this->installer->is_current_instance_installed();
		$this->assertFalse( $is_installed );
	}

	public function test_is_current_instance_installed_returns_false_if_some_non_core_plugin_not_installed() {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = [
			ABSPATH . '/wp-content/plugins/matomo/matomo.php',
			ABSPATH . '/wp-content/plugins/SomePlugin/SomePlugin.php',
			ABSPATH . '/wp-content/plugins/AnotherPlugin/AnotherPlugin.php',
		];

		$this->settings->set_option(
			Settings::INSTANCE_COMPONENTS_INSTALLED,
			wp_json_encode(
				[
					'core'       => 1,
					'SomePlugin' => 1,
				]
			)
		);
		$this->settings->save();

		$is_installed = $this->installer->is_current_instance_installed();
		$this->assertFalse( $is_installed );
	}

	public function test_is_current_instance_installed_defaults_installed_components_option_to_empty_array() {
		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		// sanity check
		$existing = $this->settings->get_option( Settings::INSTANCE_COMPONENTS_INSTALLED );
		$this->assertEmpty( $existing );

		$is_installed = $this->installer->is_current_instance_installed();
		$this->assertFalse( $is_installed );
	}

	public function test_is_current_instance_installed_returns_true_if_core_and_plugins_marked_installed() {
		$GLOBALS['MATOMO_PLUGIN_FILES'] = [
			ABSPATH . '/wp-content/plugins/matomo/matomo.php',
			ABSPATH . '/wp-content/plugins/SomePlugin/SomePlugin.php',
			ABSPATH . '/wp-content/plugins/AnotherPlugin/AnotherPlugin.php',
		];

		$this->settings->set_option(
			Settings::INSTANCE_COMPONENTS_INSTALLED,
			wp_json_encode(
				[
					'core'          => 1,
					'SomePlugin'    => 1,
					'AnotherPlugin' => 1,
				]
			)
		);
		$this->settings->save();

		$is_installed = $this->installer->is_current_instance_installed();
		$this->assertTrue( $is_installed );
	}

	public function test_mark_matomo_installed_adds_currently_installed_plugins_when_list_is_empty() {
		\Piwik\Config::getInstance()->PluginsInstalled['PluginsInstalled'] = [
			'SomePlugin',
			'SomeOtherPlugin',
		];

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		$this->installer->mark_matomo_installed();

		$existing = $this->settings->get_option( Settings::INSTANCE_COMPONENTS_INSTALLED );
		$existing = json_decode( $existing, true );

		$this->assertEquals(
			[
				'core'            => 1,
				'SomePlugin'      => 1,
				'SomeOtherPlugin' => 1,
			],
			$existing
		);
	}

	public function test_mark_matomo_installed_adds_currently_installed_plugins_when_list_is_not_empty() {
		\Piwik\Config::getInstance()->PluginsInstalled['PluginsInstalled'] = [
			'SomePlugin',
			'SomeOtherPlugin',
		];

		$this->settings->set_option(
			Settings::INSTANCE_COMPONENTS_INSTALLED,
			wp_json_encode(
				[
					'AnotherPlugin' => 1,
				]
			)
		);
		$this->settings->save();

		$this->installer->mark_matomo_installed();

		$existing = $this->settings->get_option( Settings::INSTANCE_COMPONENTS_INSTALLED );
		$existing = json_decode( $existing, true );

		$this->assertEquals(
			[
				'core'            => 1,
				'SomePlugin'      => 1,
				'SomeOtherPlugin' => 1,
				'AnotherPlugin'   => 1,
			],
			$existing
		);
	}

	public function test_install_schedules_geoip_if_not_already_ran_once() {
		// remove existing task if exists
		$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		if ( ! empty( $next ) ) {
			wp_unschedule_event( $next, \WpMatomo\ScheduledTasks::EVENT_GEOIP );
			$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		}

		$this->assertEmpty( $next );

		// mark components not installed
		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		// ensure last time before cron is empty
		$tasks  = new ScheduledTasks( $this->settings );
		$before = $tasks->get_last_time_before_cron( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		$this->assertEmpty( $before );

		$this->installer->install();

		$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		$this->assertNotEmpty( $next );
	}

	public function test_install_does_not_schedule_geoip_if_already_ran_once() {
		// remove existing task if exists
		$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		if ( ! empty( $next ) ) {
			wp_unschedule_event( $next, \WpMatomo\ScheduledTasks::EVENT_GEOIP );
			$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		}

		$this->assertEmpty( $next );

		// mark components not installed
		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		// mark geoip already run
		$tasks = new ScheduledTasks( $this->settings );
		$tasks->set_last_time_before_cron( \WpMatomo\ScheduledTasks::EVENT_GEOIP, 900 );

		$this->installer->install();

		$next = wp_next_scheduled( \WpMatomo\ScheduledTasks::EVENT_GEOIP );
		$this->assertEmpty( $next );
	}

	public function test_install_runs_if_not_started() {
		delete_option( Settings::OPTION_PREFIX . 'install-start-time' );

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		$result = $this->installer->install();

		$this->assertTrue( $result );
	}

	public function test_install_does_not_run_if_started_recently() {
		update_option( Settings::OPTION_PREFIX . 'install-start-time', time() - 10 );

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		$result = $this->installer->install();

		$this->assertFalse( $result );
	}

	public function test_install_runs_if_last_started_more_than_five_minutes_ago() {
		update_option( Settings::OPTION_PREFIX . 'install-start-time', time() - 310 );

		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '' );
		$this->settings->save();

		$result = $this->installer->install();

		$this->assertTrue( $result );
	}
}
