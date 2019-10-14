<?php
/**
 * @package Matomo_Analytics
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

}
