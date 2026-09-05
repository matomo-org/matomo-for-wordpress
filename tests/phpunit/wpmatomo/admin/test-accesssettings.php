<?php
/**
 * @package matomo
 */

use WpMatomo\Access;
use WpMatomo\Admin\AccessSettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminAccessSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var AccessSettings
	 */
	private $access_settings;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Access
	 */
	private $access;

	public function setUp(): void {
		parent::setUp();

		$this->settings        = new Settings();
		$this->access          = new Access( $this->settings );
		$this->access_settings = new AccessSettings( $this->access, $this->settings );

		wp_get_current_user()->add_role( Roles::ROLE_SUPERUSER );

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_settings_renders_ui() {
		ob_start();
		$this->access_settings->show_settings();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'WordPress Role', $output );
	}

	public function test_show_settings_does_change_any_values_if_nonce() {
		$_POST[ AccessSettings::FORM_NAME ] = array( 'editor' => Capabilities::KEY_VIEW );
		$_REQUEST['_wpnonce']               = wp_create_nonce( AccessSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']             = home_url();

		ob_start();
		$this->access_settings->show_settings();
		ob_end_clean();

		$this->assertSame( Capabilities::KEY_VIEW, $this->access->get_permission_for_role( 'editor' ) );
	}

	public function test_show_settings_does_not_change_any_values_when_not_superuser() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$_POST[ AccessSettings::FORM_NAME ] = array( 'editor' => Capabilities::KEY_VIEW );
		$_REQUEST['_wpnonce']               = wp_create_nonce( AccessSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']             = home_url();

		ob_start();
		$this->access_settings->show_settings();
		ob_end_clean();

		$this->assertEmpty( $this->access->get_permission_for_role( 'editor' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_change_any_values_for_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();

		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin() );

		$_POST[ AccessSettings::FORM_NAME ] = array( 'editor' => Capabilities::KEY_VIEW );
		$_REQUEST['_wpnonce']               = wp_create_nonce( AccessSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']             = home_url();

		ob_start();
		( new AccessSettings( $this->access, new Settings() ) )->show_settings();
		ob_end_clean();

		$this->assertEmpty( $this->access->get_permission_for_role( 'editor' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_refuse_a_matomo_super_user_who_does_not_administrate_the_network_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin() );

		$this->assertFalse( ( new AccessSettings( $this->access, new Settings() ) )->can_user_manage() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_allow_a_network_administrator_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$this->create_set_super_admin();

		$this->assertTrue( ( new AccessSettings( $this->access, new Settings() ) )->can_user_manage() );
	}

	public function test_can_user_manage_should_allow_a_matomo_super_user_when_the_network_is_not_enabled() {
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertTrue( $this->access_settings->can_user_manage() );
	}

	public function test_can_user_manage_should_refuse_a_user_without_matomo_super_user_access() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$this->assertFalse( $this->access_settings->can_user_manage() );
	}
}
