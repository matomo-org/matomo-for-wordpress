<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Summary;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;
use WpMatomo\Report\Dates;
use WpMatomo\Report\Renderer;

class AdminSummaryTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Summary
	 */
	private $summary;

	/**
	 * @var Settings
	 */
	private $settings;

	protected $disable_temp_tables = true;

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

	public function test_show_pin_widget() {
		$dashboard = new \WpMatomo\Admin\Dashboard();
		$this->assertSame( array(), $dashboard->get_widgets() );

		$_GET                   = array(
			'pin'             => '1',
			'report_date'     => Dates::YESTERDAY,
			'report_uniqueid' => Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME,
		);
		$_REQUEST['_wpnonce']   = wp_create_nonce( Summary::NONCE_DASHBOARD );
		$_SERVER['REQUEST_URI'] = home_url();

		ob_start();
		$this->summary->show();
		$output = ob_get_clean();

		$this->assertSame(
			array(
				array(
					'unique_id' => 'visits_over_time',
					'date'      => 'yesterday',
				),
			),
			$dashboard->get_widgets()
		);

		$this->assertContains( 'Dashboard updated.', $output );
	}

	public function test_show_wont_pin_widget_when_invalid_report() {
		$dashboard = new \WpMatomo\Admin\Dashboard();
		$this->assertSame( array(), $dashboard->get_widgets() );

		$_GET                   = array(
			'pin'             => '1',
			'report_date'     => 'foo',
			'report_uniqueid' => Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME,
		);
		$_REQUEST['_wpnonce']   = wp_create_nonce( Summary::NONCE_DASHBOARD );
		$_SERVER['REQUEST_URI'] = home_url();

		ob_start();
		$this->summary->show();
		$output = ob_get_clean();

		$this->assertSame( array(), $dashboard->get_widgets() );

		$this->assertNotContains( 'Dashboard updated.', $output );
	}
}
