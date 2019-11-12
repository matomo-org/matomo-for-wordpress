<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\User;

class UserTest extends MatomoUnit_TestCase {

	/**
	 * @var User
	 */
	private $user;

	public function setUp() {
		parent::setUp();

		$this->user = $this->make_user();
	}

	private function make_user() {
		return new User();
	}

	public function test_get_current_matomo_user_login_when_not_mapped() {
		$this->assertNull( $this->user->get_current_matomo_user_login() );
	}

	public function test_get_current_matomo_user_login_when_mapped() {
		$id1 = self::factory()->user->create();
		$id2 = self::factory()->user->create();

		wp_set_current_user( $id1 );
		User::map_matomo_user_login( get_current_user_id(), 'foo' );
		$this->assertSame( 'foo', $this->user->get_current_matomo_user_login() );

		// different user has still no mapping
		wp_set_current_user( $id2 );
		$this->assertFalse( $this->user->get_current_matomo_user_login() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_current_matomo_user_login_mapping_is_stored_per_blog() {
		$user_id = $this->create_set_super_admin();
		wp_set_current_user($user_id);

		$id1 = self::factory()->blog->create();
		
		User::map_matomo_user_login( get_current_user_id(), 'foo' );
		$this->assertSame( 'foo', $this->user->get_current_matomo_user_login() );

		// in different blog the user has no mapping
		switch_to_blog( $id1 );
		$this->assertFalse( $this->user->get_current_matomo_user_login() );

		// in original blog user has a mapping again
		restore_current_blog();
		$this->assertSame( 'foo', $this->user->get_current_matomo_user_login() );
	}

	public function test_map_matomo_user_login_get_matomo_user_login() {
		$this->assertSame( false, User::get_matomo_user_login( 5 ) );
		User::map_matomo_user_login( 5, 'myMatomoLogin' );
		$this->assertSame( 'myMatomoLogin', User::get_matomo_user_login( 5 ) );
	}

	public function test_map_matomo_user_login_can_overwrite_login() {
		User::map_matomo_user_login( 5, 'myMatomoLogin' );
		$this->assertSame( 'myMatomoLogin', User::get_matomo_user_login( 5 ) );

		User::map_matomo_user_login( 5, 'myMatomoLogin2' );
		$this->assertSame( 'myMatomoLogin2', User::get_matomo_user_login( 5 ) );
	}

	public function test_map_matomo_user_login_doesnt_fail_when_unsetting_not_mapped_user_login() {
		User::map_matomo_user_login( 9999, false );
		$this->assertFalse( User::get_matomo_user_login( 9999 ) );
	}

	public function test_map_matomo_user_login_deletes_set_login() {
		User::map_matomo_user_login( 5, 'myMatomoLogin' );
		User::map_matomo_user_login( 5, false );
		$this->assertFalse( User::get_matomo_user_login( 5 ) );
	}

	public function test_uninstall_removes_all_mappings() {
		User::map_matomo_user_login( 5, 'myMatomoLogin' );
		$this->assertNotEmpty( User::get_matomo_user_login( 5 ) );

		$this->user->uninstall();

		$this->assertFalse( User::get_matomo_user_login( 5 ) );
	}

}
