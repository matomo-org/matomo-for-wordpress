<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Access;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AccessTest extends MatomoUnit_TestCase {

	/**
	 * @var Access
	 */
	private $access;

	public function setUp() {
		parent::setUp();

		$this->access = $this->make_access();
	}

	private function make_access() {
		$settings = new Settings();
		$roles    = new Capabilities( $settings );
		$roles->register_hooks(); // access and capabilities need to share same settings instance otherwise tests won't work correctly

		return new Access( $settings );
	}

	public function test_get_permission_for_role_no_permission_saved_yet() {
		$this->assertNull( $this->access->get_permission_for_role( 'administrator' ) );
		$this->assertNull( $this->access->get_permission_for_role( 'editor' ) );
	}

	public function test_save_updates_roles() {
		$this->assertFalse( get_role( 'editor' )->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertFalse( get_role( 'author' )->has_cap( Capabilities::KEY_WRITE ) );

		$this->access->save( array(
			'editor' => Capabilities::KEY_WRITE,
		) );

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

		$this->access->save( array(
			'editor'          => Capabilities::KEY_WRITE,
			Roles::ROLE_ADMIN => Capabilities::KEY_VIEW,
			'author'          => Capabilities::KEY_VIEW,
			'foobar'          => Capabilities::KEY_ADMIN
		) );

		$this->assertSame( Capabilities::KEY_WRITE, $this->access->get_permission_for_role( 'editor' ) );
		$this->assertSame( Capabilities::KEY_VIEW, $this->access->get_permission_for_role( 'author' ) );
		$this->assertNull( $this->access->get_permission_for_role( 'foobar' ) );
		$this->assertNull( $this->access->get_permission_for_role( Roles::ROLE_ADMIN ) );

		// double check didn't store it for any other roles
		$settings = new Settings();
		$access   = $settings->get_global_option( Settings::OPTION_KEY_CAPS_ACCESS );
		$this->assertSame( array(
			'editor' => Capabilities::KEY_WRITE,
			'author' => Capabilities::KEY_VIEW
		), $access );
	}

}
