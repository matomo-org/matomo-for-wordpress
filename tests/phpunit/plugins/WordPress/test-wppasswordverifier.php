<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\WordPress\WpPasswordVerifier;
use WpMatomo\User;

/**
 * @package matomo
 */
class WpPasswordVerifierTest extends MatomoAnalytics_TestCase {

	/**
	 * @var WpPasswordVerifier
	 */
	private $verifier;

	public function setUp(): void {
		parent::setUp();

		$this->verifier = new WpPasswordVerifier();
	}

	public function test_returns_true_for_correct_password_when_matomo_login_matches_wp_username() {
		$this->create_mapped_user( 'testuser', 'testuser@example.com', 'testpassword', 'testuser' );

		$this->assertTrue( $this->verifier->isPasswordCorrect( 'testuser', 'testpassword' ) );
	}

	public function test_returns_false_for_incorrect_password() {
		$this->create_mapped_user( 'testuser', 'testuser@example.com', 'testpassword', 'testuser' );

		$this->assertFalse( $this->verifier->isPasswordCorrect( 'testuser', 'wrong-password' ) );
	}

	public function test_returns_true_for_correct_password_when_matomo_login_differs_from_wp_username() {
		// the Matomo login ('wp_testuser') was sanitized/prefixed and differs from the WP user_login
		// ('testuser'); it must still be resolved (via the shared email) and the password verified.
		$this->create_mapped_user( 'testuser', 'testuser@example.com', 'secret-pass', 'wp_testuser' );

		$this->assertTrue( $this->verifier->isPasswordCorrect( 'wp_testuser', 'secret-pass' ) );
	}

	public function test_returns_false_for_incorrect_password_when_matomo_login_differs_from_wp_username() {
		$this->create_mapped_user( 'testuser', 'testuser@example.com', 'secret-pass', 'wp_testuser' );

		$this->assertFalse( $this->verifier->isPasswordCorrect( 'wp_testuser', 'wrong-password' ) );
	}

	public function test_returns_false_for_unknown_login() {
		$this->assertFalse( $this->verifier->isPasswordCorrect( 'this-login-does-not-exist', 'whatever' ) );
	}

	public function test_returns_false_for_empty_login() {
		$this->assertFalse( $this->verifier->isPasswordCorrect( '', 'whatever' ) );
	}

	/**
	 * Creates a WordPress user and a Matomo user (sharing the same email) mapped to the given Matomo
	 * login.
	 */
	private function create_mapped_user( $wp_login, $email, $password, $matomo_login ) {
		// use a role the user sync ignores so it doesn't auto-create a Matomo user (which would
		// otherwise occupy the email and conflict with the user we create below).
		$wp_user_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => $wp_login,
				'user_email' => $email,
				'user_pass'  => $password,
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
}
