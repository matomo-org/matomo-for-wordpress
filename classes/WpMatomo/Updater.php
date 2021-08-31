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
use Piwik\Cache as PiwikCache;
use Piwik\Filesystem;
use Piwik\Option;
use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\SettingsServer;
use Piwik\Version;
use WP_Upgrader;
use WpMatomo\Paths;
use WpMatomo\Updater\UpdateInProgressException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Updater {
	const LOCK_NAME = 'matomo_updater';

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

	public function load_plugin_functions() {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		return function_exists( 'get_plugin_data' );
	}

	public function get_plugins_requiring_update() {
		if ( ! $this->load_plugin_functions() ) {
			return [];
		}

		$keys         = [];
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
					return [];
				}
				$keys[ $key ] = $plugin_data['Version'];
			}
		}

		return $keys;
	}

	public function update_if_needed() {
		$executed_updates = [];

		$plugins_requiring_update = $this->get_plugins_requiring_update();
		foreach ( $plugins_requiring_update as $key => $plugin_version ) {
			try {
				$this->update();
			} catch ( UpdateInProgressException $e ) {
				$this->logger->log( 'Matomo update is already in progress' );

				return; // we also don't execute any further update as they should be executed in another process
			} catch ( Exception $e ) {
				$this->logger->log_exception( 'plugin_update', $e );
				continue;
			}
			$executed_updates[] = $key;

			// we're scheduling another update in case there are some dimensions to be updated or anything
			// we do not do this in the "update" method as otherwise we might be calling this recursively...
			// it is possible that because the plugins need to be reloaded etc that those updates are not executed right
			// away but need an actual reload and cache clearance etc
			wp_schedule_single_event( time() + 15, ScheduledTasks::EVENT_UPDATE );

			update_option( $key, $plugin_version );

			// we make sure to delete cache even if no component was updated eg there may be translation updates etc
			// and caches need to be invalidated
			Filesystem::deleteAllCacheOnUpdate();
		}

		return $executed_updates;
	}

	public function update() {
		Bootstrap::do_bootstrap();

		if ( $this->load_plugin_functions() ) {
			$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );

			$history = $this->settings->get_global_option( 'version_history' );
			if ( empty( $history ) || ! is_array( $history ) ) {
				$history = [];
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

		$paths = new Paths();
		$paths->clear_cache_dir();

		Option::clearCache();
		PiwikCache::flushAll();

		\Piwik\Access::doAsSuperUser(
			function () {
				self::update_components();
				self::update_components();
			}
		);

		$upload_dir = $paths->get_upload_base_dir();

		$wp_filesystem = $paths->get_file_system();
		if ( is_dir( $upload_dir ) && is_writable( $upload_dir ) ) {
			$wp_filesystem->put_contents( $upload_dir . '/index.php', '//hello' );
			$wp_filesystem->put_contents( $upload_dir . '/index.html', '//hello' );
			$wp_filesystem->put_contents( $upload_dir . '/index.htm', '//hello' );
			$wp_filesystem->put_contents(
				$upload_dir . '/.htaccess',
				'<Files ~ "(\.mmdb)$">
' . ServerFilesGenerator::getDenyHtaccessContent() . '
</Files>
<Files ~ "(\.js)$">
' . ServerFilesGenerator::getAllowHtaccessContent() . '
</Files>'
			);
		}
		$config_dir = $paths->get_config_ini_path();
		if ( is_dir( $config_dir ) && is_writable( $config_dir ) ) {
			$wp_filesystem->put_contents( $config_dir . '/index.php', '//hello' );
			$wp_filesystem->put_contents( $config_dir . '/index.html', '//hello' );
			$wp_filesystem->put_contents( $config_dir . '/index.htm', '//hello' );
		}

		if ( $this->settings->should_disable_addhandler() ) {
			wp_schedule_single_event( time() + 10, ScheduledTasks::EVENT_DISABLE_ADDHANDLER );
		}
	}

	public function is_upgrade_in_progress() {
		if ( ! self::load_upgrader() ) {
			return 'no upgrader';
		}

		if ( self::lock() ) {
			// we can get the lock meaning no update is in progress
			self::unlock();

			return false;
		}

		return true;
	}

	private static function load_upgrader() {
		if ( ! class_exists( '\WP_Upgrader', false ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		return class_exists( '\WP_Upgrader', false );
	}

	public static function lock() {
		// prevent the upgrade from being started several times at once
		// we lock for 4 minutes. In case of major Matomo upgrades the upgrade may take much longer but it should be
		// safe in this case to run the upgrade several times
		// important: we always need to use the same timeout otherwise if something did use `create_lock(2)` then
		// even though another job locked it for 4 minutes, the other job that locks it only for 2 seconds would release
		// the lock basically since WP does not remember the initialy set release timeout
		return self::load_upgrader() && WP_Upgrader::create_lock( self::LOCK_NAME, 60 * 4 );
	}

	public static function unlock() {
		return self::load_upgrader() && WP_Upgrader::release_lock( self::LOCK_NAME );
	}

	private static function update_components() {
		$updater                     = new \Piwik\Updater();
		$components_with_update_file = $updater->getComponentUpdates();

		if ( empty( $components_with_update_file ) ) {
			return false;
		}

		if ( ! self::lock() ) {
			throw new UpdateInProgressException();
		}

		try {
			SettingsServer::setMaxExecutionTime( 0 );

			if ( function_exists( 'ignore_user_abort' ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@ignore_user_abort( true );
			}

			$result = $updater->updateComponents( $components_with_update_file );
		} catch ( Exception $e ) {
			self::unlock();
			throw $e;
		}
		self::unlock();

		if ( ! empty( $result['errors'] ) ) {
			throw new Exception( 'Error while updating components: ' . implode( ', ', $result['errors'] ) );
		}

		\Piwik\Updater::recordComponentSuccessfullyUpdated( 'core', Version::VERSION );
		Filesystem::deleteAllCacheOnUpdate();
	}
}
