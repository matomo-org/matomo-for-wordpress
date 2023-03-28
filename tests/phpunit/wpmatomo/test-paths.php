<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model as SitesModel;
use Piwik\Plugins\UsersManager\Model as UsersModel;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Paths;

class PathsTest extends MatomoUnit_TestCase {

	/**
	 * @var Paths
	 */
	private $paths;
	/**
	 * @var string the current WordPress root path
	 */
	private $root_path;
	/**
	 * @var string alternate root path containing matomo
	 */
	private $root_path_with_matomo;

	public function __construct() {
		parent::__construct();
		$this->root_path             = realpath( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . '/../../../' );
		$this->root_path_with_matomo = dirname( $this->root_path ) . '/matomo';
	}
	public function setUp() {
		parent::setUp();

		$this->paths = $this->make_paths();
	}

	public function tearDown() {
		if ( is_dir( $this->root_path_with_matomo ) ) {
			if ( is_link( $this->root_path ) ) {
				unlink( $this->root_path );
			}
			rename( $this->root_path_with_matomo, $this->root_path );
			chdir( $this->root_path );
		}
		parent::tearDown();
	}

	private function make_paths() {
		return new Paths();
	}

	public function test_get_upload_base_dir() {
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo', $this->paths->get_upload_base_dir() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_upload_base_dir_forBlog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}
		$blogid1 = self::factory()->blog->create();
		switch_to_blog( 2 );
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/sites/2/matomo', $this->paths->get_upload_base_dir() );

		wp_delete_site( $blogid1 );
	}

	public function test_get_upload_base_url() {
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/wp-content/uploads/matomo', $this->paths->get_upload_base_url() );
	}

	public function test_get_matomo_js_upload_path() {
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo/matomo.js', $this->paths->get_matomo_js_upload_path() );
	}

	public function test_get_tracker_api_rest_api_endpoint() {
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/index.php?rest_route=/matomo/v1/hit/', $this->paths->get_tracker_api_rest_api_endpoint() );
	}

	public function test_get_js_tracker_rest_api_endpoint() {
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/index.php?rest_route=/matomo/v1/hit/', $this->paths->get_js_tracker_rest_api_endpoint() );
	}

	public function test_get_tracker_api_url_in_matomo_dir() {
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/wp-content/plugins/matomo/app/matomo.php', $this->paths->get_tracker_api_url_in_matomo_dir() );
	}

	public function test_get_js_tracker_url_in_matomo_dir() {
		$this->assertSame( 'http://' . WP_TESTS_DOMAIN . '/wp-content/plugins/matomo/app/matomo.js', $this->paths->get_js_tracker_url_in_matomo_dir() );
	}

	public function test_get_config_ini_path() {
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo/config/config.ini.php', $this->paths->get_config_ini_path() );
	}

	public function test_get_tmp_dir() {
		if ( is_multisite() ) {
			$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo/tmp', $this->paths->get_tmp_dir() );
		} else {
			$this->assertSame( get_temp_dir() . 'wordpress/wp-content/cache/matomo', $this->paths->get_tmp_dir() );
		}
	}

