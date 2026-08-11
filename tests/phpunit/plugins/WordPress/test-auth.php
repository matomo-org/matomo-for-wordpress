<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Access;
use Piwik\Access\Role\Admin;
use Piwik\Access\Role\View;
use Piwik\API\Request as ApiRequest;
use Piwik\AuthResult;
use Piwik\Container\StaticContainer;
use Piwik\NoAccessException;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\WordPress\Auth;
use Piwik\Request\AuthenticationToken;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Site;
use WpMatomo\User;

/**
 * @package matomo
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */
class WordPressAuthTest extends MatomoAnalytics_SharedFixture_TestCase {

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

	public function test_authenticate_should_reject_an_application_password_when_the_user_has_no_matomo_view_capability() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'staleuser', 'staleuser@example.com', 'staleuser' );

		// the View access a sync left behind after the WordPress side was revoked
		( new Model() )->addUserAccess( 'staleuser', View::ID, [ $this->get_current_idsite() ] );

		$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );

		// the credential is created only after the user lost their Matomo capability
		$password = $this->create_application_password( $wp_user_id );

		// no WordPress session: the application password is the only thing being presented
		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'staleuser';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$result = ( new Auth() )->authenticate();

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_authenticate_should_accept_an_application_password_when_the_user_has_matomo_view_capability() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'liveuser', 'liveuser@example.com', 'liveuser' );
		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );

		( new Model() )->addUserAccess( 'liveuser', View::ID, [ $this->get_current_idsite() ] );

		$this->assertTrue( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );

		$password = $this->create_application_password( $wp_user_id );

		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'liveuser';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$result = ( new Auth() )->authenticate();

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'liveuser', $result->getIdentity() );
	}

	public function test_authenticate_should_not_grant_superuser_access_when_only_the_persisted_matomo_row_says_so() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'staleadmin', 'staleadmin@example.com', 'staleadmin' );
		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );

		$model = new Model();
		$model->addUserAccess( 'staleadmin', View::ID, [ $this->get_current_idsite() ] );

		// the superuser flag a sync left behind after the WordPress side was downgraded
		$model->setSuperUserAccess( 'staleadmin', true );

		$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_SUPERUSER ) );

		$password = $this->create_application_password( $wp_user_id );

		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'staleadmin';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$result = ( new Auth() )->authenticate();

		// the user still has View, so they authenticate, but only at the level WordPress grants
		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertFalse( $result->hasSuperUserAccess() );
		$this->assertSame( AuthResult::SUCCESS, $result->getCode() );
	}

	public function test_authenticate_should_accept_an_application_password_when_the_capabilities_feature_is_not_registered() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'nocapsuser', 'nocapsuser@example.com', 'nocapsuser' );
		( new WP_User( $wp_user_id ) )->add_role( 'administrator' );

		( new Model() )->addUserAccess( 'nocapsuser', View::ID, [ $this->get_current_idsite() ] );

		$password = $this->create_application_password( $wp_user_id );

		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'nocapsuser';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$capabilities = WpMatomo::get_active_feature( Capabilities::class );
		$this->assertNotEmpty( $capabilities, 'Capabilities is expected to be registered by default' );

		$capabilities->remove_hooks();
		try {
			// without the user_has_cap filter an administrator has no matomo capability at all
			$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );

			$result = ( new Auth() )->authenticate();
		} finally {
			$capabilities->register_hooks();
		}

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'nocapsuser', $result->getIdentity() );
	}

	public function test_authenticate_should_revoke_access_that_outranks_the_wp_capability() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'downgraded', 'downgraded@example.com', 'downgraded' );
		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );

		$idsite = $this->get_current_idsite();

		// the admin access a sync left behind before the WordPress side was downgraded to view
		( new Model() )->addUserAccess( 'downgraded', Admin::ID, [ $idsite ] );

		$password = $this->create_application_password( $wp_user_id );

		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'downgraded';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$auth   = new Auth();
		$result = $auth->authenticate();

		$this->assertTrue( $result->wasAuthenticationSuccessful() );

		// matomo's authorisation layer reads the access table, so it must no longer see admin
		Access::getInstance()->reloadAccess( $auth );

		$admin_sites = array_map( 'intval', Access::getInstance()->getSitesIdWithAdminAccess() );
		$view_sites  = array_map( 'intval', Access::getInstance()->getSitesIdWithAtLeastViewAccess() );

		$this->assertNotContains( (int) $idsite, $admin_sites );
		$this->assertContains( (int) $idsite, $view_sites );

		$this->assertSame( View::ID, $this->get_access_for_idsite( 'downgraded', $idsite ) );
	}

	public function test_authenticate_should_not_revoke_access_when_the_capabilities_feature_is_not_registered() {
		$this->skip_if_old_wordpress();

		$wp_user_id = $this->create_mapped_user( 'safemodeuser', 'safemodeuser@example.com', 'safemodeuser' );
		( new WP_User( $wp_user_id ) )->add_role( 'administrator' );

		$idsite = $this->get_current_idsite();

		( new Model() )->addUserAccess( 'safemodeuser', Admin::ID, [ $idsite ] );

		$password = $this->create_application_password( $wp_user_id );

		wp_set_current_user( 0 );
		$_SERVER['PHP_AUTH_USER'] = 'safemodeuser';
		$_SERVER['PHP_AUTH_PW']   = $password;

		$capabilities = WpMatomo::get_active_feature( Capabilities::class );
		$this->assertNotEmpty( $capabilities, 'Capabilities is expected to be registered by default' );

		$capabilities->remove_hooks();
		try {
			$result = ( new Auth() )->authenticate();
		} finally {
			$capabilities->register_hooks();
		}

		// safe mode cannot resolve capabilities, so it must not conclude everyone lost their access
		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( Admin::ID, $this->get_access_for_idsite( 'safemodeuser', $idsite ) );
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

	private function create_application_password( $wp_user_id ) {
		add_filter( 'wp_is_application_passwords_available', '__return_true' );

		$created = WP_Application_Passwords::create_new_application_password(
			$wp_user_id,
			[ 'name' => 'regression test' ]
		);

		$this->assertNotWPError( $created );

		return $created[0];
	}

	private function get_current_idsite() {
		return ( new Site() )->get_current_matomo_site_id();
	}

	/**
	 * @param string $matomo_login
	 * @param int    $idsite
	 * @return string|null
	 */
	private function get_access_for_idsite( $matomo_login, $idsite ) {
		foreach ( ( new Model() )->getSitesAccessFromUser( $matomo_login ) as $access ) {
			if ( (int) $access['site'] === (int) $idsite ) {
				return $access['access'];
			}
		}

		return null;
	}
}
