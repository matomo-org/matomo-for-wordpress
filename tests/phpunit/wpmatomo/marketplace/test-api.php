<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Marketplace\Api;
use WpMatomo\Settings;
use WpMatomo\TrackingCode\TrackingCodeGenerator;

class MarketplaceApiTest extends MatomoUnit_TestCase {

	/**
	 * @var Api
	 */
	private $api;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings = new Settings();
		$this->api      = new Api( $this->settings );
	}

	public function test_api() {
		$this->assertSame( array(), $this->api->get_licenses() );
	}

	/**
	 * @dataProvider get_invalid_key_format
	 */
	public function test_is_valid_api_key_not_valid_format() {
		$this->assertFalse( $this->api->is_valid_api_key( 'foobarbaz' ) );
	}

	/**
	 * @dataProvider get_invalid_key_format
	 */
	public function test_is_valid_api_key_valid_format_but_not_existing_key() {
		$key = sha1( 1 );
		$this->assertFalse( $this->api->is_valid_api_key( $key ) );
	}

	public function get_invalid_key_format() {
		return array(
			array( '123' ), // too short
			array( str_pad( '1', '1', 90 ) ), // too long
			array( 'foobarbazfoobarbazfoob_34343Larbazfoobarbazfoobarbazfo' ), // contains not valid characters
		);
	}

}
