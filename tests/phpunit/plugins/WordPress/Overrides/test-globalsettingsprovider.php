<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
 */

use Piwik\Application\Kernel\GlobalSettingsProvider as DefaultGlobalSettingsProvider;
use Piwik\Plugins\WordPress\Overrides\GlobalSettingsProvider;

/**
 * @package matomo
 */
class GlobalSettingsProviderTest extends MatomoAnalytics_TestCase {

	/**
	 * @var \WpMatomo\Settings
	 */
	private $settings;

	/**
	 * @var string[]
	 */
	private $temp_files = [];

	public function setUp(): void {
		parent::setUp();

		delete_option( \WpMatomo\Settings::OPTION_GLOBAL );
		delete_option( \WpMatomo\Settings::OPTION_CONFIG_BACKUP );
		delete_site_option( \WpMatomo\Settings::OPTION_CONFIG_BACKUP );
		$this->settings = new \WpMatomo\Settings();
	}

	public function tearDown(): void {
		foreach ( $this->temp_files as $file ) {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
		$this->temp_files = [];

		parent::tearDown();
	}

	public function test_is_a_global_settings_provider() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );
		$this->assertInstanceOf( DefaultGlobalSettingsProvider::class, $provider );
	}

	public function test_construct_backs_up_config_to_option_when_option_has_no_data() {
		$this->assertEquals( [], $this->get_option_data() );

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertIsArray( $stored );
		$this->assertNotEmpty( $stored );

		// only the diff (values that differ from the INI defaults) is backed up, not the full merged
		// config. so the stored data must be a strict subset of the merged settings and must not
		// contain unchanged default-only sections.
		$merged = $provider->getIniFileChain()->getAll();
		$this->assertNotEquals( $merged, $stored );
		foreach ( $stored as $section_name => $section ) {
			$this->assertArrayHasKey( $section_name, $merged );
		}
	}

	public function test_construct_backs_up_config_to_option_when_option_holds_an_empty_array() {
		$this->update_option_data( [] );

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertNotEmpty( $stored );
		$this->assertNotEquals( $provider->getIniFileChain()->getAll(), $stored );
	}

	public function test_construct_refreshes_backup_option_from_file_when_file_exists() {
		// the config.ini.php file is the source of truth, so a stale backup option must be overwritten
		// with the data currently in the file.
		$this->update_option_data(
			array(
				'ThisSectionIsNotInTheFile' => array( 'foo' => 'bar' ),
			)
		);

		new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();
		$this->assertArrayNotHasKey( 'ThisSectionIsNotInTheFile', $stored );
	}

	public function test_construct_does_not_apply_backup_option_when_file_exists() {
		// the backup option is only a backup: while config.ini.php exists it must not be layered on top
		// of the file config, so a value present only in the option is ignored.
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$section = (array) $provider->getSection( 'TestSection' );
		$this->assertArrayNotHasKey( 'test_key', $section );
	}

	public function test_construct_restores_config_from_backup_when_file_is_missing() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		// the backup is applied on top of the INI config when the file is missing
		$this->assertSame( 'test_value', $provider->getSection( 'TestSection' )['test_key'] );
	}

	public function test_construct_recreates_config_file_from_backup_when_file_is_missing() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$path = $this->non_existent_config_path();
		$this->assertFalse( file_exists( $path ) );

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		// the missing config.ini.php is written back to disk from the backup
		$this->assertTrue( file_exists( $path ) );
		$contents = file_get_contents( $path );
		$this->assertStringContainsString( '[TestSection]', $contents );
		$this->assertStringContainsString( 'test_value', $contents );
	}

	public function test_construct_does_not_write_file_when_backup_is_empty_and_file_is_missing() {
		$path = $this->non_existent_config_path();

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		// nothing to restore (eg. fresh install), so no bogus config.ini.php is created
		$this->assertFalse( file_exists( $path ) );
	}

	public function test_restore_does_not_wipe_default_sections_when_backup_holds_partial_data() {
		// this guards against the regression where partial stored data (eg. data written by older
		// plugin versions) replaced the whole config and wiped the INI defaults.
		$default_general = ( new GlobalSettingsProvider( null, null, null, $this->settings ) )->getSection( 'General' );
		$this->assertNotEmpty( $default_general, 'precondition: the General section has default values' );

		// trusted_hosts and salt are set by the installer (and generated again on restore),
		// they're not part of the default section data being compared
		unset( $default_general['trusted_hosts'] );
		unset( $default_general['salt'] );

		delete_option( \WpMatomo\Settings::OPTION_GLOBAL );
		$this->settings = new \WpMatomo\Settings();
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		// the partial backup is applied...
		$this->assertSame( 'test_value', $provider->getSection( 'TestSection' )['test_key'] );
		// ...but the default sections that are not part of the backup are preserved.
		$restored_general = $provider->getSection( 'General' );
		unset( $restored_general['trusted_hosts'] );
		unset( $restored_general['salt'] );
		$this->assertEquals( $default_general, $restored_general );
	}

	public function test_activated_plugins_are_filtered_for_wordpress_when_file_exists() {
		// the WordPress plugin filtering runs regardless of whether the config comes from the file or
		// the backup, so with the real config.ini.php in place the activated list is still filtered.
		$activated = $this->get_activated_plugins();

		$this->assertNotContains( 'Marketplace', $activated );
		$this->assertNotContains( 'MultiSites', $activated );
		$this->assertContains( 'BulkTracking', $activated );
		$this->assertContains( 'CustomJsTracker', $activated );
	}

	public function test_keeps_plugins_section_structure_so_it_can_be_read_by_pluginlist() {
		// regression test: the [Plugins] section must stay a nested array (['Plugins' => [...]]) so
		// PluginList::getActivatedPlugins() (and therefore the DI container) can read it. storing a
		// flat list of plugin names would make the activated plugin list resolve to empty.
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$section = $provider->getSection( 'Plugins' );

		$this->assertArrayHasKey( 'Plugins', $section );
		$this->assertIsArray( $section['Plugins'] );
		$this->assertNotEmpty( $section['Plugins'] );
		// the section must not be a flat list of plugin names
		$this->assertArrayNotHasKey( 0, $section );
	}

	public function test_restored_plugin_list_is_filtered_for_wordpress() {
		// a [Plugins] section stored in a (legacy) backup is discarded on restore: the plugin
		// list is runtime-computed, so the activated list must come from the defaults plus the
		// WordPress filtering, never from the stored value
		$activated = $this->get_activated_plugins_for_missing_file(
			array(
				'Plugins' => array( 'Plugins' => array( 'CoreHome', 'Marketplace', 'MultiSites' ) ),
			)
		);

		// activated plugins are read back through the real consumer of the section
		$this->assertContains( 'CoreHome', $activated );
		// plugins that do not make sense in WordPress are removed
		$this->assertNotContains( 'Marketplace', $activated );
		$this->assertNotContains( 'MultiSites', $activated );
		// plugins required for WordPress are force enabled
		$this->assertContains( 'BulkTracking', $activated );
		$this->assertContains( 'CustomJsTracker', $activated );
	}

	public function test_restored_plugin_list_adds_globally_enabled_plugins() {
		$original                          = isset( $GLOBALS['MATOMO_PLUGINS_ENABLED'] ) ? $GLOBALS['MATOMO_PLUGINS_ENABLED'] : null;
		$GLOBALS['MATOMO_PLUGINS_ENABLED'] = array( 'MyExtraPlugin' );

		try {
			$activated = $this->get_activated_plugins_for_missing_file(
				array(
					'Plugins' => array( 'Plugins' => array( 'CoreHome' ) ),
				)
			);
		} finally {
			if ( null === $original ) {
				unset( $GLOBALS['MATOMO_PLUGINS_ENABLED'] );
			} else {
				$GLOBALS['MATOMO_PLUGINS_ENABLED'] = $original;
			}
		}

		$this->assertContains( 'MyExtraPlugin', $activated );
	}

	public function test_restored_plugin_list_is_not_frozen_to_stored_value() {
		// a plugin list restored from the backup must not prevent the WordPress filtering from running
		// again (eg. BulkTracking must always end up enabled even if it is not stored).
		$activated = $this->get_activated_plugins_for_missing_file(
			array(
				'Plugins' => array( 'Plugins' => array( 'CoreHome' ) ),
			)
		);

		$this->assertContains( 'CoreHome', $activated );
		$this->assertContains( 'BulkTracking', $activated );
	}

	public function test_persistConfigOption_reflects_config_changes_in_the_option() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$chain   = $provider->getIniFileChain();
		$section = $chain->get( 'TestSection' );
		$this->assertArrayNotHasKey( 'changed_key', (array) $section );

		// change the config data the same way Piwik\Config does (through IniFileChain)
		$section                = (array) $section;
		$section['changed_key'] = 'changed_value';
		$chain->set( 'TestSection', $section );

		$provider->persistConfigOption();

		$stored = $this->get_option_data();

		$this->assertSame( 'changed_value', $stored['TestSection']['changed_key'] );
		// only the override is stored, not the full merged config
		$this->assertNotEquals( $chain->getAll(), $stored );
	}

	public function test_backup_does_not_contain_database_credentials_salt_or_trusted_hosts() {
		// the real config.ini.php contains the [database] credentials and the [General] salt
		// (secrets that must never be copied into the more exposed WordPress options table) as
		// well as trusted_hosts (blog-specific, must not enter a potentially network-shared backup)
		new GlobalSettingsProvider( null, null, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertNotEmpty( $stored );
		if ( isset( $stored['database'] ) ) {
			// only portable connection settings may be backed up, never credentials or identity
			$unexpected_keys = array_diff_key(
				$stored['database'],
				array_flip( GlobalSettingsProvider::DATABASE_KEYS_TO_BACKUP )
			);
			$this->assertSame( array(), $unexpected_keys );
		}
		if ( isset( $stored['General'] ) ) {
			$this->assertArrayNotHasKey( 'salt', $stored['General'] );
			$this->assertArrayNotHasKey( 'trusted_hosts', $stored['General'] );
		}
	}

	public function test_backup_redacts_secret_like_values() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$chain = $provider->getIniFileChain();
		$chain->set(
			'TestSection',
			array(
				'some_password' => 'secret1',
				'api_key'       => 'secret2',
				'smtpPassword'  => 'secret3',
				'auth_token'    => 'secret4',
				'accessToken'   => 'secret5',
				'passphrase'    => 'secret6',
				'bearer'        => 'secret7',
				'credentials'   => 'secret8',
				'safe_value'    => 'kept',
			)
		);

		$provider->persistConfigOption();

		$stored = $this->get_option_data();

		$this->assertSame( array( 'safe_value' => 'kept' ), $stored['TestSection'] );
	}

	public function test_backup_keeps_only_portable_database_values() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$chain    = $provider->getIniFileChain();
		$database = (array) $chain->get( 'database' );
		$chain->set(
			'database',
			array_merge(
				$database,
				array(
					'enable_ssl' => 1,
					'ssl_ca'     => '/etc/ssl/db-ca.pem',
				)
			)
		);

		$provider->persistConfigOption();

		$stored = $this->get_option_data();

		// hand-added portable connection settings survive into the backup...
		$this->assertSame( 1, $stored['database']['enable_ssl'] );
		$this->assertSame( '/etc/ssl/db-ca.pem', $stored['database']['ssl_ca'] );
		// ...but credentials and identity values never do
		$this->assertArrayNotHasKey( 'username', $stored['database'] );
		$this->assertArrayNotHasKey( 'password', $stored['database'] );
		$this->assertArrayNotHasKey( 'host', $stored['database'] );
		$this->assertArrayNotHasKey( 'dbname', $stored['database'] );
		$this->assertArrayNotHasKey( 'tables_prefix', $stored['database'] );
	}

	public function test_restore_applies_portable_database_values_over_rebuilt_credentials() {
		$this->update_option_data(
			array(
				'database'    => array(
					'ssl_ca'   => '/etc/ssl/db-ca.pem',
					'charset'  => 'custom_charset',
					'username' => 'stale_user',
					'host'     => 'stale-host.example.com',
				),
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$database = $provider->getSection( 'database' );
		$this->assertSame( '/etc/ssl/db-ca.pem', $database['ssl_ca'] );
		$this->assertSame( 'custom_charset', $database['charset'] );
		$this->assertSame( DB_USER, $database['username'] );
		$this->assertNotEquals( 'stale-host.example.com', $database['host'] );
	}

	public function test_backup_does_not_contain_reader_or_tests_database_sections() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$chain = $provider->getIniFileChain();
		$chain->set(
			'database_reader',
			array(
				'host'     => 'reader.example.com',
				'username' => 'reader_user',
				'password' => 'reader_pass',
			)
		);
		$chain->set( 'database_tests', array( 'dbname' => 'tests_db' ) );

		$provider->persistConfigOption();

		$stored = $this->get_option_data();

		$this->assertArrayNotHasKey( 'database_reader', $stored );
		$this->assertArrayNotHasKey( 'database_tests', $stored );
	}

	public function test_restore_does_not_write_a_database_reader_section_from_the_backup() {
		$this->update_option_data(
			array(
				'database_reader' => array(
					'host'     => 'reader.example.com',
					'username' => 'reader_user',
				),
				'TestSection'     => array( 'test_key' => 'test_value' ),
			)
		);

		$path = $this->non_existent_config_path();
		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$contents = file_get_contents( $path );
		$this->assertStringNotContainsString( '[database_reader]', $contents );
		$this->assertStringContainsString( "[TestSection]\n", $contents );
	}

	public function test_restore_rebuilds_database_settings_from_wordpress() {
		global $wpdb;

		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$path     = $this->non_existent_config_path();
		$provider = new GlobalSettingsProvider( null, $path, null, $this->settings );

		// the [database] section is not part of the backup, it is rebuilt from the current
		// WordPress DB credentials (so restores keep working when the credentials change)
		$database = $provider->getSection( 'database' );
		$this->assertSame( DB_USER, $database['username'] );
		$this->assertSame( $wpdb->prefix . MATOMO_DATABASE_PREFIX, $database['tables_prefix'] );

		$contents = file_get_contents( $path );
		$this->assertStringContainsString( '[database]', $contents );
	}

	public function test_restore_generates_missing_salt_and_trusted_hosts() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$general = $provider->getSection( 'General' );
		$this->assertNotEmpty( $general['salt'] );
		$this->assertNotEmpty( $general['trusted_hosts'] );
	}

	public function test_restore_does_not_reuse_a_salt_stored_in_a_backup() {
		$this->update_option_data(
			array(
				'General'     => array(
					'salt'         => 'stored-salt',
					'kept_setting' => 'kept-value',
				),
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$general = $provider->getSection( 'General' );
		$this->assertNotEmpty( $general['salt'] );
		$this->assertNotSame( 'stored-salt', $general['salt'] );
		$this->assertSame( 'kept-value', $general['kept_setting'] );
	}

	public function test_restore_does_not_reuse_trusted_hosts_stored_in_a_backup() {
		$this->update_option_data(
			array(
				'General'     => array( 'trusted_hosts' => array( 'other-blog.example.com' ) ),
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$expected_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$expected_port = wp_parse_url( home_url(), PHP_URL_PORT );
		if ( $expected_port ) {
			$expected_host .= ':' . $expected_port;
		}

		$trusted_hosts = $provider->getSection( 'General' )['trusted_hosts'];
		$this->assertNotContains( 'other-blog.example.com', $trusted_hosts );
		$this->assertSame( array( $expected_host ), $trusted_hosts );
	}

	public function test_restore_falls_back_to_legacy_config_options_backup() {
		$this->update_legacy_option_data(
			array(
				'TestSection' => array( 'test_key' => 'legacy_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$this->assertSame( 'legacy_value', $provider->getSection( 'TestSection' )['test_key'] );
	}

	public function test_restore_prefers_the_dedicated_backup_over_the_legacy_option() {
		$this->update_legacy_option_data(
			array(
				'TestSection' => array( 'test_key' => 'legacy_value' ),
			)
		);
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'backup_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$this->assertSame( 'backup_value', $provider->getSection( 'TestSection' )['test_key'] );
	}

	public function test_backup_and_restore_work_when_network_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests();
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'network_value' ),
			)
		);

		$path     = $this->non_existent_config_path();
		$provider = new GlobalSettingsProvider( null, $path, null, $this->settings );

		$this->assertSame( 'network_value', $provider->getSection( 'TestSection' )['test_key'] );
		$this->assertTrue( file_exists( $path ) );
	}

	public function test_restore_uses_legacy_backup_when_network_enabled() {
		$this->settings->set_assume_is_network_enabled_in_tests();
		$this->update_legacy_option_data(
			array(
				'TestSection' => array( 'test_key' => 'legacy_value' ),
			)
		);

		$provider = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );

		$this->assertSame( 'legacy_value', $provider->getSection( 'TestSection' )['test_key'] );
	}

	public function test_restore_does_nothing_when_backup_holds_only_excluded_values() {
		$this->update_option_data(
			array(
				'database' => array( 'password' => 'stored-password' ),
				'General'  => array(
					'salt'          => 'stored-salt',
					'trusted_hosts' => array( 'example.com' ),
				),
			)
		);

		$path = $this->non_existent_config_path();
		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$this->assertFalse( file_exists( $path ) );
	}

	public function test_backup_does_not_contain_the_plugins_section() {
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		// the [Plugins] section holds the runtime-computed plugin list (set on every reload by
		// the WordPress plugin filtering), it must not be persisted as if it were user config
		$chain = $provider->getIniFileChain();
		$chain->set( 'Plugins', array( 'Plugins' => array( 'CoreHome', 'TagManager' ) ) );

		$provider->persistConfigOption();

		$this->assertArrayNotHasKey( 'Plugins', $this->get_option_data() );
	}

	public function test_restore_does_not_write_a_plugins_section_from_the_backup() {
		$this->update_option_data(
			array(
				'Plugins'     => array( 'Plugins' => array( 'CoreHome', 'Marketplace' ) ),
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$path = $this->non_existent_config_path();
		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$contents = file_get_contents( $path );
		$this->assertStringNotContainsString( "[Plugins]\n", $contents );
		$this->assertStringContainsString( "[TestSection]\n", $contents );
	}

	public function test_installed_config_file_ends_with_end_of_file_marker() {
		// the config.ini.php created by the installer must end with the marker, otherwise it
		// would never be backed up
		$provider = new GlobalSettingsProvider( null, null, null, $this->settings );

		$this->assert_config_file_ends_with_marker( $provider->getPathLocal() );
	}

	public function test_backup_is_not_updated_when_config_file_is_missing_the_end_of_file_marker() {
		$this->update_option_data(
			array(
				'Preexisting' => array( 'key' => 'value' ),
			)
		);

		// a config file whose write did not finish (or that was truncated): no marker at the end
		$path = $this->write_config_file( "[TestSection]\ntest_key = \"test_value\"\n" );

		$original_contents = file_get_contents( $path );

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		// the incomplete file was not backed up (that would overwrite the good backup)...
		$this->assertSame( array( 'Preexisting' => array( 'key' => 'value' ) ), $this->get_option_data() );
		// ...and it was not overwritten with the backup either (a write may be in progress)
		$this->assertSame( $original_contents, file_get_contents( $path ) );
	}

	public function test_backup_is_updated_when_config_file_ends_with_the_end_of_file_marker() {
		$marker_section = GlobalSettingsProvider::END_OF_FILE_MARKER_SECTION;

		$path = $this->write_config_file(
			"[TestSection]\ntest_key = \"test_value\"\n\n"
			. '[' . $marker_section . "]\n" . GlobalSettingsProvider::END_OF_FILE_MARKER_KEY . " = 1\n"
		);

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$stored = $this->get_option_data();

		$this->assertSame( 'test_value', $stored['TestSection']['test_key'] );
		// the marker is file bookkeeping, it does not belong in the backup
		$this->assertArrayNotHasKey( $marker_section, $stored );
	}

	public function test_stale_incomplete_config_file_is_restored_from_backup() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		// a marker-less file that has not been modified for longer than the grace period: the
		// write that produced it was interrupted for good, so it is replaced with the backup
		$path = $this->write_config_file( "[OldSection]\nold_key = \"old_value\"\n" );
		touch( $path, time() - GlobalSettingsProvider::INCOMPLETE_FILE_GRACE_PERIOD_SECONDS - 60 );

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$contents = file_get_contents( $path );
		$this->assertStringContainsString( "[TestSection]\n", $contents );
		$this->assertStringNotContainsString( '[OldSection]', $contents );
		$this->assert_config_file_ends_with_marker( $path );
	}

	public function test_stale_incomplete_config_file_is_not_restored_from_the_legacy_backup() {
		$this->update_legacy_option_data(
			array(
				'TestSection' => array( 'test_key' => 'legacy_value' ),
			)
		);

		$path = $this->write_config_file( "[OldSection]\nold_key = \"old_value\"\n" );
		touch( $path, time() - GlobalSettingsProvider::INCOMPLETE_FILE_GRACE_PERIOD_SECONDS - 60 );

		$original_contents = file_get_contents( $path );

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$this->assertSame( $original_contents, file_get_contents( $path ) );
	}

	public function test_empty_config_file_is_neither_backed_up_nor_restored_over() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		// eg. a concurrent Config::forceSave() just truncated the file and is about to rewrite it
		$path = $this->non_existent_config_path();
		touch( $path );

		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$this->assertSame( '', file_get_contents( $path ) );
		$this->assertSame( array( 'TestSection' => array( 'test_key' => 'test_value' ) ), $this->get_option_data() );
	}

	public function test_restored_config_file_ends_with_end_of_file_marker_and_is_backed_up_again() {
		$this->update_option_data(
			array(
				'TestSection' => array( 'test_key' => 'test_value' ),
			)
		);

		$path = $this->non_existent_config_path();
		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$this->assert_config_file_ends_with_marker( $path );

		// round trip: the restored file passes the completeness check, so it is backed up again
		$this->update_option_data( array() );
		new GlobalSettingsProvider( null, $path, null, $this->settings );

		$stored = $this->get_option_data();
		$this->assertSame( 'test_value', $stored['TestSection']['test_key'] );
	}

	private function write_config_file( $ini_content ) {
		$path = $this->non_existent_config_path();
		file_put_contents( $path, "; <?php exit; ?> DO NOT REMOVE THIS LINE\n" . $ini_content );

		return $path;
	}

	private function assert_config_file_ends_with_marker( $path ) {
		$contents = trim( (string) file_get_contents( $path ) );

		$expected_tail = '[' . GlobalSettingsProvider::END_OF_FILE_MARKER_SECTION . "]\n"
			. GlobalSettingsProvider::END_OF_FILE_MARKER_KEY . ' = 1';

		$this->assertSame(
			$expected_tail,
			substr( $contents, - strlen( $expected_tail ) ),
			'config file does not end with the end-of-file marker, it ends with: ...' . substr( $contents, -200 )
		);
	}

	/**
	 * Builds a provider using the real config.ini.php and returns the activated plugin list the way the
	 * DI container does, ie. via PluginList. This exercises the real consumer of the [Plugins] section.
	 *
	 * @return string[]
	 */
	private function get_activated_plugins() {
		$provider    = new GlobalSettingsProvider( null, null, null, $this->settings );
		$plugin_list = new \Piwik\Application\Kernel\PluginList( $provider );

		return $plugin_list->getActivatedPlugins();
	}

	/**
	 * Builds a provider whose config.ini.php is missing so the given backup is restored, then returns
	 * the activated plugin list the way the DI container does.
	 *
	 * @param array $backup
	 * @return string[]
	 */
	private function get_activated_plugins_for_missing_file( array $backup ) {
		$this->update_option_data( $backup );

		$provider    = new GlobalSettingsProvider( null, $this->non_existent_config_path(), null, $this->settings );
		$plugin_list = new \Piwik\Application\Kernel\PluginList( $provider );

		return $plugin_list->getActivatedPlugins();
	}

	/**
	 * Returns a path to a config.ini.php that does not exist (used to simulate an accidentally deleted
	 * file). Any file created at that path during the test is cleaned up in tearDown().
	 *
	 * @return string
	 */
	private function non_existent_config_path() {
		$path = get_temp_dir() . 'matomo-wp-missing-config-' . uniqid() . '.ini.php';
		if ( file_exists( $path ) ) {
			unlink( $path );
		}
		$this->temp_files[] = $path;

		return $path;
	}

	private function get_option_data() {
		return $this->settings->get_config_backup();
	}

	private function update_option_data( array $data ) {
		$this->settings->update_config_backup( $data );
	}

	private function update_legacy_option_data( array $data ) {
		$this->settings->set_global_option( \WpMatomo\Settings::CONFIG_OPTIONS, $data );
		$this->settings->save();
	}
}
