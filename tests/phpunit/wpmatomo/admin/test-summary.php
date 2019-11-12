<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Summary;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

class AdminSummaryTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Summary
	 */
	private $summary;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->assume_admin_page();
		$this->create_set_super_admin();

		$this->settings = new Settings();
		$this->summary  = new Summary( $this->settings );
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->summary->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'Summary', $output );
		$this->assertContains( 'Change date', $output );
		$this->assertContains( 'is not enabled', $output );
	}

	public function test_show_renders_ui_when_tracking_enabled() {
		$this->settings->apply_tracking_related_changes( array( 'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT ) );

		ob_start();
		$this->summary->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'Summary', $output );
		$this->assertContains( 'Change date', $output );
		$this->assertNotContains( 'is not enabled', $output );
	}

}
