<?php

use WpMatomo\Site;
use WpMatomo\WpStatistics\Importer;
use WpMatomo\Report\Data;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;

class ImportTest extends MatomoAnalytics_TestCase {
	/**
	 * static due to multiple tests instanciations
	 *
	 * @var bool
	 */
	protected static $imported = false;
	/**
	 * @var null|bool
	 */
	private $enabled = null;
	/**
	 * @var Data
	 */
	private $data;

	private function can_be_tested() {
		if ( is_null( $this->enabled ) ) {
			$this->enabled = file_exists( $this->plugin_file() );
		}

		return $this->enabled;
	}

	private function plugin_file() {
		return ABSPATH . 'wp-content/plugins/wp-statistics/wp-statistics.php';
	}

	public function manually_load_plugin() {
		$file = $this->plugin_file();
		if ( file_exists( $file ) ) {
			require_once $file;

			$wp_statistics = \WP_Statistics();
			if ( method_exists( $wp_statistics, 'plugin_setup' ) ) {
				$wp_statistics->plugin_setup();
			} else {
				$wp_statistics->includes();
			}
		}
	}

	public function setUp(): void {
		parent::setUp();
		$this->create_set_super_admin();
		if ( $this->can_be_tested() ) {
			WP_Filesystem();
			global $wp_filesystem;

			require_once $this->plugin_file();

			set_current_screen( 'edit-post' ); // so is_admin() will return true

			$this->data = new Data();

			// import the dump file
			global $wpdb;
			$file = dirname( __FILE__ ) . '/dump.sql';
			// wpdb does not allow multiple queries in the query method
			foreach ( explode( ';', str_replace( 'wp_', $wpdb->prefix, $wp_filesystem->get_contents( $file ) ) ) as $query ) {
				if ( ! empty( trim( $query ) ) ) {
					// phpcs:ignore WordPress.DB
					$wpdb->query( $query );
				}
			}

			if ( true !== self::$imported ) {
				// must be set quickly due to the concurrent running tests
				self::$imported = true;
				$this->download_geoip();
				$this->manually_load_plugin();
			}

			update_option( 'wp_statistics_plugin_version', '1.0' ); // force upgrade from old sql dump version

			// update the wp-statistics database
			if ( method_exists( \WP_STATISTICS\Install::class, 'create_table' ) ) {
				\WP_STATISTICS\Install::create_table( is_multisite() );
				\WP_STATISTICS\Install::create_options();
			} else {
				\WP_STATISTICS\Option::saveOptionGroup( 'migrated', false, 'db' );
				\WP_STATISTICS\Option::saveOptionGroup( 'check', false, 'db' );

				if ( is_multisite() ) {
					// phpcs:ignore WordPress.DB
					$blog_ids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
					foreach ( $blog_ids as $blog_id ) {
						switch_to_blog( $blog_id );
						\WP_Statistics\Service\Database\Managers\MigrationHandler::runMigrations();
						restore_current_blog();
					}
				} else {
					\WP_Statistics\Service\Database\Managers\MigrationHandler::runMigrations();
				}

				// invoke the schema migration process manually, since the HTTP request
				// wp-statistics normally makes to start it asynchronously, does not work
				// in the test environment
				try {
					$process = WP_Statistics()->getBackgroundProcess( 'schema_migration_process' );
					$method  = new \ReflectionMethod( $process, 'handle' );
					$method->setAccessible( true );
					$method->invoke( $process );
				} catch ( \WPDieException $ex ) {
					// ignore
				}
			}

			$this->upgrade_wp_stats();

			// run the import
			$importer = new Importer( new \Psr\Log\NullLogger() );
			$site     = new Site();
			$id_site  = $site->get_current_matomo_site_id();
			// do not run the archiving for performances issues and because we test only daily reports
			$importer->set_should_rethrow( true );
			$importer->import( $id_site, false );
		}
	}

	/**
	 * Download the geoip database for the GeoIP2 client
	 *
	 * @return void
	 * @throws \Exception In case there is an error while downloading the geoip database.
	 */
	private function download_geoip() {
		// wpstatistics fails to download geoip during tests, so we do it ourselves and link it into the wpstatistics directory
		$wp_statistics_geoip_url = 'https://cdn.jsdelivr.net/npm/geolite2-city/GeoLite2-City.mmdb.gz';

		$schedule_task = new ScheduledTasks( new Settings() );
		$schedule_task->update_geo_ip2_db( $wp_statistics_geoip_url );

		$expected_path = ABSPATH . '/wp-content/uploads/matomo/GeoIP2-City.mmdb';
		if ( ! is_file( $expected_path ) ) {
			throw new \Exception( 'failed to download geoip database. contents of upload directory: ' . var_export( scandir( dirname( $expected_path ) ), true ) );
		}

		$wpstats_database_path = ABSPATH . '/wp-content/uploads/wp-statistics/GeoLite2-City.mmdb';
		if ( ! is_dir( dirname( $wpstats_database_path ) ) ) {
			mkdir( dirname( $wpstats_database_path ), 0777, true );
		}
		if ( ! is_file( $wpstats_database_path ) ) {
			symlink( $expected_path, $wpstats_database_path );
		}
	}

	public function test_countries_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCountry' );
		$this->assertGreaterThan( 80, $report['reportData']->getRowsCount() );
	}

	public function test_regions_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getRegion' );
		$this->assertGreaterThan( 300, $report['reportData']->getRowsCount() );
	}

	public function test_cities_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCity' );
		// 500 due to the limit in the datatable
		$this->assertEquals( 500, $report['reportData']->getRowsCount() );
	}

	public function test_browsers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getBrowsers' );
		$this->assertGreaterThanOrEqual( 15, $report['reportData']->getRowsCount() );
	}

	public function test_os_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getOsVersions' );
		$this->assertEquals( 10, $report['reportData']->getRowsCount() );
	}

	public function test_referrers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getWebsites' );
		$this->assertEquals( 49, $report['reportData']->getRowsCount() );
	}

	public function test_search_engines_found() {
		$this->markTestSkipped( 'test data not yet up to date' );

		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getSearchEngines' );
		$this->assertEquals( 6, $report['reportData']->getRowsCount() );
	}

	public function test_visitors_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'VisitsSummary', 'get' );
		$row    = $report['reportData']->getFirstRow();

		$this->assertInstanceOf( \Piwik\DataTable\Row::class, $row );
		$this->assertEquals( 1298, $row->getColumn( 'nb_visits' ) );
	}

	public function test_pages_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Actions', 'getPageUrls' );
		$this->assertGreaterThan( 75, $report['reportData']->getRowsCount() );
	}

	protected function fetch_report( $report_name, $method ) {
		$meta = array(
			'module'     => $report_name,
			'action'     => $method,
			'parameters' => array(),
		);

		return $this->data->fetch_report( $meta, 'day', '2020-10-17', 'nb_visits', 10000 );
	}

	private function upgrade_wp_stats() {
		$install = new class() extends \WP_STATISTICS\Install {
			public function __construct() {
				// skip since we don't want to handle hooks again
			}
		};
		$install->plugin_upgrades();
	}
}
