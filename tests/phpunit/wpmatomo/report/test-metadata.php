<?php
/**
 * @package matomo
 */

use WpMatomo\Bootstrap;
use WpMatomo\Report\Metadata;

class ReportMetadataTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Metadata
	 */
	private $metadata;

	public function setUp() {
		parent::setUp();

		$this->metadata = new Metadata();
		$this->create_set_super_admin();
	}

	public function test_get_all_reports() {
		Bootstrap::set_not_bootstrapped();
		$reports = $this->metadata->get_all_reports();
		$this->assertNotEmpty( $reports['VisitsSummary_get'] );
		$this->assertSame( 'VisitsSummary_get', $reports['VisitsSummary_get']['uniqueId'] );
		$this->assertSame( 'VisitsSummary', $reports['VisitsSummary_get']['module'] );
		$this->assertSame( 'get', $reports['VisitsSummary_get']['action'] );
	}

	public function test_find_report_by_unique_id_when_uniqueid_not_exists() {
		$report = $this->metadata->find_report_by_unique_id( 'foobar' );
		$this->assertNull( $report );
	}

	public function test_find_report_by_unique_id() {
		$report = $this->metadata->find_report_by_unique_id( 'VisitsSummary_get' );
		$this->assertSame( 'VisitsSummary_get', $report['uniqueId'] );
		$this->assertSame( 'VisitsSummary', $report['module'] );
		$this->assertSame( 'get', $report['action'] );
	}

	public function test_get_all_report_pages() {
		$report_pages = $this->metadata->get_all_report_pages();
		$this->assertNotEmpty( $report_pages );
		$this->assertTrue( is_array( $report_pages ) );
		$this->assertNotEmpty( $report_pages[0]['uniqueId'] );
		$this->assertNotEmpty( $report_pages[0]['category']['id'] );
		$this->assertNotEmpty( $report_pages[0]['widgets'] );
		$this->assertNotEmpty( $report_pages[1]['uniqueId'] );
		$this->assertNotEmpty( $report_pages[1]['category']['id'] );
		$this->assertNotEmpty( $report_pages[1]['widgets'] );
	}

	public function test_find_report_page_params_by_report_metadata() {
		$report_page = $this->metadata->find_report_page_params_by_report_metadata(
			array(
				'module' => 'UserCountry',
				'action' => 'getCountry',
			)
		);
		$this->assertSame(
			array(
				'category'    => 'General_Visitors',
				'subcategory' => 'UserCountry_SubmenuLocations',
			),
			$report_page
		);
	}

	public function test_find_report_page_params_by_report_metadata_manually_found_through_unique_id() {
		$report_page = $this->metadata->find_report_page_params_by_report_metadata(
			array(
				'module'   => 'Actions',
				'action'   => 'get',
				'uniqueId' => 'Actions_get',
			)
		);
		$this->assertSame(
			array(
				'category'    => 'General_Visitors',
				'subcategory' => 'General_Overview',
			),
			$report_page
		);
	}

	public function test_find_report_page_params_by_report_metadata_when_no_report_found_returns_empty_array() {
		$report_page = $this->metadata->find_report_page_params_by_report_metadata(
			array(
				'module'   => 'Foo',
				'action'   => 'bar',
				'uniqueId' => 'Foo_bar',
			)
		);
		$this->assertSame( array(), $report_page );
	}

}
