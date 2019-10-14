<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Report\Data;

class ReportDataTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Data
	 */
	private $data;

	public function setUp() {
		parent::setUp();

		$this->data = new Data();
		$this->create_set_super_admin();
	}

	public function test_get_report_no_dimension() {
		$meta   = array( 'module' => 'VisitsSummary', 'action' => 'get', 'parameters' => array() );
		$report = $this->data->fetch_report( $meta, 'day', 'yesterday', 'nb_visits', '10' );

		$this->assertFalse( $report['reportData']->getFirstRow() );
	}

	public function test_get_report_no_dimension_with_parameters() {
		$meta   = array( 'module' => 'Goals', 'action' => 'get', 'parameters' => array( 'idGoal' => '0' ) );
		$report = $this->data->fetch_report( $meta, 'day', 'yesterday', 'nb_visits', '10' );

		$this->assertEquals( array(
			'nb_conversions'      => 0,
			'nb_visits_converted' => 0,
			'revenue'             => '$0',
			'conversion_rate'     => '0%'
		), $report['reportData']->getFirstRow()->getColumns() );
	}
}
