<?php
/**
 * @package matomo
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
		$this->create_set_super_admin();
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

		ob_start();
		$marketplace = new Marketplace( $this->settings, new AdminMarketplaceTestValidApiKeyMock( $this->settings ) );
		$marketplace->show();
		ob_end_clean();

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

	public function test_show_settings_does_not_change_any_values_when_not_correct_format() {
		$this->fake_request( 'foobar' );

		ob_start();
		$this->marketplace->show();
		$output = ob_get_clean();

		$this->assertEmpty( $this->settings->get_license_key() );
		$this->assertContains( 'License key is not valid', $output );
	}

	private function fake_request( $license_key ) {
		$_POST[ Marketplace::FORM_NAME ] = $license_key;
		$_REQUEST['_wpnonce']            = wp_create_nonce( Marketplace::NONCE_LICENSE );
		$_SERVER['REQUEST_URI']          = home_url();
	}


}
