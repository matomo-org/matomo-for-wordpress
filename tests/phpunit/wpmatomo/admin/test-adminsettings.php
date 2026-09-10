<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\AdminSettings;
use WpMatomo\Admin\AdvancedSettings;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\PluginMeasurableSettings;
use WpMatomo\Admin\PrivacySettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	const TAB_ADDED_BY_A_PLUGIN = 'plugin-tab-added-in-tests';

	/**
	 * @var AdminSettings
	 */
	private $admin_settings;

	public function setUp(): void {
		parent::setUp();

		$settings             = new Settings();
		$this->admin_settings = new AdminSettings( $settings );

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		remove_all_filters( 'matomo_setting_tabs' );

		parent::tearDown();
	}

	public function test_show_settings_renders_ui() {
		wp_set_current_user( $this->create_set_super_admin() );

		$output = $this->show();

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Tracking', $output );
		$this->assertStringContainsString( 'Access', $output );
	}

	public function test_show_should_offer_only_the_exclusions_and_privacy_tabs_to_a_matomo_admin() {
		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$this->assertTrue( current_user_can( Capabilities::KEY_ADMIN ) );
		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertSame(
			[ AdminSettings::TAB_PRIVACY, AdminSettings::TAB_EXCLUSIONS ],
			$this->get_rendered_tabs( $this->show() )
		);
	}

	public function test_show_should_not_offer_a_tab_another_plugin_added_to_a_matomo_admin() {
		$this->add_setting_tab_from_a_plugin();

		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$_GET['tab'] = self::TAB_ADDED_BY_A_PLUGIN;

		try {
			$tabs = $this->get_rendered_tabs( $this->show() );
		} finally {
			unset( $_GET['tab'] );
		}

		$this->assertNotContains( self::TAB_ADDED_BY_A_PLUGIN, $tabs );
		$this->assertSame( [ AdminSettings::TAB_PRIVACY, AdminSettings::TAB_EXCLUSIONS ], $tabs );
	}

	public function test_show_should_offer_a_tab_another_plugin_added_to_a_matomo_super_user() {
		$this->add_setting_tab_from_a_plugin();

		wp_set_current_user( $this->create_set_super_admin() );

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	public function test_show_should_render_no_tab_when_another_plugin_removed_every_tab() {
		$this->remove_every_setting_tab();

		wp_set_current_user( $this->create_set_super_admin() );

		$output = $this->show();

		$this->assertSame( [], $this->get_rendered_tabs( $output ) );
		// the page itself still renders rather than fatalling on the missing tab
		$this->assertStringContainsString( 'nav-tab-wrapper', $output );
	}

	public function test_show_should_render_no_tab_when_the_only_tab_a_matomo_admin_may_see_was_removed() {
		$this->remove_every_setting_tab();

		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$output = $this->show();

		$this->assertSame( [], $this->get_rendered_tabs( $output ) );
		$this->assertStringContainsString( 'nav-tab-wrapper', $output );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_offer_only_the_exclusions_and_privacy_tabs_to_a_blog_administrator_when_the_plugin_is_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->network_activate_and_become_a_blog_administrator();

		$this->assertSame(
			[ AdminSettings::TAB_PRIVACY, AdminSettings::TAB_EXCLUSIONS ],
			$this->get_rendered_tabs( $this->show() )
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_not_offer_a_tab_another_plugin_added_to_a_blog_administrator_when_the_plugin_is_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->add_setting_tab_from_a_plugin();

		$this->network_activate_and_become_a_blog_administrator();

		$_GET['tab'] = self::TAB_ADDED_BY_A_PLUGIN;

		try {
			$tabs = $this->get_rendered_tabs( $this->show() );
		} finally {
			unset( $_GET['tab'] );
		}

		$this->assertNotContains( self::TAB_ADDED_BY_A_PLUGIN, $tabs );
		$this->assertSame( [ AdminSettings::TAB_PRIVACY, AdminSettings::TAB_EXCLUSIONS ], $tabs );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_still_offer_the_settings_of_a_matomo_plugin_to_a_blog_administrator_when_the_plugin_is_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->add_measurable_settings_as_a_tab_from_a_plugin();

		$this->network_activate_and_become_a_blog_administrator();

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	public function test_show_should_offer_the_settings_of_a_matomo_plugin_to_a_matomo_admin() {
		$this->add_measurable_settings_as_a_tab_from_a_plugin();

		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_offer_the_settings_of_a_matomo_plugin_to_a_matomo_admin_when_the_plugin_is_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->add_measurable_settings_as_a_tab_from_a_plugin();

		$this->network_activate_and_become_a_matomo_admin();

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_not_offer_a_tab_another_plugin_added_that_refuses_the_user_itself() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->add_advanced_settings_as_a_tab_from_a_plugin();

		$this->network_activate_and_become_a_blog_administrator();

		$this->assertFalse( current_user_can( Menu::CAP_NETWORK ) );

		$this->assertNotContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_offer_a_tab_another_plugin_added_that_allows_the_user_itself() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->add_advanced_settings_as_a_tab_from_a_plugin();

		$this->activate_matomo_plugin();
		$this->admin_settings = new AdminSettings( new Settings() );

		wp_set_current_user( $this->create_set_super_admin() );

		// the network's own screen, which is where the tabs configuring the network wide settings are
		// offered.
		set_current_screen( 'dashboard-network' );
		$this->assertTrue( is_network_admin() );

		$this->assertTrue( current_user_can( Menu::CAP_NETWORK ) );

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
	}

	private function network_activate_and_become_a_blog_administrator() {
		$this->activate_matomo_plugin();

		// built again so that it reads the settings as they are with the plugin network activated
		$this->admin_settings = new AdminSettings( new Settings() );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );
		// an administrator is the Matomo super user of the blog they administrate
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
	}

	private function network_activate_and_become_a_matomo_admin() {
		$this->activate_matomo_plugin();

		// built again so that it reads the settings as they are with the plugin network activated
		$this->admin_settings = new AdminSettings( new Settings() );

		( new Roles( new Settings() ) )->add_roles( true );
		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );
		$this->assertTrue( current_user_can( Capabilities::KEY_ADMIN ) );
		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );
	}

	private function add_advanced_settings_as_a_tab_from_a_plugin() {
		add_filter(
			'matomo_setting_tabs',
			function ( $tabs ) {
				// a real screen that answers can_user_manage() for itself, standing in for a tab an
				// add-on would register
				$tabs[ self::TAB_ADDED_BY_A_PLUGIN ] = new AdvancedSettings( new Settings() );

				return $tabs;
			}
		);
	}

	private function add_measurable_settings_as_a_tab_from_a_plugin() {
		add_filter(
			'matomo_setting_tabs',
			function ( $tabs ) {
				// the same class AdminSettings builds its own Matomo plugin tabs out of, which needs an
				// installed marketplace plugin to be discovered for real
				$tabs[ self::TAB_ADDED_BY_A_PLUGIN ] = new PluginMeasurableSettings( 'PluginName', 'Plugin Name' );

				return $tabs;
			}
		);
	}

	private function add_setting_tab_from_a_plugin() {
		add_filter(
			'matomo_setting_tabs',
			function ( $tabs ) {
				$tabs[ self::TAB_ADDED_BY_A_PLUGIN ] = new PrivacySettings( new Settings() );

				return $tabs;
			}
		);
	}

	private function remove_every_setting_tab() {
		add_filter(
			'matomo_setting_tabs',
			function () {
				return [];
			}
		);
	}

	private function show() {
		ob_start();

		try {
			$this->admin_settings->show();
		} finally {
			$output = ob_get_clean();
		}

		return $output;
	}

	private function get_rendered_tabs( $output ) {
		preg_match_all( '/[?&]tab=([a-z0-9_-]+)/i', $output, $matches );

		return array_values( array_unique( $matches[1] ) );
	}
}
