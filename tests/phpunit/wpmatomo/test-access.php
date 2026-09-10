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

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings     = new Settings();
		$this->capabilities = new Capabilities( $this->settings );
		$this->capabilities->register_hooks(); // access and capabilities need to share same settings instance otherwise tests won't work correctly

		$this->access = new Access( $this->settings );
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

	/**
	 * @group ms-required
	 */
	public function test_get_permission_for_role_should_not_give_the_administrator_role_a_permission_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->settings->set_assume_is_network_enabled_in_tests( true );

		// an administrator is the super user of the Matomo belonging to the blog they administrate,
		// which is not something these settings decide
		$this->assertNull( $this->access->get_permission_for_role( 'administrator' ) );
		$this->assertNull( $this->access->get_permission_for_role( 'editor' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_save_should_ignore_a_permission_submitted_for_the_administrator_role_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->settings->set_assume_is_network_enabled_in_tests( true );

		$this->access->save( array( 'administrator' => Capabilities::KEY_NONE ) );

		// the role is not offered for configuration, so a value posted for it is dropped rather
		// than quietly stored somewhere that nothing reads
		$this->assertNull( $this->access->get_permission_for_role( 'administrator' ) );
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
