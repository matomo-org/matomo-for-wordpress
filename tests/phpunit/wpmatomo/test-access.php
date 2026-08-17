<?php
/**
 * @package matomo
 */

use Piwik\Plugins\UsersManager\Model;
use WpMatomo\Access;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\User;
use WpMatomo\User\Sync;

class AccessTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Access
	 */
	private $access;

	/**
	 * @var Capabilities
	 */
	private $capabilities;

	public function setUp(): void {
		parent::setUp();

		$settings           = new Settings();
		$this->capabilities = new Capabilities( $settings );
		$this->capabilities->register_hooks(); // access and capabilities need to share same settings instance otherwise tests won't work correctly

		$this->access = new Access( $settings );
	}

	public function tearDown(): void {
		$this->capabilities->remove_hooks();
		$this->wordpress_fixture->reset_roles();

		parent::tearDown();
	}

	public function test_get_permission_for_role_no_permission_saved_yet() {
		$this->assertNull( $this->access->get_permission_for_role( 'administrator' ) );
		$this->assertNull( $this->access->get_permission_for_role( 'editor' ) );
	}

	public function test_save_updates_roles() {
		$this->assertFalse( get_role( 'editor' )->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertFalse( get_role( 'author' )->has_cap( Capabilities::KEY_WRITE ) );

		$this->access->save(
			array(
				'editor' => Capabilities::KEY_WRITE,
			)
		);

		$this->assertTrue( get_role( 'editor' )->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertFalse( get_role( 'author' )->has_cap( Capabilities::KEY_WRITE ) );
	}

	public function test_save_get_permission_for_role() {
		$this->assertNull( $this->access->get_permission_for_role( 'editor' ) );

		$this->access->save( array( 'editor' => Capabilities::KEY_WRITE ) );

		$this->assertSame( Capabilities::KEY_WRITE, $this->access->get_permission_for_role( 'editor' ) );
	}

	public function test_save_does_not_set_permissions_for_not_supported_roles() {
		$this->assertNull( $this->access->get_permission_for_role( 'editor' ) );

		$this->access->save(
			array(
				'editor'          => Capabilities::KEY_WRITE,
				Roles::ROLE_ADMIN => Capabilities::KEY_VIEW,
				'author'          => Capabilities::KEY_VIEW,
				'foobar'          => Capabilities::KEY_ADMIN,
			)
		);

		$this->assertSame( Capabilities::KEY_WRITE, $this->access->get_permission_for_role( 'editor' ) );
		$this->assertSame( Capabilities::KEY_VIEW, $this->access->get_permission_for_role( 'author' ) );
		$this->assertNull( $this->access->get_permission_for_role( 'foobar' ) );
		$this->assertNull( $this->access->get_permission_for_role( Roles::ROLE_ADMIN ) );

		// double check didn't store it for any other roles
		$settings = new Settings();
		$access   = $settings->get_global_option( Settings::OPTION_KEY_CAPS_ACCESS );
		$this->assertSame(
			array(
				'editor' => Capabilities::KEY_WRITE,
				'author' => Capabilities::KEY_VIEW,
			),
			$access
		);
	}

	public function test_save_should_grant_access_that_the_capabilities_it_just_stored_allow() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );

		$this->access->save( array( 'editor' => Capabilities::KEY_VIEW ) );

		$login = User::get_matomo_user_login( $user_id );
		$this->assertNotEmpty( $login );
		$this->assertEquals( array( $this->get_current_site_id() ), $this->get_view_sites_for( $login ) );
	}

	public function test_save_should_revoke_access_that_the_capabilities_it_just_stored_no_longer_allow() {
		$user_id = self::factory()->user->create( array( 'role' => 'editor' ) );

		// the behaviour under test is save()'s own sync, so get to the starting state independently
		$this->access->save( array( 'editor' => Capabilities::KEY_VIEW ) );
		( new Sync() )->sync_current_users();

		$login = User::get_matomo_user_login( $user_id );
		$this->assertEquals( array( $this->get_current_site_id() ), $this->get_view_sites_for( $login ) );

		$this->access->save( array( 'editor' => Capabilities::KEY_NONE ) );

		$this->assertSame( array(), $this->get_view_sites_for( $login ) );
	}

	private function get_current_site_id() {
		return ( new Site() )->get_current_matomo_site_id();
	}

	/**
	 * @param string $login
	 * @return array
	 */
	private function get_view_sites_for( $login ) {
		$view_access = ( new Model() )->getUsersSitesFromAccess( 'view' );

		return isset( $view_access[ $login ] ) ? $view_access[ $login ] : array();
	}
}
