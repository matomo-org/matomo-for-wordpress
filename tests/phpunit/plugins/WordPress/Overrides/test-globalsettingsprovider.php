<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
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

		// trusted_hosts is set by the installer, it's not part of the default section data
		unset( $default_general['trusted_hosts'] );

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
		$this->assertEquals( $default_general, $provider->getSection( 'General' ) );
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
		// the restored plugin activation state contains plugins that do not belong in WordPress
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
		return $this->settings->get_global_option( \WpMatomo\Settings::NETWORK_CONFIG_OPTIONS );
	}

	private function update_option_data( array $data ) {
		$this->settings->set_global_option( \WpMatomo\Settings::NETWORK_CONFIG_OPTIONS, $data );
		$this->settings->save();
	}
}
