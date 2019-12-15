<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Piwik\Filesystem;
use Piwik\Plugins\CoreUpdater\CoreUpdater;
use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\Version;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Updater {
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
		$this->logger = new Logger();
	}

	private function load_plugin_functions() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return function_exists( 'get_plugin_data' );
	}

	public function update_if_needed() {
		if ( ! $this->load_plugin_functions() ) {
			return;
		}

		$executed_updates = array();

		$plugin_files = $GLOBALS['MATOMO_PLUGIN_FILES'];
		if ( ! in_array( MATOMO_ANALYTICS_FILE, $plugin_files, true ) ) {
			$plugin_files[] = MATOMO_ANALYTICS_FILE;
			// making sure this plugin is in the list so when itself gets updated
			// it will execute the core updates
		}

		foreach ( $GLOBALS['MATOMO_PLUGIN_FILES'] as $plugin_file ) {
			$plugin_data = get_plugin_data( $plugin_file, $markup = false, $translate = false );

			$key           = Settings::OPTION_PREFIX . 'plugin-version-' . basename( str_ireplace( '.php', '', $plugin_file ) );
			$installed_ver = get_option( $key );
			if ( ! $installed_ver || $installed_ver !== $plugin_data['Version'] ) {
				if ( ! Installer::is_intalled() ) {
					return;
				}

				try {
					$this->update();
				} catch (\Exception $e) {
					$this->logger->log('Matomo failed to execute update ' . $e->getMessage());
					$this->logger->log_exception( 'plugin_update' , $e);
					throw $e;
				}
				$executed_updates[] = $key;

				// we're scheduling another update in case there are some dimensions to be updated or anything
				// we do not do this in the "update" method as otherwise we might be calling this recursively...
				// it is possible that because the plugins need to be reloaded etc that those updates are not executed right
				// away but need an actual reload and cache clearance etc
				wp_schedule_single_event( time() + 5, ScheduledTasks::EVENT_UPDATE );

				update_option( $key, $plugin_data['Version'] );

				// we make sure to delete cache even if no component was updated eg there may be translation updates etc
				// and caches need to be invalidated
				Filesystem::deleteAllCacheOnUpdate();
			}
		}

		return $executed_updates;
	}

	public function update() {
		Bootstrap::do_bootstrap();

		$error_handler = new ErrorHandler();
		$error_handler->set_error_handler('update');

		if ( $this->load_plugin_functions() ) {
			$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );

			$history = $this->settings->get_global_option( 'version_history' );
			if ( empty( $history ) || ! is_array( $history ) ) {
				$history = array();
			}

			if ( ! empty( $plugin_data['Version'] )
				&& ! in_array( $plugin_data['Version'], $history, true ) ) {
				// this allows us to see which versions of matomo the user was using before this update so we better understand
				// which version maybe regressed something
				array_unshift( $history, $plugin_data['Version'] );
				$history = array_slice( $history, 0, 5 ); // lets keep only the last 5 versions
				$this->settings->set_global_option( 'version_history', $history );
			}
		}

		$this->settings->set_global_option( 'core_version', Version::VERSION );
		$this->settings->save();

		\Piwik\Access::doAsSuperUser(
			function () {
					self::update_components();
					self::update_components();
			}
		);

		$paths      = new Paths();
		$upload_dir = $paths->get_upload_base_dir();
		if ( is_dir( $upload_dir ) && is_writable( $upload_dir ) ) {
			@file_put_contents( $upload_dir . '/index.php', '//hello' );
			@file_put_contents( $upload_dir . '/index.html', '//hello' );
			@file_put_contents( $upload_dir . '/index.htm', '//hello' );
			@file_put_contents(
				$upload_dir . '/.htaccess',
				'<Files GeoLite2-City.mmdb>
' . ServerFilesGenerator::getDenyHtaccessContent() . '
</Files>
<Files ~ "(\.js)$">
' . ServerFilesGenerator::getAllowHtaccessContent() . '
</Files>'
			);
		}
		$config_dir = $paths->get_config_ini_path();
		if ( is_dir( $config_dir ) && is_writable( $config_dir ) ) {
			@file_put_contents( $config_dir . '/index.php', '//hello' );
			@file_put_contents( $config_dir . '/index.html', '//hello' );
			@file_put_contents( $config_dir . '/index.htm', '//hello' );
		}

		$error_handler->restore();
	}

	private static function update_components() {
		$updater                     = new \Piwik\Updater();
		$components_with_update_file = CoreUpdater::getComponentUpdates( $updater );

		if ( empty( $components_with_update_file ) ) {
			return false;
		}

		CoreUpdater::updateComponents( $updater, $components_with_update_file );

		\Piwik\Updater::recordComponentSuccessfullyUpdated( 'core', Version::VERSION );
		Filesystem::deleteAllCacheOnUpdate();
	}
}
