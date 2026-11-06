<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Exception;
use Piwik\Plugin\Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Installs a Matomo plugin in the request that activates it. Otherwise the next request finds an
 * uninstalled component and runs the full install, during which other requests that use Matomo will
 * fatal.
 */
class PluginActivationInstaller extends Feature {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->logger   = new Logger();
	}

	public function register_hooks() {
		add_action( 'activated_plugin', [ $this, 'on_plugin_activated' ] );
	}

	public function remove_hooks() {
		remove_action( 'activated_plugin', [ $this, 'on_plugin_activated' ] );
	}

	/**
	 * @param string $plugin main plugin file, relative to the plugins directory
	 * @return bool true if the plugin was installed
	 */
	public function on_plugin_activated( $plugin ) {
		$wp_plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
		$plugin_dir     = dirname( $wp_plugin_file );

		// check if this is a matomo plugin
		if ( ! is_file( $plugin_dir . '/plugin.json' )
			|| ! matomo_is_plugin_compatible( $wp_plugin_file )
		) {
			return false;
		}

		// check if core is not installed or an install is in progress
		$installer = new Installer( $this->settings );
		if ( ! $installer->is_core_installed()
			|| $installer->is_install_in_progress()
		) {
			return false;
		}

		// the plugin registers itself on plugins_loaded, which already ran in this request,
		// so we call it manually here.
		matomo_add_plugin( $plugin_dir, $wp_plugin_file, true );

		$was_bootstrapped = Bootstrap::is_environment_bootstrapped();

		try {
			// clear and recreate the existing environment
			Bootstrap::destroy_bootstrapped_environment();

			if ( $was_bootstrapped ) {
				Manager::initPluginDirectories();
			}

			Bootstrap::do_bootstrap();

			// adds the plugin's dimension columns and records its version
			$updater = new Updater( $this->settings );
			$updated = $updater->update_if_needed();
			if ( ! is_array( $updated ) ) {
				return false; // update in progress or failed, left to the full install
			}

			$installer->record_installed_components();
		} catch ( Exception $e ) {
			$this->logger->log_exception( 'plugin_activation_install', $e );
			return false;
		} finally {
			Bootstrap::destroy_bootstrapped_environment();
		}

		return true;
	}
}
