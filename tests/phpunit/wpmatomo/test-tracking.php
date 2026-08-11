<?php
/**
 * @package matomo
 */

/**
 * @phpcs:disable WordPress.PHP.IniSet.Risky
 */
class TrackingTest extends MatomoAnalytics_SharedFixture_TestCase {
	public function setUp(): void {
		parent::setUp();

		$this->create_user_for_tracker();
	}

	public function tearDown(): void {
		wp_set_current_user( null );

		parent::tearDown();
	}

	public function test_cdt_requet_fails_when_no_token_auth_and_no_app_pwd() {
		$date_time_in_past = '2023-02-02 01:00:00';

		$tracker = $this->make_local_tracker( $date_time_in_past );
		$tracker->setUrl( 'http://test.com/page' );

		$error_log_before = ini_get( 'error_log' );
		ini_set( 'error_log', 'syslog' );
		try {
			self::assert_not_tracking_response( $tracker->doTrackPageView( 'page title' ) );
		} finally {
			ini_set( 'error_log', $error_log_before );
		}

		$visit_date = $this->get_latest_action_date();
		$this->assertEmpty( $visit_date );
	}

	public function test_cdt_request_fails_when_token_auth_and_no_app_pwd() {
		$date_time_in_past = '2023-02-02 01:00:00';

		$tracker = $this->make_local_tracker( $date_time_in_past );
		$tracker->setTokenAuth( 'testtesttest' );
		$tracker->setUrl( 'http://test.com/page' );

		$error_log_before = ini_get( 'error_log' );
		ini_set( 'error_log', 'syslog' );
		try {
			self::assert_not_tracking_response( $tracker->doTrackPageView( 'page title' ) );
		} finally {
			ini_set( 'error_log', $error_log_before );
		}

		$visit_date = $this->get_latest_action_date();
		$this->assertEmpty( $visit_date );
	}

	public function test_cdt_ignored_when_no_token_auth_and_app_pwd() {
		$date_time_in_past = '2023-02-02 01:00:00';

		$tracker = $this->make_local_tracker( $date_time_in_past );
		$tracker->setUrl( 'http://test.com/page' );
		$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->tracker_user );
		$tracker->setExtraServerVar( 'PHP_AUTH_PW', $this->application_password );

		$error_log_before = ini_get( 'error_log' );
		ini_set( 'error_log', 'syslog' );
		try {
			self::assert_not_tracking_response( $tracker->doTrackPageView( 'page title' ) );
		} finally {
			ini_set( 'error_log', $error_log_before );
		}

		$visit_date = $this->get_latest_action_date();
		$this->assertEmpty( $visit_date );
	}

	public function test_cdt_used_when_valid_app_pwd_supplied_with_token_auth() {
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '<' ) ) {
			$this->markTestSkipped( 'WordPress version does not support application passwords.' );
		}

		$date_time_in_past = '2023-02-02 01:00:00';

		$tracker = $this->make_local_tracker( $date_time_in_past );
		$tracker->setUrl( 'http://test.com/page' );
		$tracker->setTokenAuth( 'testtesttest' ); // ignored
		$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->tracker_user );
		$tracker->setExtraServerVar( 'PHP_AUTH_PW', $this->application_password );
		self::assert_tracking_response( $tracker->doTrackPageView( 'page title' ) );

		$visit_date = $this->get_latest_action_date();
		$this->assertEquals( $date_time_in_past, $visit_date );
	}

	private function get_latest_action_date() {
		return \Piwik\Db::fetchOne(
			'SELECT server_time FROM '
			. \Piwik\Common::prefixTable( 'log_link_visit_action' )
			. ' ORDER BY idlink_va DESC LIMIT 1'
		);
	}
}
