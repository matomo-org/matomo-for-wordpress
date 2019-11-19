<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Marketplace;
use WpMatomo\Settings;

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
		$this->marketplace = new Marketplace( $this->settings );

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
		$this->assertContains( 'Discover new functionality for your Matomo', $output );
	}


}
