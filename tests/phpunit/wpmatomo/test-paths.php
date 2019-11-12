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

	public function setUp() {
		parent::setUp();

		$this->paths = $this->make_paths();
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
			'../plugins/WordPress', // locally
			'../../matomo/tests/phpunit/wpmatomo', // travis
		);

		$val = $this->paths->get_relative_dir_to_matomo( __DIR__ );
		$this->assertTrue( in_array( $val, $valid_values, true ) );
		// automatically double check that it works
		$this->assertTrue( is_dir( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/../tests/phpunit/wpmatomo' ) );
	}

	public function test_clear_assets_dir_does_not_fail() {
		$this->paths->clear_assets_dir();
	}

	public function test_clear_cache_dir_does_not_fail() {
		$this->paths->clear_cache_dir();
	}

	public function test_get_relative_dir_to_matomo_differentDirectoryInPlugin() {
		$plugin_dir  = plugin_dir_path( MATOMO_ANALYTICS_FILE );
		$dir_te_test = $plugin_dir . 'plugins/WordPress';

		$valid_values = array(
			'../plugins/WordPress', // locally
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
		$blogid1 = self::factory()->blog->create();
		switch_to_blog( 2 );
		wp_delete_site( $blogid1 );
		$this->assertSame( get_temp_dir() . 'wordpress/wp-content/uploads/matomo', $this->paths->get_gloal_upload_dir_if_possible() );
	}

}
