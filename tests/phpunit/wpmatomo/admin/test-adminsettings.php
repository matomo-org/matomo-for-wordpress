<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\AdminSettings;
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

	public function test_show_should_offer_only_the_exclusions_tab_to_a_matomo_admin() {
		( new Roles( new Settings() ) )->add_roles( true );
		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$this->assertTrue( current_user_can( Capabilities::KEY_ADMIN ) );
		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertSame( [ AdminSettings::TAB_EXCLUSIONS ], $this->get_rendered_tabs( $this->show() ) );
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
		$this->assertSame( [ AdminSettings::TAB_EXCLUSIONS ], $tabs );
	}

	public function test_show_should_offer_a_tab_another_plugin_added_to_a_matomo_super_user() {
		$this->add_setting_tab_from_a_plugin();

		wp_set_current_user( $this->create_set_super_admin() );

		$this->assertContains( self::TAB_ADDED_BY_A_PLUGIN, $this->get_rendered_tabs( $this->show() ) );
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
