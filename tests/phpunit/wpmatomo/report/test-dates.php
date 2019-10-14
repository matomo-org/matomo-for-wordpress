<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Report\Dates;

class ReportDatesTest extends MatomoUnit_TestCase {

	/**
	 * @var Dates
	 */
	private $dates;

	public function setUp() {
		parent::setUp();

		$this->dates = new Dates();
	}

	public function test_get_supported_dates() {
		$this->assertNotEmpty( $this->dates->get_supported_dates() );
	}

	/**
	 * @dataProvider get_report_dates
	 */
	public function test_detect_period_and_date( $report_date, $expected_period, $expected_date ) {
		$this->assertEquals( array(
			$expected_period,
			$expected_date
		), $this->dates->detect_period_and_date( $report_date ) );
	}

	public function get_report_dates() {
		return array(
			array( 'foobar', 'day', 'yesterday' ), // default
			array( Dates::THIS_YEAR, 'year', 'today' ),
			array( Dates::THIS_MONTH, 'month', 'today' ),
			array( Dates::THIS_WEEK, 'week', 'today' ),
			array( Dates::TODAY, 'day', 'today' ),
			array( Dates::YESTERDAY, 'day', 'yesterday' ),
			array( '2015-05-06', 'day', '2015-05-06' ),
			array( '15-05-06', 'day', 'yesterday' ),
		);
	}
}
