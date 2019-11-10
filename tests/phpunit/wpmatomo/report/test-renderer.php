<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Report\Dates;

class ReportRendererTest extends MatomoAnalytics_TestCase {

	protected $disable_temp_tables = true;

	public function setUp() {
		parent::setUp();

		$this->create_set_super_admin();
	}

	public function test_render_report_no_dimension_no_data() {
		$report = do_shortcode( '[matomo_report unique_id=VisitsSummary_get limit=15 report_date=' . Dates::YESTERDAY . ']' );
		$this->assertContains( 'There is no data for this report.', $report );
	}

	public function test_render_report_with_dimension_no_data() {
		$report = do_shortcode( '[matomo_report unique_id=Actions_getPageTitles limit=15 report_date=' . Dates::YESTERDAY . ']' );

		$this->assertContains( 'There is no data for this report.', $report );
	}

	public function test_render_report_no_dimension_with_data() {
		$local_tracker = $this->make_local_tracker( date( 'Y-m-d H:i:s' ) );
		$this->assert_tracking_response( $local_tracker->doTrackPageView( 'test' ) );

		$this->enable_browser_archiving();

		$report = do_shortcode( '[matomo_report unique_id=VisitsSummary_get limit=15 report_date=' . Dates::THIS_MONTH . ']' );
		$this->assertContains( '<td width="75%">Unique visitors</td><td width="25%">1</td></tr><tr><td width="75%">Visits</td><td width="25%">1</td></tr>', $report );
	}

	public function test_render_report_with_dimension_with_data() {
		$local_tracker = $this->make_local_tracker( date( 'Y-m-d H:i:s' ) );
		$this->assert_tracking_response( $local_tracker->doTrackPageView( 'test' ) );

		$this->enable_browser_archiving();

		$report = do_shortcode( '[matomo_report unique_id=Actions_getPageUrls limit=15 report_date=' . Dates::TODAY . ']' );
		$this->assertContains( '</td><td width="25%">1</td></tr>', $report );
		$this->assertContains( '<th width="75%">Page URL</th>', $report );
		$this->assertContains( '<th class="right">Pageviews</th>', $report );
	}

}
