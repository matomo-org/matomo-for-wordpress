<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Access;
use Piwik\API\Request as ApiRequest;
use Piwik\Container\StaticContainer;
use Piwik\NoAccessException;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\WordPress\Auth;
use Piwik\Request\AuthenticationToken;
use WpMatomo\User;

/**
 * @package matomo
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */
class WordPressAuthTest extends MatomoAnalytics_TestCase {

	private $original_get;
	private $original_post;
	private $original_request;
	private $original_server;

	public function setUp(): void {
		parent::setUp();

		$this->original_get     = $_GET;
		$this->original_post    = $_POST;
		$this->original_request = $_REQUEST;
		$this->original_server  = $_SERVER;
	}

	public function tearDown(): void {
		$_GET     = $this->original_get;
		$_POST    = $this->original_post;
		$_REQUEST = $this->original_request;
		$_SERVER  = $this->original_server;

		parent::tearDown();
	}

	public function test_authenticate_rejects_a_token_even_when_it_belongs_to_the_logged_in_user() {
		// a matomo token_auth is not allowed to authenticate by itself
		$wp_user_id = $this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$token      = $this->create_token_for( 'testuser' );

		wp_set_current_user( $wp_user_id );

		$result = $this->authenticate_with_token( $token );

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_authenticate_rejects_a_token_when_matomo_login_differs_from_wp_username() {
		// WP user 'testuser2' is mapped to a renamed Matomo login 'wp_testuser2'; the token belongs to
		// 'wp_testuser2'. it must still be rejected: token_auth alone never authenticates.
		$wp_user_id = $this->create_mapped_user( 'testuser2', 'testuser2@example.com', 'wp_testuser2' );
		$token      = $this->create_token_for( 'wp_testuser2' );

		wp_set_current_user( $wp_user_id );

		$result = $this->authenticate_with_token( $token );

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_authenticate_rejects_a_token_belonging_to_a_different_user() {
		$testuser_id = $this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$this->create_mapped_user( 'testuser3', 'testuser3@example.com', 'testuser3' );
		$testuser3_token = $this->create_token_for( 'testuser3' );

		// logged in as testuser, but presenting testuser3's token_auth
		wp_set_current_user( $testuser_id );

		$result = $this->authenticate_with_token( $testuser3_token );

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_authenticate_is_anonymous_when_no_user_is_logged_in() {
		$this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$token = $this->create_token_for( 'testuser' );

		wp_set_current_user( 0 );

		$result = $this->authenticate_with_token( $token );

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
	}

	public function test_authenticate_rejects_a_token_even_when_force_api_session_is_requested() {
		// force_api_session is a caller controlled request parameter and must never re-enable token_auth
		// authentication
		$wp_user_id = $this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$token      = $this->create_token_for( 'testuser' );

		wp_set_current_user( $wp_user_id );

		$_GET['force_api_session']     = '1';
		$_POST['force_api_session']    = '1';
		$_REQUEST['force_api_session'] = '1';

		$result = $this->authenticate_with_token( $token );

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_api_request_with_token_auth_and_wp_session_is_not_authenticated() {
		$wp_user_id = $this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$token      = $this->create_token_for( 'testuser' );

		wp_set_current_user( $wp_user_id );

		// simulate the API request globals (token supplied as a POST parameter, force_api_session on).
		$_SERVER['REQUEST_METHOD']  = 'POST';
		$_GET['module']             = 'API';
		$_GET['method']             = 'SitesManager.getSitesIdWithAtLeastViewAccess';
		$_POST['token_auth']        = $token;
		$_POST['force_api_session'] = '1';

		// the token is detected once per request and cached, so give this simulated request a fresh detector.
		StaticContainer::getContainer()->set( AuthenticationToken::class, new AuthenticationToken() );

		// performs the same token based auth reload the FrontController / API dispatcher does.
		try {
			ApiRequest::reloadAuthUsingTokenAuth(
				[
					'token_auth'        => $token,
					'force_api_session' => '1',
					'module'            => 'API',
					'method'            => 'SitesManager.getSitesIdWithAtLeastViewAccess',
				]
			);
			$this->fail( 'expected NoAccessException to be thrown' );
		} catch ( NoAccessException $e ) {
			// ignore
		}

		$login = Access::getInstance()->getLogin();
		$this->assertNotSame( 'testuser', $login );
		$this->assertTrue( empty( $login ) || 'anonymous' === $login );
		$this->assertFalse( Access::getInstance()->hasSuperUserAccess() );
	}

	private function authenticate_with_token( $token ) {
		$auth = new Auth();
		$auth->setLogin( null );
		$auth->setPassword( null );
		$auth->setTokenAuth( $token );
		return $auth->authenticate();
	}

	private function create_mapped_user( $wp_login, $email, $matomo_login ) {
		// use a role the user sync ignores so it doesn't auto-create a Matomo user (which would
		// otherwise occupy the email and conflict with the user we create below; email is unique).
		$wp_user_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => $wp_login,
				'user_email' => $email,
			)
		);

		$model = new Model();

		// make sure no other Matomo user already holds this (unique) email.
		$existing = $model->getUserByEmail( $email );
		if ( ! empty( $existing['login'] ) && $existing['login'] !== $matomo_login ) {
			$model->deleteUserOnly( $existing['login'] );
		}

		if ( ! $model->getUser( $matomo_login ) ) {
			$model->addUser( $matomo_login, md5( 'pwd-' . $matomo_login ), $email, '2020-01-01 00:00:00' );
		}

		User::map_matomo_user_login( $wp_user_id, $matomo_login );

		return $wp_user_id;
	}

	private function create_token_for( $matomo_login ) {
		$model = new Model();
		$token = $model->generateRandomTokenAuth();
		$model->addTokenAuth( $matomo_login, $token, 'test token', '2020-01-01 00:00:00' );
		return $token;
	}
}
