<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\GeolocationSettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminGeolocationSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var GeolocationSettings
	 */
	private $geolocation_settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings             = new Settings();
		$this->geolocation_settings = new GeolocationSettings( $this->settings );

		wp_get_current_user()->add_role( Roles::ROLE_SUPERUSER );

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		$_REQUEST = [];
		$_POST    = [];

		parent::tearDown();
	}

	public function test_show_settings_should_store_a_submitted_licence_key() {
		$this->submit_licence_key( 'abcdefghij' );

		$this->assertSame( 'abcdefghij', ( new Settings() )->get_global_option( 'maxmind_license_key' ) );
	}

	public function test_show_settings_should_not_store_a_licence_key_submitted_by_a_user_without_matomo_super_user_access() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$this->submit_licence_key( 'abcdefghij' );

		$this->assertEmpty( ( new Settings() )->get_global_option( 'maxmind_license_key' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_store_a_licence_key_submitted_by_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();

		$this->submit_licence_key( 'abcdefghij' );

		$this->assertEmpty( ( new Settings() )->get_global_option( 'maxmind_license_key' ) );
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

		$this->assertFalse( ( new GeolocationSettings( new Settings() ) )->can_user_manage() );
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

		$this->assertTrue( ( new GeolocationSettings( new Settings() ) )->can_user_manage() );
	}

	public function test_can_user_manage_should_allow_a_matomo_super_user_when_the_network_is_not_enabled() {
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertTrue( $this->geolocation_settings->can_user_manage() );
	}

	public function test_can_user_manage_should_refuse_a_user_without_matomo_super_user_access() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$this->assertFalse( $this->geolocation_settings->can_user_manage() );
	}

	private function submit_licence_key( $licence_key ) {
		$_POST[ GeolocationSettings::FORM_NAME ] = $licence_key;
		$_REQUEST['_wpnonce']                    = wp_create_nonce( GeolocationSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']                  = home_url();

		ob_start();
		( new GeolocationSettings( new Settings() ) )->show_settings();
		ob_end_clean();
	}
}
