<?php
/**
 * @package matomo
 */

use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\PluginActivationInstaller;
use WpMatomo\Settings;

class PluginActivationInstallerTest extends MatomoAnalytics_TestCase {

	const TEST_PLUGIN      = 'MatomoActivationTestPlugin';
	const TEST_PLUGIN_FILE = 'MatomoActivationTestPlugin/MatomoActivationTestPlugin.php';

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var PluginActivationInstaller
	 */
	private $activation_installer;

	/**
	 * @var array
	 */
	private $original_globals;

	public function setUp(): void {
		parent::setUp();

		$this->original_globals = $this->get_plugin_globals();

		$this->settings             = new Settings();
		$this->activation_installer = new PluginActivationInstaller( $this->settings );

		$this->copy_test_plugin_to_plugins_dir();
	}

	public function tearDown(): void {
		$this->remove_test_plugin_from_plugins_dir();

		foreach ( $this->original_globals as $name => $value ) {
			$GLOBALS[ $name ] = $value; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		}

		parent::tearDown();
	}

	public function test_on_plugin_activated_should_install_the_plugin_when_matomo_is_not_loaded_yet() {
		Bootstrap::destroy_bootstrapped_environment();

		$this->assertTrue( $this->activation_installer->on_plugin_activated( self::TEST_PLUGIN_FILE ) );

		$this->assert_test_plugin_installed();
	}

	public function test_on_plugin_activated_should_install_the_plugin_when_matomo_was_already_loaded() {
		Bootstrap::do_bootstrap();

		$this->assertTrue( $this->activation_installer->on_plugin_activated( self::TEST_PLUGIN_FILE ) );

		$this->assert_test_plugin_installed();
	}

	public function test_on_plugin_activated_should_ignore_plugins_without_a_plugin_json() {
		$this->assertFalse( $this->activation_installer->on_plugin_activated( 'hello.php' ) );

		$this->assertNotContains( WP_PLUGIN_DIR . '/hello.php', $GLOBALS['MATOMO_PLUGIN_FILES'] );
	}

	public function test_on_plugin_activated_should_leave_the_plugin_to_the_full_install_when_one_is_in_progress() {
		update_option( Settings::OPTION_PREFIX . 'install-start-time', time() );

		$this->assertFalse( $this->activation_installer->on_plugin_activated( self::TEST_PLUGIN_FILE ) );

		Bootstrap::do_bootstrap();
		$this->assertFalse( $this->table_exists( 'matomo_activation_test' ) );
	}

	public function test_on_plugin_activated_should_leave_the_plugin_to_the_full_install_when_core_is_not_installed() {
		$this->settings->set_option( Settings::INSTANCE_COMPONENTS_INSTALLED, '[]' );
		$this->settings->save();

		$this->assertFalse( $this->activation_installer->on_plugin_activated( self::TEST_PLUGIN_FILE ) );

		Bootstrap::do_bootstrap();
		$this->assertFalse( $this->table_exists( 'matomo_activation_test' ) );
	}

	private function assert_test_plugin_installed() {
		$installer = new Installer( $this->settings );
		$this->assertTrue( $installer->is_current_instance_installed() );

		Bootstrap::do_bootstrap();
		$this->assertTrue( $this->table_exists( 'matomo_activation_test' ) );

		$columns = array_column( \Piwik\Db::fetchAll( 'SHOW COLUMNS IN ' . \Piwik\Common::prefixTable( 'log_visit' ) ), 'Field' );
		$this->assertContains( 'matomo_activation_test', $columns );
	}

	private function table_exists( $table ) {
		$tables = \Piwik\Db::fetchAll( 'SHOW TABLES LIKE ?', [ \Piwik\Common::prefixTable( $table ) ] );
		return ! empty( $tables );
	}

	private function get_plugin_globals() {
		$globals = [];
		foreach ( [ 'MATOMO_PLUGIN_FILES', 'MATOMO_PLUGIN_DIRS', 'MATOMO_PLUGINS_ENABLED', 'MATOMO_MARKETPLACE_PLUGINS' ] as $name ) {
			$globals[ $name ] = isset( $GLOBALS[ $name ] ) ? $GLOBALS[ $name ] : [];
		}
		return $globals;
	}

	private function copy_test_plugin_to_plugins_dir() {
		$source = __DIR__ . '/resources/' . self::TEST_PLUGIN;
		$target = WP_PLUGIN_DIR . '/' . self::TEST_PLUGIN;

		wp_mkdir_p( $target . '/Columns' );
		foreach ( [ 'plugin.json', self::TEST_PLUGIN . '.php', 'Columns/ActivationTestColumn.php' ] as $file ) {
			copy( $source . '/' . $file, $target . '/' . $file );
		}
	}

	private function remove_test_plugin_from_plugins_dir() {
		$target = WP_PLUGIN_DIR . '/' . self::TEST_PLUGIN;
		foreach ( [ 'plugin.json', self::TEST_PLUGIN . '.php', 'Columns/ActivationTestColumn.php' ] as $file ) {
			if ( is_file( $target . '/' . $file ) ) {
				unlink( $target . '/' . $file );
			}
		}
		foreach ( [ $target . '/Columns', $target ] as $dir ) {
			if ( is_dir( $dir ) ) {
				rmdir( $dir );
			}
		}
	}
}
