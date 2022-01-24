<?php

/**
 * $ImportTest = new ImportTest();
 * tests_add_filter( 'muplugins_loaded', [ $ImportTest, 'manually_load_plugin' ] );
 */
if ( ! defined( 'MATOMO_DATABASE_PREFIX' ) ) {
	define( 'MATOMO_DATABASE_PREFIX', 'matomo_' );
}

use WpMatomo\Site;
use WpMatomo\WpStatistics\Importer;
use WpMatomo\WpStatistics\Logger\EchoLogger;
use WpMatomo\Report\Data;
use Piwik\Plugins\UserCountry\Archiver;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use Piwik\Archive;
use Piwik\ArchiveProcessor\PluginsArchiver;
use Piwik\Cache;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable\Manager;
use Piwik\Date;
use Piwik\FrontController;
use Piwik\Option;
use Piwik\Plugin\API;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Roles;
use WpMatomo\Uninstaller;
use WpMatomo\Report\Metadata;

class ImportTest extends MatomoAnalytics_TestCase {
	/**
	 * static due to multiple tests instanciations
	 * @var bool
	 */
	protected static $imported = false;
	/**
	 * @var null|bool does test can be runned?
	 */
	private $enabled = null;
	/**
	 * @var Data
	 */
	private $data;

	public function __construct() {
		parent::__construct();
		if ( $this->can_be_tested() ) {
			WP_Filesystem();
			global $wp_filesystem;
			$this->data = new Data();

			// import the dump file
			global $wpdb;
			$file = dirname( __FILE__ ) . '/dump.sql';
			// wpdb does not allow multiple queries in the query method
			foreach ( explode( ';', str_replace( 'wp_', $wpdb->prefix, $wp_filesystem->get_contents( $file ) ) ) as $query ) {
				if ( ! empty( trim( $query ) ) ) {
					$wpdb->query( $query );
				}
			}
		}
		$uninstall = new Uninstaller();
		$uninstall->uninstall( true );
	}

	private function can_be_tested() {
		if ( is_null( $this->enabled ) ) {
			$this->enabled = ( ! getenv( 'TRAVIS' ) && file_exists( $this->plugin_file() ) );
		}

		return $this->enabled;

	}

	private function plugin_file() {
		return dirname( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) ) . '/wp-statistics/wp-statistics.php';
	}

	/**
	 * Copy the content of parent::setUp to allow to disable the cleaning of all existing data.
	 * Otherwise we should run the import for each test which could be time consuming
	 * @return void
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function setUp() {
		if ( ! defined( 'PIWIK_TEST_MODE' ) ) {
			define( 'PIWIK_TEST_MODE', true );
		}
		$uninstall = new Uninstaller();
		$uninstall->uninstall( false );
		clearstatcache();

		Bootstrap::set_not_bootstrapped();

		$settings  = new Settings();
		$installer = new Installer( $settings );
		$installer->install();

		// we need to init roles again... seems like WP isn't doing this by themselves...
		// otherwise if one test adds eg Capability WRITE_MATOMO to a role "editor", in other tests this
		// capability will still be present
		global $wp_roles;
		$wp_roles->init_roles();

		$roles = new Roles( $settings );
		$roles->add_roles();

		add_action(
			'set_current_user',
			function () {
				// auth might still be pointing to a different user...
				Bootstrap::set_not_bootstrapped();
			}
		);

		add_action(
			'matomo_uninstall',
			function () {
				Option::clearCache();
				Cache::flushAll();
				\Piwik\Singleton::clearAll();
				API::unsetAllInstances();
				ArchiveTableCreator::clear();
				\Piwik\Site::clearCache();
				Archive::clearStaticCache();
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				FrontController::$requestId = null;
				Date::$now                  = null;
				\Piwik\Tracker\Cache::deleteTrackerCache();
				\Piwik\NumberFormatter::getInstance()->clearCache();
				\Piwik\Plugins\ScheduledReports\API::$cache = array();
				Manager::getInstance()->deleteAll();
				\WpMatomo\Updater::unlock();
				PluginsArchiver::$archivers = array();
				$_GET                       = array();
				$_REQUEST                   = array();
				\Piwik\Container\StaticContainer::get( \Piwik\Translation\Translator::class )->reset();
				\Piwik\Log::unsetInstance();
			}
		);

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}
		/*
		 * begin custom part of the setUp
		 */
		\WpMatomo\Bootstrap::do_bootstrap();
		$this->create_set_super_admin();
		if ( self::$imported !== true ) {
			// must be run soon!
			self::$imported = true;
			$this->manually_load_plugin();
			// download the geoip database to avoid the GeoIP2 exception
			$this->download_geoip();

			// run the import
			$importer = new Importer( new EchoLogger() );
			$site     = new Site();
			$id_site  = $site->get_current_matomo_site_id();
			// disable the archiving for performances issues
			$importer->import( $id_site, false );
		}


	}

	public function manually_load_plugin() {
		$file = $this->plugin_file();
		if ( file_exists( $file ) ) {
			require_once $file;
			// no autoloader, load it manually
			$GLOBALS['WP_Statistics']->includes();
		}
	}

	/**
	 * Download the geoip database for the GeoIP2 client
	 * @return void
	 * @throws Exception
	 */
	private function download_geoip() {
		$schedule_task = new ScheduledTasks( new Settings() );
		$schedule_task->update_geo_ip2_db();
	}

	/**
	 * Override parent method to avoid cleaning after tests
	 * @return void
	 */
	public function tearDown() {
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}

		$uninstall = new Uninstaller();
		$uninstall->uninstall( false );

		unset( $_GET['trigger'] );
		Metadata::clear_cache();
	}

	public function test_countries_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCountry' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 88 );
	}

	protected function fetch_report( $report_name, $method ) {
		$meta = array(
			'module'     => $report_name,
			'action'     => $method,
			'parameters' => array(),
		);

		return $this->data->fetch_report( $meta, 'day', '2020-10-17', 'nb_visits', 10000 );
	}

	public function test_regions_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getRegion' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 214 );
	}

	public function test_cities_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCity' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 770 );
	}

	public function test_browsers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getBrowsers' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 15 );
	}

	public function test_os_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getOsVersions' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 10 );
	}

	public function test_referrers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getWebsites' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 49 );
	}

	public function test_search_engines_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getSearchEngines' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 6 );
	}

	public function test_keywords_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getKeywords' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 2 );
	}

	public function test_visitors_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'Travis or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'VisitsSummary', 'get' );
		$this->assertEquals( $report['reportData']->getFirstRow()->getColumn( 'nb_visits' ), 1298 );
	}
}