<?php
/**
 * @package matomo
 */

use Piwik\Access\Role\Admin;
use Piwik\Access\Role\View;
use Piwik\Access\Role\Write;
use WpMatomo\Access;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class TestMatomoCapabilities extends Capabilities {
	/**
	 * @param $cap_to_find
	 * @param $allcaps
	 *
	 * @return bool
	 */
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function has_any_higher_permission( $cap_to_find, $allcaps ) {
		return parent::has_any_higher_permission( $cap_to_find, $allcaps );
	}
}

class CapabilitiesTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var TestMatomoCapabilities
	 */
	private $caps;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings = new Settings();
		$this->caps     = $this->make_capabilities();
		$this->caps->register_hooks();
	}

	public function tearDown(): void {
		$this->caps->remove_hooks();
		parent::tearDown();
	}

	private function make_capabilities() {
		return new TestMatomoCapabilities( $this->settings );
	}

	public function test_get_all_capabilities_sorted_by_highest_permission() {
		$this->assertCount( 4, $this->caps->get_all_capabilities_sorted_by_highest_permission() );
	}

	/**
	 * @dataProvider get_any_higher_permission_provider
	 */
	public function test_has_any_higher_permission( $expected_result, $cap_to_find, $caps ) {
		$this->assertSame( $expected_result, $this->caps->has_any_higher_permission( $cap_to_find, $caps ) );
	}

	public function get_any_higher_permission_provider() {
		return array(
			array( true, Capabilities::KEY_VIEW, $this->make_all_caps( array( Capabilities::KEY_VIEW ) ) ),
			array( true, Capabilities::KEY_VIEW, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
			array( true, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
			array( true, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_SUPERUSER ) ) ),
			array( true, Capabilities::KEY_ADMIN, $this->make_all_caps( array( Capabilities::KEY_SUPERUSER ) ) ),
			array( false, Capabilities::KEY_SUPERUSER, $this->make_all_caps( array( Capabilities::KEY_ADMIN ) ) ),
			array( false, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_VIEW ) ) ),
			array( false, Capabilities::KEY_ADMIN, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
		);
	}

	/**
	 * @dataProvider get_test_data_for_network_enabled_test
	 */
	public function test_add_capabilities_to_user_and_add_capabilities_to_roles( $assume_network_enabled ) {
		$this->settings->set_assume_is_network_enabled_in_tests( $assume_network_enabled );

		$this->create_set_super_admin();

		$id1 = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id2 = self::factory()->user->create( array( 'role' => 'author' ) );
		$id3 = self::factory()->user->create( array( 'role' => 'contributor' ) );

		foreach ( array( $id1, $id2, $id3 ) as $user_id ) {
			$this->assertFalse( user_can( $user_id, Capabilities::KEY_ADMIN ) );
			$this->assertFalse( user_can( $user_id, Capabilities::KEY_WRITE ) );
			$this->assertFalse( user_can( $user_id, Capabilities::KEY_VIEW ) );
			$this->assertFalse( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
		}

		$access = new Access( $this->settings );
		$access->save(
			array(
				'editor'      => Capabilities::KEY_ADMIN,
				'author'      => Capabilities::KEY_WRITE,
				'contributor' => Capabilities::KEY_VIEW,
			)
		);

		$this->assertTrue( get_role( 'editor' )->has_cap( Capabilities::KEY_ADMIN ) );
		$this->assertTrue( get_role( 'author' )->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertTrue( get_role( 'contributor' )->has_cap( Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id1, Capabilities::KEY_SUPERUSER ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_ADMIN ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id2, Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( user_can( $id2, Capabilities::KEY_ADMIN ) );
		$this->assertTrue( user_can( $id2, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id2, Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id3, Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( user_can( $id3, Capabilities::KEY_ADMIN ) );
		$this->assertFalse( user_can( $id3, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id3, Capabilities::KEY_VIEW ) );
	}

	public function get_test_data_for_network_enabled_test() {
		return [
			[ true ],
			[ false ],
		];
	}

	public function test_get_role_ranking_should_rank_matomo_access_from_no_access_to_superuser() {
		// also guards the role IDs in Capabilities against drifting from Matomo's Role classes, as
		// they have to be spelled out literally there
		$this->assertSame( 0, Capabilities::get_role_ranking( null ) );
		$this->assertSame( 0, Capabilities::get_role_ranking( 'noaccess' ) );
		$this->assertSame( 1, Capabilities::get_role_ranking( View::ID ) );
		$this->assertSame( 2, Capabilities::get_role_ranking( Write::ID ) );
		$this->assertSame( 3, Capabilities::get_role_ranking( Admin::ID ) );
		$this->assertSame( 4, Capabilities::get_role_ranking( Capabilities::MATOMO_ROLE_SUPERUSER ) );
	}

	/**
	 * @dataProvider get_test_data_for_network_enabled_test
	 */
	public function test_get_highest_role_for_user_should_return_the_matomo_access_the_wp_capability_maps_to( $assume_network_enabled ) {
		$this->settings->set_assume_is_network_enabled_in_tests( $assume_network_enabled );

		$super_admin_id = $this->create_set_super_admin();

		$admin_id     = self::factory()->user->create( [ 'role' => 'editor' ] );
		$write_id     = self::factory()->user->create( [ 'role' => 'author' ] );
		$view_id      = self::factory()->user->create( [ 'role' => 'contributor' ] );
		$no_access_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$access = new Access( $this->settings );
		$access->save(
			[
				'editor'      => Capabilities::KEY_ADMIN,
				'author'      => Capabilities::KEY_WRITE,
				'contributor' => Capabilities::KEY_VIEW,
			]
		);

		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $super_admin_id ) );
		$this->assertSame( Admin::ID, Capabilities::get_highest_role_for_user( $admin_id ) );
		$this->assertSame( Write::ID, Capabilities::get_highest_role_for_user( $write_id ) );
		$this->assertSame( View::ID, Capabilities::get_highest_role_for_user( $view_id ) );
		$this->assertNull( Capabilities::get_highest_role_for_user( $no_access_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_add_capabilities_to_user_should_let_the_matomo_superuser_role_grant_superuser_access_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();

		( new Roles( $this->settings ) )->add_roles( true );

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );

		$this->assertFalse( is_super_admin( $user_id ) );

		$this->assertTrue( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $user_id ) );
	}

	public function test_add_capabilities_to_user_should_grant_superuser_capability_when_the_user_has_the_superuser_role() {
		( new Roles( $this->settings ) )->add_roles( true );

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );

		$this->assertTrue( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $user_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_add_capabilities_to_user_should_still_grant_superuser_access_to_a_super_admin_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->settings->set_assume_is_network_enabled_in_tests( true );

		$super_admin_id = $this->create_set_super_admin();

		$this->assertTrue( user_can( $super_admin_id, Capabilities::KEY_SUPERUSER ) );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $super_admin_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_add_capabilities_to_user_should_grant_superuser_access_to_a_wordpress_administrator_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();
		wp_roles()->init_roles();

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$this->assertFalse( is_super_admin( $user_id ) );

		// the blog they administrate has a Matomo of its own, and they are its super user
		$this->assertTrue( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
		$this->assertTrue( user_can( $user_id, Capabilities::KEY_ADMIN ) );
		$this->assertTrue( user_can( $user_id, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $user_id, Capabilities::KEY_VIEW ) );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $user_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_add_capabilities_to_user_should_not_grant_an_administrator_superuser_access_on_a_blog_they_do_not_administrate() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();
		wp_roles()->init_roles();

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $user_id ) );

		$other_blog = self::factory()->blog->create();

		switch_to_blog( $other_blog );
		try {
			$this->assertFalse( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
			$this->assertNull( Capabilities::get_highest_role_for_user( $user_id ) );
		} finally {
			restore_current_blog();
		}
	}

	public function test_add_capabilities_to_user_should_leave_an_administrator_the_super_user_when_the_network_is_not_enabled() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		$this->assertTrue( user_can( $user_id, Capabilities::KEY_SUPERUSER ) );
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::get_highest_role_for_user( $user_id ) );
	}

	public function test_matomo_role_superuser_should_be_reachable_under_its_deprecated_name() {
		$this->assertSame( Capabilities::MATOMO_ROLE_SUPERUSER, Capabilities::ROLE_SUPERUSER );
	}

	private function make_all_caps( $caps_to_set ) {
		$caps = array();
		foreach ( $this->make_capabilities()->get_all_capabilities_sorted_by_highest_permission() as $cap ) {
			$caps[ $cap ] = in_array( $cap, $caps_to_set, true );
		}

		return $caps;
	}
}
