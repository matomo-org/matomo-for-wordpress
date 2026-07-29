<?php
/**
 * @package matomo
 */

use WpMatomo\User;

class UserTest extends MatomoUnit_TestCase {

	/**
	 * @var User
	 */
	private $user;

	public function setUp(): void {
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
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}
		$user_id = $this->create_set_super_admin();
		wp_set_current_user( $user_id );

		$id1 = self::factory()->blog->create();

		User::map_matomo_user_login( get_current_user_id(), 'foo' );
		$this->assertSame( 'foo', $this->user->get_current_matomo_user_login() );

		// in different blog the user has no mapping
		switch_to_blog( $id1 );
		$this->assertFalse( $this->user->get_current_matomo_user_login() );

		// in original blog user has a mapping again
		restore_current_blog();
		$this->assertSame( 'foo', $this->user->get_current_matomo_user_login() );

		wp_delete_site( $id1 );
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

	public function test_get_wp_user_ids_for_matomo_login_returns_empty_array_when_login_is_empty() {
		$this->assertSame( [], $this->user->get_wp_user_ids_for_matomo_login( '' ) );
		$this->assertSame( [], $this->user->get_wp_user_ids_for_matomo_login( null ) );
	}

	public function test_get_wp_user_ids_for_matomo_login_returns_empty_array_when_no_user_is_mapped() {
		User::map_matomo_user_login( 5, 'someLogin' );

		$this->assertSame( [], $this->user->get_wp_user_ids_for_matomo_login( 'notMappedLogin' ) );
	}

	public function test_get_wp_user_ids_for_matomo_login_returns_the_single_mapped_user() {
		User::map_matomo_user_login( 5, 'myMatomoLogin' );

		$this->assertSame( [ 5 ], $this->user->get_wp_user_ids_for_matomo_login( 'myMatomoLogin' ) );
	}

	public function test_get_wp_user_ids_for_matomo_login_returns_all_users_sharing_the_login() {
		// unexpected state: two distinct WordPress users end up mapped to the same Matomo login
		User::map_matomo_user_login( 5, 'sharedLogin' );
		User::map_matomo_user_login( 6, 'sharedLogin' );
		User::map_matomo_user_login( 7, 'otherLogin' );

		$ids = $this->user->get_wp_user_ids_for_matomo_login( 'sharedLogin' );

		$this->assertEqualsCanonicalizing( [ 5, 6 ], $ids );
		foreach ( $ids as $id ) {
			$this->assertIsInt( $id );
		}
	}

	public function test_get_wp_user_ids_for_matomo_login_matches_the_login_exactly() {
		User::map_matomo_user_login( 5, 'login' );
		User::map_matomo_user_login( 6, 'login2' );

		$this->assertSame( [ 5 ], $this->user->get_wp_user_ids_for_matomo_login( 'login' ) );
	}

	public function test_delete_mappings_for_matomo_login_removes_every_mapping_pointing_to_the_given_login() {
		User::map_matomo_user_login( 5, 'sharedLogin' );
		User::map_matomo_user_login( 6, 'sharedLogin' );
		User::map_matomo_user_login( 7, 'otherLogin' );

		$this->user->delete_mappings_for_matomo_login( 'sharedLogin' );

		$this->assertFalse( User::get_matomo_user_login( 5 ) );
		$this->assertFalse( User::get_matomo_user_login( 6 ) );
		// unrelated mappings must be left untouched
		$this->assertSame( 'otherLogin', User::get_matomo_user_login( 7 ) );
		$this->assertSame( [], $this->user->get_wp_user_ids_for_matomo_login( 'sharedLogin' ) );
	}

	public function test_delete_mappings_for_matomo_login_does_nothing_when_login_is_empty() {
		User::map_matomo_user_login( 5, 'someLogin' );

		$this->user->delete_mappings_for_matomo_login( '' );
		$this->user->delete_mappings_for_matomo_login( null );

		$this->assertSame( 'someLogin', User::get_matomo_user_login( 5 ) );
	}

	public function test_delete_mappings_for_matomo_login_does_nothing_when_login_is_not_mapped() {
		User::map_matomo_user_login( 5, 'someLogin' );

		$this->user->delete_mappings_for_matomo_login( 'notMappedLogin' );

		$this->assertSame( 'someLogin', User::get_matomo_user_login( 5 ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_delete_mappings_for_matomo_login_removes_mappings_across_all_blogs_when_network_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// simulate a network activated plugin so is_network_enabled() returns true
		update_site_option( 'active_sitewide_plugins', array( 'matomo/matomo.php' => time() ) );

		$blog1 = self::factory()->blog->create();
		$blog2 = self::factory()->blog->create();

		User::map_matomo_user_login( 5, 'sharedLogin' );

		switch_to_blog( $blog1 );
		User::map_matomo_user_login( 5, 'sharedLogin' );
		User::map_matomo_user_login( 7, 'otherLogin' );
		restore_current_blog();

		switch_to_blog( $blog2 );
		User::map_matomo_user_login( 6, 'sharedLogin' );
		restore_current_blog();

		$this->user->delete_mappings_for_matomo_login( 'sharedLogin' );

		// the shared login mapping is removed on every blog...
		$this->assertFalse( User::get_matomo_user_login( 5 ) );

		switch_to_blog( $blog1 );
		$this->assertFalse( User::get_matomo_user_login( 5 ) );
		// ...while a mapping pointing at a different login on the same blog is left untouched
		$this->assertSame( 'otherLogin', User::get_matomo_user_login( 7 ) );
		restore_current_blog();

		switch_to_blog( $blog2 );
		$this->assertFalse( User::get_matomo_user_login( 6 ) );
		restore_current_blog();

		delete_site_option( 'active_sitewide_plugins' );
		wp_delete_site( $blog1 );
		wp_delete_site( $blog2 );
	}

	/**
	 * @group ms-required
	 */
	public function test_delete_mappings_for_matomo_login_only_touches_current_blog_when_not_network_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// make sure the plugin is not considered network activated
		delete_site_option( 'active_sitewide_plugins' );

		$blog1 = self::factory()->blog->create();

		// mapping on the current (main) blog
		User::map_matomo_user_login( 5, 'sharedLogin' );

		// same login string but on another blog => a different Matomo instance / user
		switch_to_blog( $blog1 );
		User::map_matomo_user_login( 6, 'sharedLogin' );
		restore_current_blog();

		$this->user->delete_mappings_for_matomo_login( 'sharedLogin' );

		// only the current blog's mapping is removed...
		$this->assertFalse( User::get_matomo_user_login( 5 ) );

		// ...the other blog's mapping is left untouched
		switch_to_blog( $blog1 );
		$this->assertSame( 'sharedLogin', User::get_matomo_user_login( 6 ) );
		restore_current_blog();

		wp_delete_site( $blog1 );
	}
}
