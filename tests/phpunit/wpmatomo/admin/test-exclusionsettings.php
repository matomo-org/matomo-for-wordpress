<?php
/**
 * @package matomo
 */

use Piwik\Access;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SitesManager\API;
use Piwik\Session\SessionAuth;
use WpMatomo\Admin\ExclusionSettings;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Admin\InvalidIpException;
use WpMatomo\Roles;
use WpMatomo\Settings;

class AdminExclusionSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var ExclusionSettings
	 */
	private $exclusion_settings;

	public function setUp(): void {
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
		$this->assertStringContainsString( 'Save Changes', $output );
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

		$settings = new Settings();

		// verify actually saved
		$this->assertEquals( '127.0.0.1,127.0.0.2', API::getInstance()->getExcludedIpsGlobal() );
		$this->assertEquals( 'test,test2', API::getInstance()->getExcludedQueryParametersGlobal() );
		$this->assertEquals( [ 'firefox', 'safari' ], $settings->get_global_user_agent_exclusions() );
		$this->assertNotEmpty( API::getInstance()->getKeepURLFragmentsGlobal() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_let_a_matomo_admin_change_what_their_own_blog_records_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();
		( new Roles( new Settings() ) )->add_roles( true );

		$settings = new Settings();
		$settings->apply_changes( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->authenticate_matomo_as_current_user();

		try {
			$this->submit_exclusion_settings(
				[
					Settings::OPTION_KEY_STEALTH_BLOG => [ 'author' => '1' ],
					'excluded_ips'                    => '127.0.0.9',
					'excluded_user_agents'            => "firefox\nsafari",
				]
			);
		} finally {
			$this->restore_matomo_super_user_access();
		}

		$saved = new Settings();

		// check that 'editor' was added to the blog specific action and that it's correctly merged
		// with the pre-existing network wide option when fetching.
		$this->assertSame( [ 'author' => '1' ], $saved->get_option( Settings::OPTION_KEY_STEALTH_BLOG ) );
		$this->assertSame(
			[
				'editor' => true,
				'author' => true,
			],
			$saved->get_stealth_roles()
		);

		// check that excluded IPs was set
		$this->assertSame( '127.0.0.9', API::getInstance()->getExcludedIpsGlobal() );

		// and that the user agents were stored without needing Matomo super user access
		$this->assertSame( [ 'firefox', 'safari' ], $saved->get_global_user_agent_exclusions() );

		// check that the network wide option was not modified
		$this->assertSame( [ 'editor' => '1' ], $saved->get_global_option( Settings::OPTION_KEY_STEALTH ) );
	}

	public function test_show_settings_should_not_let_a_matomo_write_user_change_the_exclusions() {
		( new Roles( new Settings() ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_WRITE ] ) );

		$this->assertFalse( current_user_can( Capabilities::KEY_ADMIN ) );

		$this->submit_exclusion_settings( [ 'excluded_ips' => '127.0.0.9' ] );

		$this->assertEmpty( API::getInstance()->getExcludedIpsGlobal() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_let_a_matomo_admin_change_the_network_wide_tracking_filter_from_a_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();
		( new Roles( new Settings() ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_ADMIN ] ) );

		// the field name of the network's list rather than the blog's, which is what a hand written
		// request would carry
		$this->submit_exclusion_settings( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );

		$this->assertSame( [], ( new Settings() )->get_global_option( Settings::OPTION_KEY_STEALTH ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_let_a_network_administrator_change_the_network_wide_tracking_filter() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		wp_set_current_user( $this->create_set_super_admin() );
		set_current_screen( 'dashboard-network' );
		$this->assertTrue( is_network_admin() );

		$this->assertSame( [], ( new Settings() )->get_global_option( Settings::OPTION_KEY_STEALTH ) );

		try {
			$output = $this->submit_exclusion_settings( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );

			$this->assertStringContainsString( 'Tracking filter', $output );
			$this->assertStringNotContainsString( 'excluded_ips', $output );
		} finally {
			set_current_screen( 'dashboard' );
		}

		$this->assertSame( [ 'editor' => '1' ], ( new Settings() )->get_global_option( Settings::OPTION_KEY_STEALTH ) );
	}

	public function test_show_settings_should_keep_the_tracking_filter_in_one_setting_when_the_network_is_not_enabled() {
		$this->submit_exclusion_settings( [ Settings::OPTION_KEY_STEALTH => [ 'editor' => '1' ] ] );

		$saved = new Settings();

		$this->assertSame( [ 'editor' => '1' ], $saved->get_global_option( Settings::OPTION_KEY_STEALTH ) );
		$this->assertSame( [], $saved->get_option( Settings::OPTION_KEY_STEALTH_BLOG ) );
		$this->assertSame( [ 'editor' => true ], $saved->get_stealth_roles() );
	}

	public function test_validate_ip() {
		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '127.0.0.1',
		);
		$_REQUEST['_wpnonce']                  = wp_create_nonce( ExclusionSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']                = home_url();

		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertTrue( true );
		} catch ( InvalidIpException $e ) {
			$this->assertFalse( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '1.2.3.4/24',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertTrue( true );
		} catch ( InvalidIpException $e ) {
			$this->assertFalse( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '1.2.3.*',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertTrue( true );
		} catch ( InvalidIpException $e ) {
			$this->assertFalse( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '1.2.*.*',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertTrue( true );
		} catch ( InvalidIpException $e ) {
			$this->assertFalse( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '350.17.24.23',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertFalse( true );
		} catch ( InvalidIpException $e ) {
			$this->assertTrue( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => 'not an ip',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertFalse( true );
		} catch ( InvalidIpException $e ) {
			$this->assertTrue( true );
		}
		ob_get_clean();

		$_POST[ ExclusionSettings::FORM_NAME ] = array(
			'excluded_ips' => '192.168.0.1/34',
		);
		ob_start();
		try {
			$this->exclusion_settings->show_settings( true );
			$this->assertFalse( true );
		} catch ( InvalidIpException $e ) {
			$this->assertTrue( true );
		}
		ob_get_clean();
	}

	private function authenticate_matomo_as_current_user() {
		Bootstrap::do_bootstrap();

		$access = Access::getInstance();

		$access->setSuperUserAccess( false );
		$access->reloadAccess( StaticContainer::get( SessionAuth::class ) );

		$this->assertFalse( $access->hasSuperUserAccess() );
	}

	private function restore_matomo_super_user_access() {
		Access::getInstance()->setSuperUserAccess( true );
	}

	/**
	 * @param array $form_values
	 *
	 * @return string what the screen rendered
	 */
	private function submit_exclusion_settings( $form_values ) {
		$_POST[ ExclusionSettings::FORM_NAME ] = $form_values;
		$_REQUEST['_wpnonce']                  = wp_create_nonce( ExclusionSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']                = home_url();

		ob_start();

		try {
			// a new instance, so that it loads the settings again, rather than using what
			// the tests set up
			( new ExclusionSettings( new Settings() ) )->show_settings();
		} finally {
			$output = ob_get_clean();

			$_POST    = [];
			$_REQUEST = [];
		}

		return $output;
	}
}
