<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\PrivacySettings;

class AdminPrivacySettingsTest extends MatomoUnit_TestCase {

	/**
	 * @var PrivacySettings
	 */
	private $privacy_settings;

	public function setUp(): void {
		parent::setUp();

		$settings               = new \WpMatomo\Settings();
		$this->privacy_settings = new PrivacySettings( $settings );
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->privacy_settings->show_settings();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Let users opt-out of tracking', $output );
	}


}
