<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Admin\SystemReport;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminSystemReportTest extends MatomoUnit_TestCase {

	/**
	 * @var SystemReport
	 */
	private $report;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();
		$this->settings = new Settings();
		$this->report   = new SystemReport( $this->settings );

		if ( is_multisite() ) {
			// the main difference in behavior is more like whether it is network enabled or not ...
			// and not so much if it is multisite or not
			$this->settings->set_assume_is_network_enabled_in_tests( true );
		}

		$this->assume_admin_page();
	}

	public function tearDown() {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->report->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'WordPress Plugins', $output );
	}

	/**
	 * @dataProvider get_trouble_shooting_data
	 */
	public function test_show_does_change_license_key_if_nonce_and_valid( $method ) {

		$this->fake_request( $method );

		$this->assertNull( $this->report->show() );
	}

	public function get_trouble_shooting_data() {
		if ( is_multisite() ) {
			return array(
				array( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES ),
				array( SystemReport::TROUBLESHOOT_SYNC_ALL_USERS ),
				array( SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ),
			);
		} else {
			return array(
				array( SystemReport::TROUBLESHOOT_SYNC_USERS ),
				array( SystemReport::TROUBLESHOOT_SYNC_SITE ),
				array( SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ),
			);
		}
	}

	private function fake_request( $field ) {
		$_POST[ $field ]        = 1;
		$_REQUEST['_wpnonce']   = wp_create_nonce( SystemReport::NONCE_NAME );
		$_SERVER['REQUEST_URI'] = home_url();
	}

}
