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

	public function setUp() {
		parent::setUp();

		$this->roles = $this->make_roles();
		$this->roles->add_roles();
	}

	private function make_roles() {
		return new Roles( new Settings() );
	}

	public function test_add_roles_by_default() {
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
		foreach ( $roles as $roleName => $options ) {
			$this->assertTrue( $this->roles->is_matomo_role( $roleName ) );
		}
		$this->assertFalse( $this->roles->is_matomo_role( 'administrator' ) );
		$this->assertFalse( $this->roles->is_matomo_role( 'editor' ) );
		$this->assertFalse( $this->roles->is_matomo_role( 'foobarnotexisting' ) );
	}

	public function test_get_available_roles_for_configuration() {
		$roles = $this->roles->get_available_roles_for_configuration();
		$this->assertSame(
			array(
				'editor'      => 'Editor',
				'author'      => 'Author',
				'contributor' => 'Contributor',
				'subscriber'  => 'Subscriber',
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
		$this->assertNotEmpty( get_role( Roles::ROLE_VIEW ) );
		$this->assertNotEmpty( get_role( Roles::ROLE_WRITE ) );
		$this->assertNotEmpty( get_role( Roles::ROLE_ADMIN ) );
		$this->assertNotEmpty( get_role( Roles::ROLE_SUPERUSER ) );
	}
}
