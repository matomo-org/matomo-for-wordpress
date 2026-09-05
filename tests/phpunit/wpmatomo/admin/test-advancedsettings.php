<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\AdvancedSettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminAdvancedSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var AdvancedSettings
	 */
	private $advanced_settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings          = new Settings();
		$this->advanced_settings = new AdvancedSettings( $this->settings );

		wp_get_current_user()->add_role( Roles::ROLE_SUPERUSER );

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		$_REQUEST = [];
		$_POST    = [];

		parent::tearDown();
	}

	public function test_show_settings_should_store_a_submitted_tracking_delay() {
		$this->submit_tracking_delay( 42 );

		$this->assertSame( 42, ( new Settings() )->get_option( Settings::SERVER_SIDE_TRACKING_DELAY_SECS ) );
	}

	public function test_show_settings_should_not_store_a_tracking_delay_submitted_by_a_user_without_matomo_super_user_access() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$this->submit_tracking_delay( 42 );

		$this->assertNotSame( 42, ( new Settings() )->get_option( Settings::SERVER_SIDE_TRACKING_DELAY_SECS ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_store_a_tracking_delay_submitted_by_a_matomo_super_user_who_does_not_administrate_the_network() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();

		$this->submit_tracking_delay( 42 );

		$this->assertNotSame( 42, ( new Settings() )->get_option( Settings::SERVER_SIDE_TRACKING_DELAY_SECS ) );
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

		// these settings are the network's, the proxy client header above all
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin() );

		$this->assertFalse( ( new AdvancedSettings( new Settings() ) )->can_user_manage() );
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

		$this->assertTrue( ( new AdvancedSettings( new Settings() ) )->can_user_manage() );
	}

	public function test_can_user_manage_should_allow_a_matomo_super_user_when_the_network_is_not_enabled() {
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->assertTrue( $this->advanced_settings->can_user_manage() );
	}

	public function test_can_user_manage_should_refuse_a_user_without_matomo_super_user_access() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		$this->assertFalse( $this->advanced_settings->can_user_manage() );
	}

	private function submit_tracking_delay( $delay ) {
		$_POST[ AdvancedSettings::FORM_NAME ] = [
			Settings::SERVER_SIDE_TRACKING_DELAY_SECS => (string) $delay,
		];
		$_REQUEST['_wpnonce']                 = wp_create_nonce( AdvancedSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']               = home_url();

		ob_start();
		( new AdvancedSettings( new Settings() ) )->show_settings();
		ob_end_clean();
	}
}
