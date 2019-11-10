<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Admin\GetStarted;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Admin\Info;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminGetStartedTest extends MatomoUnit_TestCase {

	/**
	 * @var GetStarted
	 */
	private $get_started;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings    = new Settings();
		$this->get_started = new GetStarted( $this->settings );

		$this->create_set_super_admin();
		$this->assume_admin_page();
	}

	public function tearDown() {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->get_started->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'About', $output );
	}

	public function test_show_does_change_value_if_nonce() {
		$this->assertSame( TrackingSettings::TRACK_MODE_DISABLED, $this->settings->get_global_option( 'track_mode' ) );

		$this->fake_request( TrackingSettings::TRACK_MODE_DEFAULT );

		ob_start();
		$this->get_started->show();
		ob_end_clean();

		$this->assertSame( TrackingSettings::TRACK_MODE_DEFAULT, $this->settings->get_global_option( 'track_mode' ) );
		// still show the get started page
		$this->assertNotEmpty( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) );
	}

	public function test_show_settings_does_not_change_any_values_when_not_correct_value() {

		$this->fake_request( 'manually' );

		ob_start();
		$this->get_started->show();
		ob_end_clean();

		$this->assertSame( TrackingSettings::TRACK_MODE_DISABLED, $this->settings->get_global_option( 'track_mode' ) );
	}

	public function test_show_settings_get_started_page() {

		$this->assertNotEmpty( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) );
		$this->fake_request( 'no', Settings::SHOW_GET_STARTED_PAGE );

		ob_start();
		$this->get_started->show();
		ob_end_clean();

		$this->assertEmpty( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) );
	}

	public function test_show_settings_get_started_page_when_not_correct_value() {

		$this->assertNotEmpty( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) );
		$this->fake_request( Settings::SHOW_GET_STARTED_PAGE, '' );

		ob_start();
		$this->get_started->show();
		ob_end_clean();

		$this->assertNotEmpty( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) );
	}

	private function fake_request( $track_mode_value, $post_key = 'track_mode' ) {
		$_POST[ GetStarted::FORM_NAME ] = array( $post_key => $track_mode_value );
		$_REQUEST['_wpnonce']     = wp_create_nonce( GetStarted::NONCE_NAME );
		$_SERVER['REQUEST_URI']   = home_url();
	}


}
