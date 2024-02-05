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
			$GLOBALS['WP_Statistics']->includes();
		}
	}

	public function setUp(): void {
		parent::setUp();
		$this->create_set_super_admin();
		if ( $this->can_be_tested() ) {
			WP_Filesystem();
			global $wp_filesystem;

			require_once $this->plugin_file();

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
			// run the import
			$importer = new Importer( new \Psr\Log\NullLogger() );
			$site     = new Site();
			$id_site  = $site->get_current_matomo_site_id();
			// do not run the archiving for performances issues and because we test only daily reports
			$importer->import( $id_site, false );
		}
	}

	/**
	 * Download the geoip database for the GeoIP2 client
	 *
	 * @return void
	 * @throws Exception In case there is an error while downloading the geoip database.
	 */
	private function download_geoip() {
		$schedule_task = new ScheduledTasks( new Settings() );
		$schedule_task->update_geo_ip2_db();
	}

	public function test_countries_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCountry' );
		$this->assertEquals( 89, $report['reportData']->getRowsCount() );
	}

	public function test_regions_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getRegion' );
		$this->assertEquals( 308, $report['reportData']->getRowsCount() );
	}

	public function test_cities_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'UserCountry', 'getCity' );
		// 500 due to the limit in the datatable
		$this->assertEquals( $report['reportData']->getRowsCount(), 500 );
	}

	public function test_browsers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getBrowsers' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 15 );
	}

	public function test_os_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'DevicesDetection', 'getOsVersions' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 10 );
	}

	public function test_referrers_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getWebsites' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 49 );
	}

	public function test_search_engines_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getSearchEngines' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 6 );
	}

	public function test_keywords_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'Referrers', 'getKeywords' );
		$this->assertEquals( $report['reportData']->getRowsCount(), 2 );
	}

	public function test_visitors_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$report = $this->fetch_report( 'VisitsSummary', 'get' );
		$this->assertEquals( $report['reportData']->getFirstRow()->getColumn( 'nb_visits' ), 1298 );
	}

	public function test_pages_found() {
		if ( ! $this->can_be_tested() ) {
			$this->markTestSkipped( 'CI or plugin unavailable' );

			return;
		}

		$expected = version_compare( getenv( 'WORDPRESS_VERSION' ), '5.3', '<' ) ? 156 : 81;

		$report = $this->fetch_report( 'Actions', 'getPageUrls' );
		$this->assertEquals( $expected, $report['reportData']->getRowsCount() );
	}

	protected function fetch_report( $report_name, $method ) {
		$meta = array(
			'module'     => $report_name,
			'action'     => $method,
			'parameters' => array(),
		);

		return $this->data->fetch_report( $meta, 'day', '2020-10-17', 'nb_visits', 10000 );
	}
}
