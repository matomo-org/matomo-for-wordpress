<?php
/**
 * @package matomo
 */

use Piwik\API\Request as MatomoApiRequest;
use Piwik\Plugins\Live\Settings\VisitorLogDisabled;
use WpMatomo\API;
use WpMatomo\Roles;
use WpMatomo\Site;
use WpMatomo\User\Sync;

class ApiTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var WP_REST_Server
	 */
	private $server;

	public function setUp(): void {
		parent::setUp();

		$this->api    = new API();
		$this->server = rest_get_server();
	}

	public function test_adds_namespace() {
		$namespaces = $this->server->get_namespaces();
		$this->assertTrue( in_array( API::VERSION, $namespaces, true ) );
	}

	public function test_to_snake_case() {
		$this->assertEquals( 'api', $this->api->to_snake_case( 'api' ) );
		$this->assertEquals( 'get_matomo_version', $this->api->to_snake_case( 'getMatomoVersion' ) );
	}

	public function test_dispatch_matomo_api() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertStringStartsWith( '5.', $response->get_data() );
		$this->assertTrue( strlen( $response->get_data() ) < 15 );
	}

	public function test_dispatch_uses_the_registered_route_method_when_request_params_conflict() {
		$this->create_set_super_admin();

		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$request->set_query_params( [
			'module' => 'API',
			'method' => 'API.getSuggestedValuesForSegment',
		] );
		$request->set_body_params( [
			'module' => 'CoreAdminHome',
			'method' => 'API.getBulkRequest',
			'urls'   => [ 'method=Live.getLastVisitsDetails&idSite=1&period=day&date=today' ],
		] );

		$original_get  = $_GET;
		$original_post = $_POST;
		$_GET          = [ 'module' => 'API', 'method' => 'API.getSuggestedValuesForSegment' ];
		$_POST         = [ 'module' => 'CoreAdminHome', 'method' => 'API.getBulkRequest' ];
		MatomoApiRequest::setIsRootRequestApiRequest( $_GET['method'] );

		try {
			$response = $this->server->dispatch( $request );
			$this->assertStringStartsWith( '5.', $response->get_data() );
			$this->assertSame( 'API.getMatomoVersion', MatomoApiRequest::getRootApiRequestMethod() );
		} finally {
			$_GET  = $original_get;
			$_POST = $original_post;
		}
	}

	public function test_view_user_cannot_override_a_rest_route_to_run_archive_reports() {
		$wp_user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );
		( new Sync() )->sync_current_users();
		wp_set_current_user( $wp_user_id );

		$id_site = ( new Site() )->get_current_matomo_site_id();
		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$request->set_query_params( [
			'module' => 'API',
			'method' => 'API.getSuggestedValuesForSegment',
		] );
		$request->set_body_params( [
			'module'   => 'CoreAdminHome',
			'method'   => 'CoreAdminHome.archiveReports',
			'idSite'   => $id_site,
			'period'   => 'day',
			'date'     => 'today',
		] );

		MatomoApiRequest::setIsRootRequestApiRequest( 'API.getSuggestedValuesForSegment' );
		$response = $this->server->dispatch( $request );

		$this->assertStringStartsWith( '5.', $response->get_data() );
	}

	public function test_disabled_visitor_log_cannot_be_bypassed_with_conflicting_bulk_route_params() {
		$this->create_set_super_admin();
		$id_site = ( new Site() )->get_current_matomo_site_id();
		$setting = VisitorLogDisabled::getMeasurableSetting( $id_site );
		$setting->setValue( true );
		$setting->save();

		$tracker = $this->make_local_tracker( '' );
		$tracker->setUrl( 'http://whatever.com/a/page' );
		$this->assert_tracking_response( $tracker->doTrackPageView( 'a page view' ) );

		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$request->set_query_params( [
			'module' => 'API',
			'method' => 'API.getSuggestedValuesForSegment',
		] );
		$request->set_body_params( [
			'module' => 'API',
			'method' => 'API.getBulkRequest',
			'urls'   => [ 'method=Live.getLastVisitsDetails&idSite=' . $id_site . '&period=day&date=today' ],
		] );

		MatomoApiRequest::setIsRootRequestApiRequest( 'API.getSuggestedValuesForSegment' );
		$response = $this->server->dispatch( $request );

		$this->assertStringStartsWith( '5.', $response->get_data() );
	}

	public function test_dispatch_matomo_api_with_json_response() {
		$this->create_set_super_admin();

		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$request->set_param( 'format', 'json' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertStringStartsWith( '5.', $response->get_data() );
		// check that the string is not serialized json
		$this->assertNull( json_decode( $response->get_data() ) );
	}

	public function test_dispatch_matomo_api_with_json_response_when_array() {
		$this->create_set_super_admin();

		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/widget_metadata' );
		$request->set_param( 'format', 'json' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertIsArray( $response->get_data() );
	}

	public function test_dispatch_matomo_api_response_when_datatable() {
		$this->create_set_super_admin();

		$tracker = $this->make_local_tracker( '' );
		$tracker->setUrl( 'http://whatever.com/a/page' );
		$this->assert_tracking_response( $tracker->doTrackPageView( 'a page view' ) );

		$request = new WP_REST_Request( 'GET', '/' . API::VERSION . '/live/last_visits_details' );
		$request->set_param( 'format', 'json' );
		$request->set_param( 'period', 'month' );
		$request->set_param( 'date', 'today' );
		$request->set_param( 'idSite', '1' );

		$response = rest_get_server()->dispatch( $request );
		$this->assertIsArray( $response->get_data() );
		$this->assertCount( 1, $response->get_data() );
	}

	public function test_dispatch_matomo_api_when_not_authenticated() {
		$request  = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals(
			array(
				'code'    => 'matomo_no_access_exception',
				'message' => 'You must be logged in to access this functionality.',
				'data'    => null,
			),
			$response->get_data()
		);
	}

	public function test_dispatch_matomo_api_must_use_correct_method() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'POST', '/' . API::VERSION . '/api/matomo_version' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();
		// some newer wp versions have a dot at the end
		$data['message'] = trim( $data['message'], '.' );
		$this->assertEquals(
			array(
				'code'    => 'rest_no_route',
				'message' => 'No route was found matching the URL and request method',
				'data'    => array( 'status' => 404 ),
			),
			$data
		);
	}

	public function test_dispatch_matomo_api_uses_post_method_when_needed_and_keeps_some_prefixes_in_route() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'POST', '/' . API::VERSION . '/core_admin_home/run_scheduled_tasks' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( array(), $response->get_data() );
	}

	public function test_dispatch_matomo_api_keeps_prefix_when_otherwise_empty() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'POST', '/' . API::VERSION . '/annotations/add' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals(
			array(
				'code'    => 'matomo_http_bad_request_exception',
				'message' => 'Please specify a value for \'date\'.',
				'data'    => null,
			),
			$response->get_data()
		);
	}

	public function test_dispatch_matomo_api_removes_add_prefix_and_detects_post() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'POST', '/' . API::VERSION . '/goals/goal' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals(
			array(
				'code'    => 'matomo_http_bad_request_exception',
				'message' => 'Please specify a value for \'name\'.',
				'data'    => null,
			),
			$response->get_data()
		);
	}

	public function test_dispatch_matomo_api_removes_update_prefix_and_detects_put() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'PUT', '/' . API::VERSION . '/goals/goal' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals(
			array(
				'code'    => 'matomo_http_bad_request_exception',
				'message' => 'Please specify a value for \'idGoal\'.',
				'data'    => null,
			),
			$response->get_data()
		);
	}

	public function test_dispatch_matomo_api_removes_delete_prefix_and_detects_delete() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'DELETE', '/' . API::VERSION . '/goals/goal' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals(
			array(
				'code'    => 'matomo_http_bad_request_exception',
				'message' => 'Please specify a value for \'idGoal\'.',
				'data'    => null,
			),
			$response->get_data()
		);
	}
}
