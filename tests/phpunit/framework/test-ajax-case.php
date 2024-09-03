<?php
/**
 * @package matomo
 */
abstract class MatomoUnit_Ajax_TestCase extends \WP_Ajax_UnitTestCase {
	/**
	 * @var MatomoUnit_WordPress_Fixture
	 */
	private $wordpress_fixture;

	public function setUp(): void {
		parent::setUp();
		$this->wordpress_fixture = new MatomoUnit_WordPress_Fixture();
		$this->wordpress_fixture->set_up();
	}

	public function tearDown(): void {
		$this->wordpress_fixture->tear_down();
		parent::tearDown();
	}

	/**
	 * Calls an WP AJAX method and returns the response as decoded JSON.
	 *
	 * @param string $ajax_method
	 * @param array  $get_params
	 * @param array  $post_params
	 * @return mixed
	 */
	protected function call_ajax( $ajax_method, $get_params = [], $post_params = [] ) {
		$_GET  = $get_params;
		$_POST = $post_params;

		try {
			$this->_handleAjax( $ajax_method );
			$this->fail( 'expected WPAjaxDieContinueException to be thrown in test' );
		} catch ( WPAjaxDieContinueException $e ) {
			// empty
		}

		$response = json_decode( $this->_last_response, true );
		return $response;
	}
}
