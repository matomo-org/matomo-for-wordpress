<?php
/**
 * @package matomo
 */

use \WpMatomo\API;

class ApiTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var WP_REST_Server
	 */
	private $server;

	public function setUp() {
		parent::setUp();

		$this->api    = new API();
		$this->server = rest_get_server();
	}

	public function test_adds_namespace() {
		$namespaces = $this->server->get_namespaces();
		$this->assertTrue( in_array( API::VERSION, $namespaces ) );
	}

	public function test_to_snake_case() {
		$this->assertEquals( 'api', $this->api->to_snake_case( 'api' ) );
		$this->assertEquals( 'get_matomo_version', $this->api->to_snake_case( 'getMatomoVersion' ) );
	}

	public function test_dispatch_matomo_api() {
		$this->create_set_super_admin();

		$request  = new WP_REST_Request( 'GET', '/' . API::VERSION . '/api/matomo_version' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertStringStartsWith( '3.', $response->get_data() );
		$this->assertTrue( strlen( $response->get_data() ) < 15 );
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
		$this->assertEquals(
			array(
				'code'    => 'rest_no_route',
				'message' => 'No route was found matching the URL and request method',
				'data'    => array( 'status' => 404 ),
			),
			$response->get_data()
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
				'code'    => 'matomo_error',
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
				'code'    => 'matomo_error',
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
				'code'    => 'matomo_error',
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
				'code'    => 'matomo_error',
				'message' => 'Please specify a value for \'idGoal\'.',
				'data'    => null,
			),
			$response->get_data()
		);
	}
}
