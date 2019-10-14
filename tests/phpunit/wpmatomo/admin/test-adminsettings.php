<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Admin\AdminSettings;
use WpMatomo\Settings;

class AdminSettingsTest extends MatomoUnit_TestCase {

	/**
	 * @var AdminSettings
	 */
	private $admin_settings;

	public function setUp() {
		parent::setUp();

		$settings             = new Settings();
		$this->admin_settings = new AdminSettings( $settings );

		$this->assume_admin_page();
	}

	public function test_show_settings_renders_ui() {
		ob_start();
		$this->admin_settings->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'Tracking', $output );
		$this->assertContains( 'Access', $output );
	}

}