	public function test_get_relative_dir_to_matomo() {
		$valid_values = array(
			'../../matomo/tests/phpunit/wpmatomo', // travis
		);
		$val          = $this->paths->get_relative_dir_to_matomo( __DIR__ );
		$this->assertTrue( in_array( $val, $valid_values, true ) );
		// automatically double check that it works
		$this->assertTrue( is_dir( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/../tests/phpunit/wpmatomo' ) );
	}

	/**
	 * rename part of the document root to add matomo in its path
	 */
	private function add_matomo_in_document_root() {
		$renamed = false;
		if ( is_writeable( $this->root_path ) ) {
			// replace the last part of the root path by matomo
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$renamed = @rename( $this->root_path, $this->root_path_with_matomo );
			if ( $renamed ) {
				// create a link for the phpunit dependencies
				$renamed = symlink( $this->root_path_with_matomo, $this->root_path );
			}
		}
		return $renamed;
	}

	/**
	 * @return string get the matomo.php file in the folder which contains matomo in its path
	 */
	private function get_alternate_matomo_analytics_file() {
		return $this->root_path_with_matomo . '/wp-content/plugins/matomo/matomo.php';
	}

	public function test_get_relative_dir_to_matomo_with_matomo_in_path_for_tracker_js() {
		// run the test only if we have been able to rename the path
		if ( $this->add_matomo_in_document_root() ) {
			$valid_values                    = array(
				'../../matomo/app/matomo.js',
			);
			$temporary_matomo_analytics_file = $this->get_alternate_matomo_analytics_file();
			$val                             = $this->paths->get_relative_dir_to_matomo( plugin_dir_path( $temporary_matomo_analytics_file ) . 'app/matomo.js', $temporary_matomo_analytics_file );
			$this->assertTrue( in_array( $val, $valid_values, true ) );
			// automatically double check that it works
			$this->assertTrue( is_file( plugin_dir_path( $temporary_matomo_analytics_file ) . 'app/matomo.js' ) );
		} else {
			$this->markTestSkipped( 'Can t rename.' );
		}
	}

	public function test_get_relative_dir_to_matomo_with_matomo_in_path_for_upload_dir() {
		// run the test only if we have been able to rename the path
		if ( $this->add_matomo_in_document_root() ) {
			$valid_values = array(
				'../../matomo/../../uploads/matomo',
			);

			echo '___________________________________' . PHP_EOL;
			echo $valid_values[0] . PHP_EOL;
			echo realpath( $valid_values[0] ) . PHP_EOL;
			$temporary_matomo_analytics_file = $this->get_alternate_matomo_analytics_file();
			echo $temporary_matomo_analytics_file . PHP_EOL;
			echo realpath( $temporary_matomo_analytics_file ) . PHP_EOL;

			$temporary_matomo_analytics_file = $this->get_alternate_matomo_analytics_file();
			// do not use the path get upload dir method: it returns the path on the test instance
			$val = $this->paths->get_relative_dir_to_matomo( plugin_dir_path( $temporary_matomo_analytics_file ) . '../../uploads/matomo', $temporary_matomo_analytics_file );
			echo $val . PHP_EOL;
			$this->assertTrue( in_array( $val, $valid_values, true ) );
			// automatically double check that it works
			$this->assertTrue( is_dir( plugin_dir_path( $temporary_matomo_analytics_file ) . '../../uploads/matomo' ) );
		} else {
			$this->markTestSkipped( 'Can t rename.' );
		}
	}

	public function test_get_relative_dir_to_matomo_with_matomo_in_path_for_upload_dir_config() {
		// run the test only if we have been able to rename the path
		if ( $this->add_matomo_in_document_root() ) {
			$valid_values = array(
				'../../matomo/../../uploads/matomo/config',
			);

			echo '___________________________________' . PHP_EOL;
			echo $valid_values[0] . PHP_EOL;
			echo realpath( $valid_values[0] ) . PHP_EOL;
			$temporary_matomo_analytics_file = $this->get_alternate_matomo_analytics_file();
			echo $temporary_matomo_analytics_file . PHP_EOL;
			echo realpath( $temporary_matomo_analytics_file ) . PHP_EOL;

			$temporary_matomo_analytics_file = $this->get_alternate_matomo_analytics_file();
			// do not use the path get upload dir method: it returns the path on the test instance
			$val = $this->paths->get_relative_dir_to_matomo( plugin_dir_path( $temporary_matomo_analytics_file ) . '../../uploads/matomo/config', $temporary_matomo_analytics_file );
			echo $val . PHP_EOL;
			$this->assertTrue( in_array( $val, $valid_values, true ) );
			// automatically double check that it works
			$this->assertTrue( is_dir( plugin_dir_path( $temporary_matomo_analytics_file ) . '../../uploads/matomo/config' ) );
		} else {
			$this->markTestSkipped( 'Can t rename.' );
		}
	}

	public function test_clear_assets_dir_does_not_fail() {
		$this->paths->clear_assets_dir();
		// for the phpunit warning
		$this->assertTrue( true );
	}

	public function test_clear_cache_dir_does_not_fail() {
		$this->paths->clear_cache_dir();
		// for the phpunit warning
		$this->assertTrue( true );
	}

	public function test_get_relative_dir_to_matomo_differentDirectoryInPlugin() {
		$plugin_dir  = plugin_dir_path( MATOMO_ANALYTICS_FILE );
		$dir_te_test = $plugin_dir . 'plugins/WordPress';

		$valid_values = array(
			'../../matomo/plugins/WordPress', // travis
		);
		$val          = $this->paths->get_relative_dir_to_matomo( $dir_te_test );
		$this->assertTrue( in_array( $val, $valid_values, true ) );
		// automatically double check that it works
		$this->assertTrue( is_dir( $plugin_dir . 'app/../plugins/WordPress' ) );
	}

	public function test_get_tmp_dir_createsDirectory() {
		$this->assertTrue( is_dir( $this->paths->get_tmp_dir() ) );
		$this->assertTrue( is_writable( $this->paths->get_tmp_dir() ) );
	}

	public function test_get_gloal_upload_dir_if_possible() {
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo', $this->paths->get_gloal_upload_dir_if_possible() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_gloal_upload_dir_if_possible_forBlog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}
		$blogid1 = self::factory()->blog->create();
		switch_to_blog( 2 );
		wp_delete_site( $blogid1 );
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo', $this->paths->get_gloal_upload_dir_if_possible() );
	}

}
