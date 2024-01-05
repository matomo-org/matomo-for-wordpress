<?php
/**
 * @package matomo
 */

/**
 * @phpcs:disable WordPress.PHP.IniSet.Risky
 */
class TrackingTest extends MatomoAnalytics_TestCase {
	private $application_password;

	private $user_login;

	public function setUp(): void {
		parent::setUp();

		$user_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user_id );
		$this->user_login = wp_get_current_user()->user_login;

		$this->assertNotEmpty( $this->user_login );

		$sync = new \WpMatomo\User\Sync();
		$sync->sync_all();

		$user_model = new \Piwik\Plugins\UsersManager\Model();
		$this->assertNotEmpty( $user_model->getUser( \WpMatomo\User::get_matomo_user_login( $user_id ) ) );

		// add application password
		// NOTE: we don't skip all the tests here to make sure the auth code
		// works when application password functions do not exist
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '>=' ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_true' );

			$request = new WP_REST_Request( 'POST', '/wp/v2/users/me/application-passwords' );
			$request->set_param( 'name', 'test' );
			$response = rest_get_server()->dispatch( $request );

			$response_data              = $response->get_data();
			$this->application_password = $response_data['password'];
		}
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
		$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->user_login );
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
		$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->user_login );
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
