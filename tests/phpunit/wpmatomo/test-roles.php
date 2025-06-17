<?php
/**
 * @package matomo
 */

use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class RolesTest extends MatomoUnit_TestCase {

	/**
	 * @var Roles
	 */
	private $roles;

	public function setUp(): void {
		parent::setUp();

		$this->roles = $this->make_roles();
		$this->roles->uninstall();
		$this->roles->add_roles();
	}

	private function make_roles() {
		return new Roles( new Settings() );
	}

	public function test_add_roles_by_default() {
		$this->assertHasMatomoRoles();
	}

	public function test_on_update_adds_any_missing_capabilities() {
		$this->assertHasMatomoRoles();

		$wp_roles = wp_roles();
		$wp_roles->remove_cap( Roles::ROLE_VIEW, 'read' );
		$wp_roles->add_cap( Roles::ROLE_WRITE, 'read', false );
		$wp_roles->remove_cap( Roles::ROLE_ADMIN, 'view_admin_dashboard' );
		$wp_roles->add_cap( Roles::ROLE_SUPERUSER, 'view_admin_dashboard', false );

		// sanity check
		try {
			$this->assertHasMatomoRoles();
			$this->fail( 'test did not succeed in removing capabilities' );
		} catch ( \Exception $ex ) {
			// ignore
		}

		$this->roles->on_update();

		$this->assertHasMatomoRoles();
	}

	public function test_on_update_installs_roles_if_they_do_not_exist() {
		$this->assertHasMatomoRoles();

		$this->roles->uninstall();

		$this->assertNotHasMatomoRoles();

		$this->roles->on_update();

		$this->assertHasMatomoRoles();
	}

	public function test_uninstall() {
		$this->assertHasMatomoRoles();

		$this->roles->uninstall();
		$this->assertNotHasMatomoRoles();

		// we can add roles again after it was uninstalled
		$this->roles->add_roles();
		$this->assertHasMatomoRoles();
	}

	public function test_is_matomo_role() {
		$roles = $this->roles->get_matomo_roles();
		foreach ( $roles as $role_name => $options ) {
			$this->assertTrue( $this->roles->is_matomo_role( $role_name ) );
		}
		$this->assertFalse( $this->roles->is_matomo_role( 'administrator' ) );
		$this->assertFalse( $this->roles->is_matomo_role( 'editor' ) );
		$this->assertFalse( $this->roles->is_matomo_role( 'foobarnotexisting' ) );
	}

	public function test_get_available_roles_for_configuration() {
		$roles = $this->roles->get_available_roles_for_configuration();
		$this->assertSame(
			array(
				'editor'       => 'Editor',
				'author'       => 'Author',
				'contributor'  => 'Contributor',
				'subscriber'   => 'Subscriber',
				'customer'     => 'Customer',
				'shop_manager' => 'Shop manager',
			),
			$roles
		);
	}

	public function test_role_capability() {
		global $wp_roles;
		$role = get_role( Roles::ROLE_WRITE );
		$this->assertTrue( $role->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertFalse( $role->has_cap( Capabilities::KEY_VIEW ) );
		$this->assertFalse( $role->has_cap( Capabilities::KEY_ADMIN ) );

		$role = get_role( Roles::ROLE_VIEW );
		$this->assertTrue( $role->has_cap( Capabilities::KEY_VIEW ) );
		$this->assertFalse( $role->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertFalse( $role->has_cap( Capabilities::KEY_ADMIN ) );
	}

	public function test_role_name() {
		global $wp_roles;
		$names = $wp_roles->role_names;
		$this->assertSame( 'Matomo Write', $names[ Roles::ROLE_WRITE ] );
		$this->assertSame( 'Matomo View', $names[ Roles::ROLE_VIEW ] );
	}

	private function assertNotHasMatomoRoles() {
		$this->assertNull( get_role( Roles::ROLE_VIEW ) );
		$this->assertNull( get_role( Roles::ROLE_WRITE ) );
		$this->assertNull( get_role( Roles::ROLE_ADMIN ) );
		$this->assertNull( get_role( Roles::ROLE_SUPERUSER ) );
	}

	private function assertHasMatomoRoles() {
		$role = get_role( Roles::ROLE_VIEW );
		$this->assertNotEmpty( $role );
		$this->assertEquals(
			[
				Capabilities::KEY_VIEW => true,
				'read'                 => true,
				'view_admin_dashboard' => true,
			],
			$role->capabilities
		);

		$role = get_role( Roles::ROLE_WRITE );
		$this->assertNotEmpty( $role );
		$this->assertEquals(
			[
				Capabilities::KEY_WRITE => true,
				'read'                  => true,
				'view_admin_dashboard'  => true,
			],
			$role->capabilities
		);

		$role = get_role( Roles::ROLE_ADMIN );
		$this->assertNotEmpty( $role );
		$this->assertEquals(
			[
				Capabilities::KEY_ADMIN => true,
				'read'                  => true,
				'view_admin_dashboard'  => true,
			],
			$role->capabilities
		);

		$role = get_role( Roles::ROLE_SUPERUSER );
		$this->assertNotEmpty( $role );
		$this->assertEquals(
			[
				Capabilities::KEY_SUPERUSER => true,
				'read'                      => true,
				'view_admin_dashboard'      => true,
			],
			$role->capabilities
		);
	}
}
