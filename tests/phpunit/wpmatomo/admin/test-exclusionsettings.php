<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\API;
use WpMatomo\Admin\ExclusionSettings;
use WpMatomo\Capabilities;

class AdminExclusionSettingsTest extends MatomoAnalytics_TestCase {

	/**
	 * @var ExclusionSettings
	 */
	private $exclusion_settings;

	public function setUp() {
		parent::setUp();

		$settings                 = new \WpMatomo\Settings();
		$this->exclusion_settings = new ExclusionSettings( $settings );
		$this->create_set_super_admin();
		$this->assume_admin_page();
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->exclusion_settings->show_settings();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertContains( 'Save Changes', $output );
	}

	public function test_show_settings_does_change_any_values_if_nonce() {
		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips'              => "127.0.0.1\n127.0.0.2",
			'excluded_query_parameters' => "test\ntest2",
			'excluded_user_agents'      => "firefox\nsafari",
			'keep_url_fragments'        => '1',
		);
		$_REQUEST['_wpnonce']                  = wp_create_nonce( ExclusionSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']                = home_url();

		ob_start();
		$this->exclusion_settings->show_settings();
		$output = ob_get_clean();

		// verify actually saved
		$this->assertEquals( '127.0.0.1,127.0.0.2', API::getInstance()->getExcludedIpsGlobal() );
		$this->assertEquals( 'test,test2', API::getInstance()->getExcludedQueryParametersGlobal() );
		$this->assertEquals( 'firefox,safari', API::getInstance()->getExcludedUserAgentsGlobal() );
		$this->assertNotEmpty( API::getInstance()->getKeepURLFragmentsGlobal() );
	}

	public function test_validate_ip() {
		$this->assertTrue( $this->exclusion_settings->validate_ip( '127.0.0.1' ) );
		$this->assertTrue( $this->exclusion_settings->validate_ip( '1.2.3.4/24' ) );
		$this->assertTrue( $this->exclusion_settings->validate_ip( '1.2.3.*' ) );
		$this->assertTrue( $this->exclusion_settings->validate_ip( '1.2.*.*' ) );
		$this->assertFalse( $this->exclusion_settings->validate_ip( '350.17.24.23' ) );
		$this->assertFalse( $this->exclusion_settings->validate_ip( 'not an ip' ) );
		$this->assertFalse( $this->exclusion_settings->validate_ip( '192.168.0.1/32' ) );
	}

	public function test_validate_user_agent() {
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'Firefox' ) );
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'Mozilla/5.0' ) );
		$this->assertFalse( $this->exclusion_settings->validate_user_agent( 'Mozilla/a.a' ) );
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'AdsBot-Google' ) );
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'Goose/3.1.6' ) );
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'PEAR HTTP_Request class' ) );
		$this->assertTrue( $this->exclusion_settings->validate_user_agent( 'WeSEE:Search/0.1' ) );
	}

}
