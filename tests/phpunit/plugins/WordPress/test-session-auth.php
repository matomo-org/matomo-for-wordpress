<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Access\Role\Admin;
use Piwik\Access\Role\View;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\WordPress\SessionAuth;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Site;
use WpMatomo\User;
use WpMatomo\User\Sync;

/**
 * @package matomo
 */
class WordPressSessionAuthTest extends MatomoAnalytics_SharedFixture_TestCase {

	public function test_authenticate_should_revoke_access_that_outranks_the_wp_capability() {
		$wp_user_id = $this->create_view_user();
		$login      = $this->sync_and_get_login( $wp_user_id );
		$idsite     = $this->get_current_idsite();

		// the admin access a sync left behind before the WordPress side was downgraded to view
		$this->set_access_for_idsite( $login, Admin::ID, $idsite );

		wp_set_current_user( $wp_user_id );

		$result = ( new SessionAuth() )->authenticate();

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( $login, $result->getIdentity() );
		$this->assertSame( View::ID, $this->get_access_for_idsite( $login, $idsite ) );
	}

	public function test_authenticate_should_leave_access_that_matches_the_wp_capability() {
		$wp_user_id = $this->create_view_user();
		$login      = $this->sync_and_get_login( $wp_user_id );
		$idsite     = $this->get_current_idsite();

		$this->assertSame( View::ID, $this->get_access_for_idsite( $login, $idsite ) );

		wp_set_current_user( $wp_user_id );

		$result = ( new SessionAuth() )->authenticate();

		$this->assertTrue( $result->wasAuthenticationSuccessful() );
		$this->assertSame( View::ID, $this->get_access_for_idsite( $login, $idsite ) );
	}

	public function test_authenticate_should_be_anonymous_when_no_user_is_logged_in() {
		wp_set_current_user( 0 );

		$result = ( new SessionAuth() )->authenticate();

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	public function test_authenticate_should_reject_a_user_without_the_matomo_view_capability() {
		$wp_user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );

		wp_set_current_user( $wp_user_id );

		$result = ( new SessionAuth() )->authenticate();

		$this->assertFalse( $result->wasAuthenticationSuccessful() );
		$this->assertSame( 'anonymous', $result->getIdentity() );
	}

	/**
	 * @return int
	 */
	private function create_view_user() {
		$wp_user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );

		$this->assertTrue( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );
		$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_ADMIN ) );

		return $wp_user_id;
	}

	/**
	 * @param int $wp_user_id
	 * @return string
	 */
	private function sync_and_get_login( $wp_user_id ) {
		( new Sync() )->sync_current_users();

		$login = User::get_matomo_user_login( $wp_user_id );
		$this->assertNotEmpty( $login );

		return $login;
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

	/**
	 * @param string $matomo_login
	 * @param string $role
	 * @param int    $idsite
	 */
	private function set_access_for_idsite( $matomo_login, $role, $idsite ) {
		$model = new Model();

		$model->deleteUserAccess( $matomo_login, [ $idsite ] );
		$model->addUserAccess( $matomo_login, $role, [ $idsite ] );

		$this->assertSame( $role, $this->get_access_for_idsite( $matomo_login, $idsite ) );
	}
}
