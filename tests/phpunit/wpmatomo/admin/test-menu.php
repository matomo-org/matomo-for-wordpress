<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Menu;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class MenuTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Menu
	 */
	private $menu;

	public function setUp(): void {
		parent::setUp();

		$this->assume_admin_page();

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$this->menu = new Menu( new Settings() );
	}

	public function tearDown(): void {
		if ( is_multisite() ) {
			set_current_screen( 'dashboard' );
		}

		parent::tearDown();
	}

	/**
	 * @group ms-required
	 */
	public function test_add_menu_should_register_every_network_admin_page_with_a_network_capability() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		wp_set_current_user( $this->create_set_super_admin() );
		set_current_screen( 'dashboard-network' );
		$this->assertTrue( is_network_admin() );

		$this->register_menu();

		$capabilities = $this->get_registered_capability_per_page();

		$this->assertArrayHasKey( Menu::SLUG_SETTINGS, $capabilities );

		$matomo_capabilities = ( new Capabilities( new Settings() ) )->get_all_capabilities_sorted_by_highest_permission();

		foreach ( $capabilities as $slug => $capability ) {
			$this->assertNotContains(
				$capability,
				$matomo_capabilities,
				$slug . ' is registered with a Matomo capability in the network admin'
			);
			$this->assertSame( Menu::CAP_NETWORK, $capability, $slug );
		}
	}

	/**
	 * A site administrator can hand the matomo_superuser_role out with plain promote_users, so
	 * holding it must not open anything in the network admin.
	 *
	 * @group ms-required
	 */
	public function test_add_menu_should_register_no_network_admin_page_for_a_matomo_superuser_role_holder() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );
		set_current_screen( 'dashboard-network' );

		// the role still carries the capability these pages used to be registered with, even though
		// the network no longer lets it grant it
		$this->assertTrue( get_role( Roles::ROLE_SUPERUSER )->has_cap( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin( $user_id ) );

		$this->register_menu();

		$this->assertSame( [], $this->get_registered_capability_per_page() );

		// user_can_access_admin_page() refuses a page recorded here before it looks at anything else
		$denied = $this->get_denied_pages();

		$this->assertArrayHasKey( Menu::SLUG_SETTINGS, $denied );
		$this->assertArrayHasKey( Menu::SLUG_SYSTEM_REPORT, $denied );
		$this->assertArrayHasKey( Menu::SLUG_ABOUT, $denied );
	}

	public function test_add_menu_should_keep_the_matomo_capability_on_a_blogs_own_admin() {
		$this->activate_matomo_plugin();

		wp_set_current_user( $this->create_set_super_admin() );

		if ( is_multisite() ) {
			set_current_screen( 'dashboard' );
			$this->assertFalse( is_network_admin() );
		}

		$this->register_menu();

		$capabilities = $this->get_registered_capability_per_page();

		$this->assertSame( Capabilities::KEY_VIEW, $capabilities[ Menu::SLUG_ABOUT ] );
		$this->assertSame( Capabilities::KEY_SUPERUSER, $capabilities[ Menu::SLUG_SYSTEM_REPORT ] );
	}

	/**
	 * @group ms-required
	 */
	public function test_add_menu_should_register_the_settings_page_for_a_matomo_admin_on_a_blog_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		set_current_screen( 'dashboard' );
		$this->assertFalse( is_network_admin() );
		$this->assertTrue( current_user_can( Capabilities::KEY_ADMIN ) );
		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->register_menu();

		// the page carries only the Exclusions and Privacy tabs there, and everything the blog
		// decides for itself on them is a Matomo admin's to change
		$this->assertSame( Capabilities::KEY_ADMIN, $this->get_registered_capability_per_page()[ Menu::SLUG_SETTINGS ] );
	}

	public function test_add_menu_should_require_matomo_super_user_for_the_settings_page_when_the_network_is_not_enabled() {
		wp_set_current_user( $this->create_set_super_admin() );

		if ( is_multisite() ) {
			set_current_screen( 'dashboard' );
		}

		$this->register_menu();

		// every tab is on the page then, including the ones that configure tracking
		$this->assertSame( Capabilities::KEY_SUPERUSER, $this->get_registered_capability_per_page()[ Menu::SLUG_SETTINGS ] );
	}

	private function register_menu() {
		global $menu, $submenu, $_registered_pages, $_parent_pages, $_wp_submenu_nopriv, $_wp_menu_nopriv;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$menu               = [];
		$submenu            = [];
		$_registered_pages  = [];
		$_parent_pages      = [];
		$_wp_submenu_nopriv = [];
		$_wp_menu_nopriv    = [];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		$this->menu->add_menu();
	}

	/**
	 * @return array<string, string> page slug => the capability it was registered with
	 */
	private function get_registered_capability_per_page() {
		global $submenu;

		$capabilities = [];
		foreach ( (array) ( isset( $submenu[ Menu::$parent_slug ] ) ? $submenu[ Menu::$parent_slug ] : [] ) as $item ) {
			$capabilities[ $item[2] ] = $item[1];
		}

		return $capabilities;
	}

	/**
	 * @return array<string, bool> the pages WordPress refused to register for the current user
	 */
	private function get_denied_pages() {
		global $_wp_submenu_nopriv;

		return isset( $_wp_submenu_nopriv[ Menu::$parent_slug ] ) ? $_wp_submenu_nopriv[ Menu::$parent_slug ] : [];
	}
}
