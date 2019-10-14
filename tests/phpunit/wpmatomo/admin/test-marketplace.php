<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Admin\Marketplace;
use WpMatomo\Marketplace\Api;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminMarketplaceTestValidApiKeyMock extends Api {
	public function is_valid_api_key( $license_key ) {
		return true;
	}
}

class AdminMarketplaceTest extends MatomoUnit_TestCase {

	/**
	 * @var Marketplace
	 */
	private $marketplace;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();
		$this->settings    = new Settings();
		$api               = new Api( $this->settings );
		$this->marketplace = new Marketplace( $this->settings, $api );

		$this->assume_admin_page();

		if ( is_multisite() ) {
			$id = self::factory()->user->create();
			grant_super_admin( $id );
			wp_set_current_user( $id );
		} else {
			$user = wp_get_current_user();
			$user->add_role( Roles::ROLE_SUPERUSER );
		}

	}

	public function tearDown() {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->marketplace->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'Marketplace', $output );
	}

	public function test_show_does_change_license_key_if_nonce_and_valid() {
		$this->assertEmpty( $this->settings->get_license_key() );

		$license_key = sha1( 1 );
		$this->fake_request( $license_key );

		$marketplace = new Marketplace( $this->settings, new AdminMarketplaceTestValidApiKeyMock( $this->settings ) );
		$marketplace->show();

		$this->assertSame( $license_key, $this->settings->get_license_key() );
	}

	public function test_show_does_not_change_license_key_if_nonce_but_not_valid() {
		$this->assertEmpty( $this->settings->get_license_key() );

		$this->fake_request( sha1( 1 ) );

		ob_start();
		$this->marketplace->show();
		$output = ob_get_clean();

		$this->assertEmpty( $this->settings->get_license_key() );
		$this->assertContains( 'License key is not valid', $output );
	}

	public function test_show_settings_does_not_change_any_values_when_not_superuser() {
		wp_get_current_user()->remove_role( Roles::ROLE_SUPERUSER );

		if ( is_multisite() ) {
			revoke_super_admin( wp_get_current_user()->ID );
		}

		$this->fake_request( sha1( 1 ) );

		ob_start();
		$this->marketplace->show();
		$output = ob_get_clean();

		$this->assertEmpty( $this->settings->get_license_key() );
		$this->assertNotContains( 'License key is not valid', $output ); // doesn't contain the string cause it should not have the permission to execute it
	}

	public function test_show_settings_does_not_change_any_values_when_not_correct_format() {

		$this->fake_request( 'foobar' );

		ob_start();
		$this->marketplace->show();
		$output = ob_get_clean();

		$this->assertEmpty( $this->settings->get_license_key() );
		$this->assertContains( 'License key is not valid', $output );
	}

	private function fake_request( $licenseKey ) {
		$_POST[ Marketplace::FORM_NAME ] = $licenseKey;
		$_REQUEST['_wpnonce']            = wp_create_nonce( Marketplace::NONCE_LICENSE );
		$_SERVER['REQUEST_URI']          = home_url();

	}


}
