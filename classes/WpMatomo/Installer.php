<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DbHelper;
use Piwik\Exception\NotYetInstalledException;
use Piwik\Plugin\API as PluginApi;
use Piwik\SettingsPiwik;
use WpMatomo\Site\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Installer {

	const OPTION_NAME_INSTALL_DATE = 'matomo-install-date';
	const OPTION_NAME_INSTALL_VERSION = 'matomo-install-version';

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
		add_action( 'activate_matomo', array( $this, 'install' ) );
	}

	public function looks_like_it_is_installed() {
		$paths       = new Paths();
		$config_file = $paths->get_config_ini_path();

		$config_dir = dirname( $config_file );
		if ( ! is_dir( $config_dir ) ) {
			wp_mkdir_p( $config_dir );
		}

		return file_exists( $config_file );
	}

	public static function is_intalled() {
		try {
			Bootstrap::do_bootstrap();

			return SettingsPiwik::isMatomoInstalled();
		} catch ( NotYetInstalledException $e ) {
			// not yet installed.... we will need to install it
		}

		return false;
	}

	public function can_be_installed() {
		$paths      = new Paths();
		$upload_dir = $paths->get_upload_base_dir();

		return is_writable( $upload_dir ) || is_writable( dirname( $upload_dir ) );
	}

	public function install() {
		if ( ! $this->can_be_installed() ) {
			return false;
		}

		try {
			// prevent session related errors during install making it more stable
			if ( ! defined( 'PIWIK_ENABLE_SESSION_START' ) ) {
				define( 'PIWIK_ENABLE_SESSION_START', false );
			}

			Bootstrap::do_bootstrap();

			if ( ! SettingsPiwik::isMatomoInstalled() || ! $this->looks_like_it_is_installed() ) {
				throw new NotYetInstalledException( 'Not yet installed' );
			}

			return false;
		} catch ( NotYetInstalledException $e ) {
			$this->logger->log( 'Matomo is not yet installed... installing now' );

			$db_info = $this->create_db();
			$this->create_config( $db_info );

			// we're scheduling another update in case there are some dimensions to be updated or anything
			// it is possible that because the plugins need to be reloaded etc that those updates are not executed right
			// away but need an actual reload and cache clearance etc
			wp_schedule_single_event( time() + 30, ScheduledTasks::EVENT_UPDATE );

			// to set up geoip in the background later... don't want this to influence the install
			wp_schedule_single_event( time() + 35, ScheduledTasks::EVENT_GEOIP );

			// in case something fails with website or user creation
			// also to set up all the other users
			wp_schedule_single_event( time() + 45, ScheduledTasks::EVENT_SYNC );

			update_option(self::OPTION_NAME_INSTALL_DATE, time());
			$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
			if ( ! empty( $plugin_data['Version'] )) {
				update_option( self::OPTION_NAME_INSTALL_VERSION, $plugin_data['Version'] );
			}

			$this->create_website();
			$this->create_user(); // we sync users as early as possible to make sure things are set up correctly
			$this->install_tracker();

			try {
				$this->logger->log( 'Matomo will now init the environment' );
				$environment = new \Piwik\Application\Environment( null );
				$environment->init();
			} catch ( \Exception $e ) {
				$this->logger->log( 'Ignoring error environment init' );
				$this->logger->log_exception( 'install_env_init', $e );
			}

			try {
				// should load and install plugins
				$this->logger->log( 'Matomo will now init the front controller and install plugins etc' );
				\Piwik\FrontController::unsetInstance(); // make sure we're loading the latest instance
				$controller = \Piwik\FrontController::getInstance();
				$controller->init();
			} catch ( \Exception $e ) {
				$this->logger->log( 'Ignoring error frontcontroller init' );
				$this->logger->log_exception( 'install_front_init', $e );
			}

			try {
				// sync user now again after installing plugins...
				// before eg the users_language table would not have been available yet
				$this->create_user();
			} catch ( \Exception $e ) {
				$this->logger->log_exception( 'install_create_user', $e );
			}

			try {
				// update plugins if there are any
				$this->update_components();
			} catch ( \Exception $e ) {
				$this->logger->log_exception( 'install_update_comp', $e );
			}

			$this->logger->log( 'Recording version and url' );

			DbHelper::recordInstallVersion();

			if ( ! SettingsPiwik::getPiwikUrl() ) {
				// especially needed for tests on cli
				SettingsPiwik::overwritePiwikUrl( plugins_url( 'app', MATOMO_ANALYTICS_FILE ) );
			}

			$this->logger->log( 'Emptying some caches' );

			\Piwik\Singleton::clearAll();
			PluginApi::unsetAllInstances();
			\Piwik\Cache::flushAll();

			$this->logger->log( 'Matomo install finished' );
		}

		return true;
	}

	private function install_tracker() {
		$this->logger->log( 'Matomo is now installing the tracker' );
		// making sure the tracker will be created in the wp uploads directory
		$updater = StaticContainer::get( 'Piwik\Plugins\CustomJsTracker\TrackerUpdater' );
		$updater->update();
	}

	private function create_db() {
		$this->logger->log( 'Matomo will now create the database' );

		try {
			$db_infos = self::get_db_infos();
			$config = Config::getInstance();
			if (isset($config)) {
				$db_infos = array_merge($config->database, $db_infos);
			}
			$config->database = $db_infos;

			DbHelper::checkDatabaseVersion();
		} catch ( \Exception $e ) {
			$message = sprintf( 'Database info detection failed with %s in %s:%s.', $e->getMessage(), $e->getFile(), $e->getLine() );
			throw new \Exception( $message, $e->getCode(), $e );
		}

		$tables_installed = DbHelper::getTablesInstalled();
		if ( count( $tables_installed ) > 0 ) {
			// todo define behaviour... might need to ask user how to proceed... but ideally we add check to
			// see if all tables are there and if so, reuse them...
			return $db_infos;
		}
		DbHelper::createTables();
		DbHelper::createAnonymousUser();
		$this->update_components();

		return $db_infos;
	}

	private function create_config( $db_info ) {
		$this->logger->log( 'Matomo is now creating the config' );
		$domain  = home_url();
		$general = array(
			'trusted_hosts' => array( $domain ),
			'salt'          => Common::generateUniqId(),
		);
		$config  = Config::getInstance();
		$path    = $config->getLocalPath();
		if ( ! is_dir( dirname( $path ) ) ) {
			wp_mkdir_p( dirname( $path ) );
		}
		$db_default      = array();
		$general_default = array();
		if ( $config->database ) {
			$db_default = $config->database;
		}
		if ( $config->General ) {
			$general_default = $config->General;
		}
		$config->database = array_merge( $db_default, $db_info );
		$config->General  = array_merge( $general_default, $general );
		$config->forceSave();

		$mode = 0664;
		@chmod( $config->getLocalPath(), $mode );
	}

	private function create_website() {
		$sync = new Sync( $this->settings );

		return $sync->sync_current_site();
	}

	private function create_user() {
		$sync = new User\Sync();

		$sync->sync_current_users();
	}

	/**
	 * @param array $default params
	 * @return array
	 */
	public static function get_db_infos( $default = array() ) {
		global $wpdb;

		$socket = '';
		$host_data = null;
		$host = null;
		$port = 3306;
		if (method_exists($wpdb, 'parse_db_host')) {
			// WP 4.9+
			$host_data = $wpdb->parse_db_host( DB_HOST );
			if ($host_data) {
				list( $host, $port, $socket, $is_ipv6 ) = $host_data;
				if (!$port && !$socket) {
					$port = 3306;
				}
			}
		}

		if (!$host_data || !$host) {
			// WP 4.8 and older
			// in case DB credentials change in wordpress, we need to apply these changes here as well on demand
			$hostParts = explode(':', DB_HOST);
			$host = $hostParts[0];
			if (count($hostParts) === 2 && is_numeric($hostParts[1])) {
				$port = $hostParts[1];
			} else {
				$port = 3306;
			}
		}

		$charset = $wpdb->charset ? $wpdb->charset : 'utf8';

		$database = array(
			'host' => $host,
			'port' => $port,
			'username' => DB_USER,
			'password' => DB_PASSWORD,
			'dbname' => DB_NAME,
			'charset' => $charset,
			'tables_prefix' => $wpdb->prefix . MATOMO_DATABASE_PREFIX,
			'adapter' => 'WordPress',
		);
		if (!empty($socket)) {
			$database['unix_socket'] = $socket;
		}
		$database = array_merge($default, $database);

		return $database;
	}

	private function update_components() {
		$this->logger->log( 'Matomo will now trigger an update' );
		Updater::unlock(); // make sure the update can be executed
		$updater = new Updater( $this->settings );
		$updater->update();
	}

}
