<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\WordPress\Auth;
use WpMatomo\User;

/**
 * @package matomo
 */
class WordPressAuthTest extends MatomoAnalytics_TestCase {

	public function test_authenticate_succeeds_when_token_belongs_to_the_logged_in_user() {
		$wp_user_id = $this->create_mapped_user( 'testuser', 'testuser@example.com', 'testuser' );
		$token      = $this->create_token_for( 'testuser' );

		wp_set_current_user( $wp_user_id );

		$result = $this->authenticate_with_token( $token );

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'testuser', $result->getIdentity() );
	}

	public function test_authenticate_succeeds_when_matomo_login_differs_from_wp_username() {
		// WP user 'testuser2' is mapped to a renamed Matomo login 'wp_testuser2'. The token belongs to 'wp_testuser2',
		// so authentication must succeed even though it does not match the WP user_login.
		$wp_user_id = $this->create_mapped_user( 'testuser2', 'testuser2@example.com', 'wp_testuser2' );
		$token      = $this->create_token_for( 'wp_testuser2' );

		wp_set_current_user( $wp_user_id );

		$result = $this->authenticate_with_token( $token );

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'wp_testuser2', $result->getIdentity() );
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
