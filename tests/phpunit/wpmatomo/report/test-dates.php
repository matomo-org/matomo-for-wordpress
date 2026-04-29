<?php
/**
 * @package matomo
 */

use WpMatomo\Report\Dates;
use WpMatomo\User;

class ReportDatesTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Dates
	 */
	private $dates;

	private $user_id;

	public function setUp(): void {
		parent::setUp();

		$this->dates   = new Dates();
		$this->user_id = $this->create_set_super_admin();
	}

	public function test_get_supported_dates() {
		$this->assertNotEmpty( $this->dates->get_supported_dates() );
	}

	/**
	 * @dataProvider get_report_dates
	 */
	public function test_detect_period_and_date( $report_date, $expected_period, $expected_date ) {
		$this->assertEquals(
			array(
				$expected_period,
				$expected_date,
			),
			$this->dates->detect_period_and_date( $report_date )
		);
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

	/**
	 * @dataProvider get_test_data_for_get_date_from_query
	 */
	public function test_get_date_from_query( $query, $default_date, $expected ) {
		$_REQUEST = $query;

		wp_set_current_user( $this->user_id );
		\Piwik\Access::getInstance()->reloadAccess( new \Piwik\Plugins\WordPress\SessionAuth() );

		$login = \Piwik\Access::getInstance()->getLogin();
		$this->assertNotEmpty( $login );

		if ( $default_date ) {
			$api = \Piwik\Plugins\UsersManager\API::getInstance();
			$api->setUserPreference( $login, \Piwik\Plugins\UsersManager\API::PREFERENCE_DEFAULT_REPORT_DATE, $default_date );
		}

		$actual = $this->dates->get_date_from_query();
		$this->assertEquals( $expected, $actual );
	}

	public function get_test_data_for_get_date_from_query() {
		return [
			// no query, no default date
			[
				[],
				null,
				'yesterday',
			],
			// no query, default dates
			[
				[],
				'today',
				'today',
			],
			[
				[],
				'yesterday',
				'yesterday',
			],
			[
				[],
				'last7',
				'thisweek',
			],
			[
				[],
				'previous7',
				'lastweek',
			],
			[
				[],
				'last30',
				'thismonth',
			],
			[
				[],
				'previous30',
				'lastmonth',
			],
			[
				[],
				'last5',
				'yesterday',
			],
			[
				[],
				'previous5',
				'yesterday',
			],
			// no query, garbage default date
			[
				[],
				'asdfasdfsadf',
				'yesterday',
			],
			// garbage query, default date
			[
				[ 'report_date' => 'asdfklj' ],
				'previous30',
				'lastmonth',
			],
			[
				[ 'report_date' => 'aaaaaa' ],
				'previous5',
				'yesterday',
			],

			// query, no default date
			[
				[ 'report_date' => 'lastweek' ],
				null,
				'lastweek',
			],
			// query, default dates
			[
				[ 'report_date' => 'lastmonth' ],
				'today',
				'lastmonth',
			],
			[
				[ 'report_date' => 'thisweek' ],
				'yesterday',
				'thisweek',
			],
			[
				[ 'report_date' => 'thismonth' ],
				'last7',
				'thismonth',
			],
			[
				[ 'report_date' => 'today' ],
				'previous7',
				'today',
			],
			[
				[ 'report_date' => 'yesterday' ],
				'last30',
				'yesterday',
			],
			[
				[ 'report_date' => 'lastweek' ],
				'previous30',
				'lastweek',
			],
			[
				[ 'report_date' => 'lastmonth' ],
				'last5',
				'lastmonth',
			],
			[
				[ 'report_date' => 'thisweek' ],
				'previous5',
				'thisweek',
			],
			// query, garbage default date
			[
				[ 'report_date' => 'thisweek' ],
				'asdfdsfasdf',
				'thisweek',
			],
		];
	}
}
