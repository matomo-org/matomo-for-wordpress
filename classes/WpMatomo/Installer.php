<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DbHelper;
use Piwik\Exception\NotYetInstalledException;
use Piwik\Filesystem;
use Piwik\Plugin\API as PluginApi;
use Piwik\Plugins\Installation\FormDatabaseSetup;
use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\SettingsPiwik;
use WpMatomo\Site\Sync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Installer {

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

		$configDir = dirname( $config_file );
		if ( ! is_dir( $configDir ) ) {
			wp_mkdir_p( $configDir );
		}

		return file_exists( $config_file );
	}

	public static function is_intalled() {
		try {
			Bootstrap::do_bootstrap();

			return SettingsPiwik::isPiwikInstalled();
		} catch ( NotYetInstalledException $e ) {
		}

		return false;
	}

	public function install() {
		try {
			// prevent session related errors during install making it more stable
			if ( ! defined( 'PIWIK_ENABLE_SESSION_START' ) ) {
				define( 'PIWIK_ENABLE_SESSION_START', false );
			}

			Bootstrap::do_bootstrap();

			if ( ! SettingsPiwik::isPiwikInstalled() || ! $this->looks_like_it_is_installed() ) {
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
			wp_schedule_single_event( time() + 25, ScheduledTasks::EVENT_UPDATE );

			// to set up geoip in the background later... don't want this to influence the install
			wp_schedule_single_event( time() + 30, ScheduledTasks::EVENT_GEOIP );

			// in case something fails with website or user creation
			// also to set up all the other users
			wp_schedule_single_event( time() + 35, ScheduledTasks::EVENT_SYNC );

			$this->create_website();
			$this->create_user(); // we sync users as early as possible to make sure things are set up correctly
			$this->install_tracker();

			try {
				$this->logger->log( 'Matomo will now init the environment' );
				$environment = new \Piwik\Application\Environment( null );
				$environment->init();
			} catch ( \Exception $e ) {

			}

			try {
				// should load and install plugins
				$this->logger->log( 'Matomo will now init the front controller and install plugins etc' );
				\Piwik\FrontController::unsetInstance(); // make sure we're loading the latest instance
				$controller = \Piwik\FrontController::getInstance();
				$controller->init();
			} catch ( \Exception $e ) {

			}

			try {
				// sync user now again after installing plugins...
				// before eg the users_language table would not have been available yet
				$this->create_user();
			} catch ( \Exception $e ) {

			}

			try {
				// update plugins if there are any
				$this->update_components();
			} catch ( \Exception $e ) {

			}

			$this->logger->log( 'Recording version and url' );

			DbHelper::recordInstallVersion();

			if ( !SettingsPiwik::getPiwikUrl() ) {
				// especially needed for tests on cli
				\Piwik\SettingsPiwik::overwritePiwikUrl(plugins_url( 'app', MATOMO_ANALYTICS_FILE ));
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
		$updater = StaticContainer::get( 'Piwik\Plugins\CustomPiwikJs\TrackerUpdater' );
		$updater->update();
	}

	private function create_db() {
		$this->logger->log( 'Matomo will now create the database' );

		$form = $this->get_db_form();

		try {
			$db_infos                              = $form->createDatabaseObject();
			\Piwik\Config::getInstance()->database = $db_infos;

			DbHelper::checkDatabaseVersion();

		} catch ( \Exception $e ) {
			throw new \Exception( sprintf( 'Database creation failed with %s.', $e->getMessage() ) );
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

	private function create_config( $dbInfo ) {
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
		$config->database = array_merge( $config->database ?: array(), $dbInfo );
		$config->General  = array_merge( $config->General ?: array(), $general );
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
	 * @return FormDatabaseSetup
	 */
	private function get_db_form() {
		global $wpdb;
		$prefix = $wpdb->prefix . MATOMO_DATABASE_PREFIX;
		$form   = new FormDatabaseSetup();

		$form->addDataSource( new \HTML_QuickForm2_DataSource_SuperGlobal() );

		$host_parts = explode( ':', DB_HOST );
		$host       = $host_parts[0];

		$hostname = $form->getElementsByName( 'host' );
		array_shift( $hostname )->setValue( $host );

		$username = $form->getElementsByName( 'username' );
		array_shift( $username )->setValue( DB_USER );

		$password = $form->getElementsByName( 'password' );
		array_shift( $password )->setValue( DB_PASSWORD );

		$name = $form->getElementsByName( 'dbname' );
		array_shift( $name )->setValue( DB_NAME );

		$tables_prefix = $form->getElementsByName( 'tables_prefix' );
		array_shift( $tables_prefix )->setValue( $prefix );

		$adapter = $form->getElementsByName( 'adapter' );
		$adapter = array_shift( $adapter );
		$adapter->loadOptions( array( 'Wordpress' => 'Wordpress' ) );
		$adapter->setValue( 'Wordpress' );

		$engine = $form->getElementsByName( 'type' );
		array_shift( $engine )->setValue( 'InnoDB' );

		return $form;
	}

	private function update_components() {
		$this->logger->log( 'Matomo will now trigger an update' );
		$updater = new Updater( $this->settings );
		$updater->update();
	}

}
