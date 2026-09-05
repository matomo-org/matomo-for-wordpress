<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;

/**
 * @package matomo
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
 */
class AdminTrackingSettingsTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var TrackingSettings
	 */
	private $tracking_settings;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var callable|null
	 */
	private $unfiltered_html_grant;

	public function setUp(): void {
		parent::setUp();

		$this->settings          = new Settings();
		$this->tracking_settings = new TrackingSettings( $this->settings );

		$this->create_set_super_admin();
		$this->assume_admin_page();

		$this->delete_temp_wp_config();
	}

	public function tearDown(): void {
		$_REQUEST = array();
		$_POST    = array();

		if ( $this->unfiltered_html_grant ) {
			remove_filter( 'map_meta_cap', $this->unfiltered_html_grant, 20 );
			$this->unfiltered_html_grant = null;
		}

		$this->delete_temp_wp_config();

		parent::tearDown();
	}

	public function test_show_settings_renders_ui() {
		ob_start();
		$this->tracking_settings->show_settings();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Tracking code', $output );
	}

	public function test_show_settings_does_change_any_values_if_nonce() {
		$this->assertSame( TrackingSettings::TRACK_MODE_DISABLED, $this->settings->get_global_option( 'track_mode' ) );
		$this->assertSame( 'default', $this->settings->get_global_option( 'track_js_endpoint' ) );
		$this->assertEquals( false, $this->settings->get_global_option( 'track_404' ) );

		$this->fake_request(
			array(
				'track_mode'        => TrackingSettings::TRACK_MODE_MANUALLY,
				'track_js_endpoint' => 'restapi',
				'track_404'         => true,
			)
		);

		ob_start();
		$this->tracking_settings->show_settings();
		ob_end_clean();

		$this->assertSame( TrackingSettings::TRACK_MODE_MANUALLY, $this->settings->get_global_option( 'track_mode' ) );
		$this->assertSame( 'restapi', $this->settings->get_global_option( 'track_js_endpoint' ) );
		$this->assertEquals( true, $this->settings->get_global_option( 'track_404' ) );
	}

	private function fake_request( $params ) {
		$_POST[ TrackingSettings::FORM_NAME ] = $params;
		$_REQUEST['_wpnonce']                 = wp_create_nonce( TrackingSettings::NONCE_NAME );
		$_SERVER['REQUEST_URI']               = home_url();
	}

	public function test_show_settings_does_not_set_any_random_value_but_only_whitelisted() {
		$this->fake_request(
			array(
				'track_mode'                     => 'disabled',
				'foobar'                         => 'baz',
				Settings::OPTION_KEY_CAPS_ACCESS => array( 'editor' => Capabilities::KEY_VIEW ),
			)
		);

		ob_start();
		$this->tracking_settings->show_settings();
		ob_end_clean();

		$this->assertEmpty( $this->settings->get_global_option( 'foobar' ) );
		$this->assertEmpty( $this->settings->get_option( 'foobar' ) );
		$this->assertEquals( array(), $this->settings->get_global_option( Settings::OPTION_KEY_CAPS_ACCESS ) );
	}

	public function test_get_active_containers_when_no_container_defined() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'skipped in multisite' );

			return;
		}
		$containers = $this->tracking_settings->get_active_containers();
		$this->assertSame( array(), $containers );
	}

	public function test_validate_html_comments() {
		$html = '<div></div>';
		$this->assertTrue( $this->tracking_settings->validate_html_comments( $html ) );
		$html = '<script></script>';
		$this->assertTrue( $this->tracking_settings->validate_html_comments( $html ) );
		$html = '<!-- begin comment--><script></script><!-- end comment -->';
		$this->assertTrue( $this->tracking_settings->validate_html_comments( $html ) );
		$html = '<!-- begin comment--><script></script>';
		$this->assertTrue( $this->tracking_settings->validate_html_comments( $html ) );
		$html = '<!-- begin comment--><script></script><!-- end invalid tag ->';
		$this->assertFalse( $this->tracking_settings->validate_html_comments( $html ) );
		$html = '<!-- begin comment--><script></script><!-- valid end --><!-- end invalid tag ->';
		$this->assertFalse( $this->tracking_settings->validate_html_comments( $html ) );
	}

	public function test_is_track_script_used_in_wp_config_when_wp_config_does_not_exist() {
		$this->assertNull( TrackingSettings::is_track_script_used_in_wp_config( __DIR__ ) );
	}

	public function test_is_track_script_used_in_wp_config_when_wp_config_not_readable() {
		$wp_config_path = $this->get_wp_config_path();

		$contents = <<<EOF
<?php

if ( is_file( ABSPATH . 'wp-content/plugins/matomo/misc/track_ai_bot.php' ) ) {
	require_once ABSPATH . 'wp-content/plugins/matomo/misc/track_ai_bot.php';
}
EOF;

		file_put_contents( $wp_config_path, $contents );
		chmod( __DIR__ . '/wp-config.php', 222 ); // write only
		$this->assertFalse( is_readable( $wp_config_path ) );

		$this->assertNull( TrackingSettings::is_track_script_used_in_wp_config( __DIR__ ) );
	}

	public function test_is_track_script_used_in_wp_config_when_snippet_is_present() {
		$wp_config_path = $this->get_wp_config_path();

		$contents = <<<EOF
<?php

if ( is_file( ABSPATH . 'wp-content/plugins/matomo/misc/track_ai_bot.php' ) ) {
	require_once ABSPATH . 'wp-content/plugins/matomo/misc/track_ai_bot.php';
}
EOF;

		file_put_contents( $wp_config_path, $contents );

		$this->assertTrue( TrackingSettings::is_track_script_used_in_wp_config( __DIR__ ) );
	}

	public function test_is_track_script_used_in_wp_config_when_wp_config_is_readable_and_snippet_is_not_present() {
		$wp_config_path = $this->get_wp_config_path();

		file_put_contents( $wp_config_path, '<?php' );

		$this->assertFalse( TrackingSettings::is_track_script_used_in_wp_config( __DIR__ ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_refuse_a_matomo_super_user_who_does_not_administrate_the_network_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();
		( new Roles( $this->settings ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] ) );

		// the role is theirs to hold, and it is worth this blog's Matomo. the tracking settings are
		// the network's, so they are not part of what it is worth
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( is_super_admin() );

		$this->assertFalse( ( new TrackingSettings( new Settings() ) )->can_user_manage() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_manage_should_refuse_a_blog_administrator_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// network enabled for real rather than assumed, so that the plugin's own hooks see it too
		$this->activate_matomo_plugin();

		$this->make_current_user_blog_administrator_of_the_network_activated_install();

		$this->assertFalse( $this->tracking_settings->can_user_manage() );
		$this->assertFalse( $this->tracking_settings->can_user_edit_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_let_a_blog_administrator_change_any_tracking_setting_when_the_network_is_enabled() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		( new Settings() )->apply_tracking_related_changes(
			[
				'track_mode' => TrackingSettings::TRACK_MODE_DISABLED,
				'track_404'  => true,
			]
		);

		$this->make_current_user_blog_administrator_of_the_network_activated_install();

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'track_404'     => '',
				'tracking_code' => '<script>alert(/xss/)</script>',
				'noscript_code' => '<script>alert(/xss/)</script>',
			]
		);

		$saved = new Settings();

		// the whole screen is refused, not only the tracking code: every setting on it is stored
		// once for the network
		$this->assertSame( TrackingSettings::TRACK_MODE_DISABLED, $saved->get_global_option( 'track_mode' ) );
		$this->assertEquals( true, $saved->get_global_option( 'track_404' ) );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_js_tracking_code() );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_noscript_tracking_code() );
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

		$this->assertTrue( ( new TrackingSettings( new Settings() ) )->can_user_manage() );
	}

	public function test_can_user_manage_should_allow_a_matomo_super_user_when_the_network_is_not_enabled() {
		( new Roles( $this->settings ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] ) );

		$this->assertTrue( ( new TrackingSettings( new Settings() ) )->can_user_manage() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_edit_tracking_code_should_refuse_a_blog_administrator_when_the_plugin_is_not_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->make_current_user_blog_administrator();

		$tracking_settings = new TrackingSettings( new Settings() );

		// the rest of the tab is still theirs, only the code they could put on the frontend is not
		$this->assertTrue( $tracking_settings->can_user_manage() );
		$this->assertFalse( $tracking_settings->can_user_edit_tracking_code() );
	}

	public function test_can_user_edit_tracking_code_should_allow_an_administrator_outside_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}

		$this->assertTrue( ( new TrackingSettings( new Settings() ) )->can_user_edit_tracking_code() );
	}

	public function test_can_user_edit_tracking_code_should_refuse_the_matomo_super_user_role_outside_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}

		( new Roles( $this->settings ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] ) );

		$tracking_settings = new TrackingSettings( new Settings() );

		$this->assertTrue( $tracking_settings->can_user_manage() );
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );
		$this->assertFalse( $tracking_settings->can_user_edit_tracking_code() );
	}

	public function test_show_settings_should_not_let_the_matomo_super_user_role_switch_to_the_manual_tracking_code_outside_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}

		$this->settings->apply_tracking_related_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT ] );

		( new Roles( $this->settings ) )->add_roles( true );

		wp_set_current_user( self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] ) );

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<script>alert(/xss/)</script>',
				'noscript_code' => '<script>alert(/xss/)</script>',
			]
		);

		$saved = new Settings();

		$this->assertFalse( current_user_can( 'unfiltered_html' ) );
		$this->assertSame( TrackingSettings::TRACK_MODE_DEFAULT, $saved->get_global_option( 'track_mode' ) );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_js_tracking_code() );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_noscript_tracking_code() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_can_user_edit_tracking_code_should_refuse_everyone_when_unfiltered_html_is_disallowed() {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		define( 'DISALLOW_UNFILTERED_HTML', true );

		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$tracking_settings = new TrackingSettings( new Settings() );

		$this->assertFalse( $tracking_settings->can_user_edit_tracking_code() );

		// check that the rest of the tab is still editable
		$this->assertTrue( $tracking_settings->can_user_manage() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_edit_tracking_code_should_allow_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$user_id = $this->create_set_super_admin();

		$this->assertTrue( is_super_admin( $user_id ) );
		$this->assertNotContains( 'administrator', wp_get_current_user()->roles );
		$this->assertNotContains( 'editor', wp_get_current_user()->roles );

		// check that network admins have unfiltered_html by default
		$this->assertTrue( current_user_can( 'unfiltered_html' ) );

		$this->assertTrue( ( new TrackingSettings( new Settings() ) )->can_user_edit_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_can_user_edit_tracking_code_should_allow_a_blog_administrator_the_network_granted_unfiltered_html() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->make_current_user_blog_administrator();

		$this->assertFalse( ( new TrackingSettings( new Settings() ) )->can_user_edit_tracking_code() );

		// what a network that wants its blog administrators trusted with markup does, since
		// map_meta_cap() is where WordPress takes the capability away from them
		$this->grant_unfiltered_html_to_everyone();

		$this->assertTrue( current_user_can( 'unfiltered_html' ) );
		$this->assertTrue( ( new TrackingSettings( new Settings() ) )->can_user_edit_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_let_a_blog_administrator_the_network_granted_unfiltered_html_change_the_tracking_code() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->make_current_user_blog_administrator();
		$this->grant_unfiltered_html_to_everyone();

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<script>console.log(1)</script>',
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_MANUALLY, $saved->get_global_option( 'track_mode' ) );
		$this->assertSame( '<script>console.log(1)</script>', $saved->get_js_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_not_let_a_blog_administrator_switch_to_the_manual_tracking_code_when_the_plugin_is_not_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->settings->apply_tracking_related_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT ] );

		$this->make_current_user_blog_administrator();

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<script>alert(/xss/)</script>',
				'noscript_code' => '<script>alert(/xss/)</script>',
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_DEFAULT, $saved->get_global_option( 'track_mode' ) );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_js_tracking_code() );
		$this->assertStringNotContainsString( 'alert(/xss/)', $saved->get_noscript_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_keep_the_tracking_code_a_network_administrator_entered_when_the_plugin_is_not_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<!-- set by the network administrator -->',
				'noscript_code' => '<!-- noscript set by the network administrator -->',
			]
		);

		$this->make_current_user_blog_administrator();

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<script>alert(/xss/)</script>',
				'noscript_code' => '<script>alert(/xss/)</script>',
			]
		);

		$saved = new Settings();

		// left alone rather than blanked, so submitting the page does not wipe what is configured
		$this->assertSame( '<!-- set by the network administrator -->', $saved->get_js_tracking_code() );
		$this->assertSame( '<!-- noscript set by the network administrator -->', $saved->get_noscript_tracking_code() );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_keep_the_tracking_code_a_network_administrator_entered_when_the_submission_leaves_the_track_mode_out() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->submit_tracking_settings(
			[
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<!-- set by the network administrator -->',
				'noscript_code' => '<!-- noscript set by the network administrator -->',
			]
		);

		$this->make_current_user_blog_administrator();

		// no track_mode field supplied at all. the mode stays as manual, so whatever is stored keeps being
		// served. we check that the tracking code is not overwritten with new values in this case.
		$this->submit_tracking_settings(
			[
				'tracking_code' => '<script>alert(/xss/)</script>',
				'noscript_code' => '<script>alert(/xss/)</script>',
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_MANUALLY, $saved->get_global_option( 'track_mode' ) );
		$this->assertSame( '<!-- set by the network administrator -->', $saved->get_js_tracking_code() );
		$this->assertSame( '<!-- noscript set by the network administrator -->', $saved->get_noscript_tracking_code() );
	}

	/**
	 * @dataProvider get_unrecognised_track_modes
	 */
	public function test_show_settings_should_not_store_a_track_mode_it_does_not_recognise( $track_mode ) {
		$this->settings->apply_tracking_related_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT ] );

		$this->submit_tracking_settings(
			[
				'track_mode' => $track_mode,
				'track_404'  => true,
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_DEFAULT, $saved->get_global_option( 'track_mode' ) );

		// the rest of the submission is still saved, only the mode is dropped
		$this->assertEquals( true, $saved->get_global_option( 'track_404' ) );
	}

	/**
	 * @dataProvider get_unrecognised_track_modes
	 */
	public function test_show_settings_should_still_remove_the_slashes_from_a_tracking_code_submitted_with_an_unrecognised_track_mode( $track_mode ) {
		$this->settings->apply_tracking_related_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_MANUALLY ] );

		$tracking_code = '<!-- Matomo --><script>var matomoUrl = "//example.org/";</script>';

		$this->submit_tracking_settings(
			[
				'track_mode'    => $track_mode,
				'tracking_code' => addslashes( $tracking_code ),
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_MANUALLY, $saved->get_global_option( 'track_mode' ) );
		$this->assertSame( $tracking_code, $saved->get_option( 'tracking_code' ) );
	}

	public function get_unrecognised_track_modes() {
		return [
			'differs from a known mode only in case' => [ 'Manually' ],
			'is not a mode at all'                   => [ 'foobar' ],
			'is empty'                               => [ '' ],
		];
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_still_let_a_blog_administrator_change_the_other_tracking_settings_when_the_plugin_is_not_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->make_current_user_blog_administrator();

		$this->submit_tracking_settings(
			[
				'track_mode'        => TrackingSettings::TRACK_MODE_DEFAULT,
				'track_404'         => true,
				'track_search'      => true,
				'track_js_endpoint' => 'restapi',
			]
		);

		$saved = new Settings();

		$this->assertSame( TrackingSettings::TRACK_MODE_DEFAULT, $saved->get_global_option( 'track_mode' ) );
		$this->assertEquals( true, $saved->get_global_option( 'track_404' ) );
		$this->assertEquals( true, $saved->get_global_option( 'track_search' ) );
		$this->assertSame( 'restapi', $saved->get_global_option( 'track_js_endpoint' ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_offer_the_manual_mode_and_its_code_fields_as_disabled_to_a_blog_administrator_when_the_plugin_is_not_network_activated() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->make_current_user_blog_administrator();

		ob_start();
		$this->tracking_settings->show_settings();
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<input type="radio" id="track_mode_manually"[^>]*disabled="disabled"/', $output );
		$this->assertMatchesRegularExpression( '/<textarea[^>]*id="tracking_code"[^>]*readonly="readonly"/', $output );
		$this->assertMatchesRegularExpression( '/<textarea[^>]*id="noscript_code"[^>]*readonly="readonly"/', $output );

		// the settings that only feed the generated code stay editable
		$this->assertMatchesRegularExpression( '/<input type="radio" id="track_mode_default"(?![^>]*disabled)/', $output );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_settings_should_offer_the_manual_mode_and_its_code_fields_to_a_network_administrator() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		ob_start();
		$this->tracking_settings->show_settings();
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<input type="radio" id="track_mode_manually"(?![^>]*disabled)/', $output );
		$this->assertDoesNotMatchRegularExpression( '/<textarea[^>]*id="tracking_code"[^>]*readonly="readonly"/', $output );
		$this->assertDoesNotMatchRegularExpression( '/<textarea[^>]*id="noscript_code"[^>]*readonly="readonly"/', $output );
	}

	/**
	 * An administrator of a blog on a multisite where each blog activates the plugin for itself, so
	 * the tracking settings are that blog's own and theirs to change. Not the network activated case,
	 * which is make_current_user_blog_administrator_of_the_network_activated_install().
	 *
	 * @return int
	 */
	private function make_current_user_blog_administrator() {
		// multisite, but not network activated
		update_site_option( 'active_sitewide_plugins', [] );
		update_option( 'active_plugins', [ 'matomo/matomo.php' ] );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		add_user_to_blog( get_current_blog_id(), $user_id, 'administrator' );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );
		$this->assertFalse( current_user_can( 'manage_network_options' ) );

		// what WordPress itself denies them, and the reason for all of the above
		$this->assertFalse( current_user_can( 'unfiltered_html' ) );

		$this->settings = new Settings();
		$this->assertFalse( $this->settings->is_network_enabled() );

		$this->tracking_settings = new TrackingSettings( $this->settings );

		return $user_id;
	}

	/**
	 * The population AS-702 created: an administrator of a blog on a network activated install. They
	 * hold Matomo super user access through the administrator role rather than through a Matomo
	 * capability of their own, which is a different branch of
	 * Capabilities::has_matomo_super_user_capability() than the Matomo Super User role takes.
	 *
	 * Assumes the plugin is already network activated, so that a test can set up the settings it
	 * expects to find unchanged in the store the network reads them from.
	 *
	 * @return int
	 */
	private function make_current_user_blog_administrator_of_the_network_activated_install() {
		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		add_user_to_blog( get_current_blog_id(), $user_id, 'administrator' );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );
		$this->assertFalse( current_user_can( 'manage_network_options' ) );

		// the access this branch grants them, and the reason for the tests that use this
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$this->settings = new Settings();
		$this->assertTrue( $this->settings->is_network_enabled() );

		$this->tracking_settings = new TrackingSettings( $this->settings );

		return $user_id;
	}

	private function grant_unfiltered_html_to_everyone() {
		$this->unfiltered_html_grant = function ( $caps, $cap ) {
			return 'unfiltered_html' === $cap ? [ 'unfiltered_html' ] : $caps;
		};

		add_filter( 'map_meta_cap', $this->unfiltered_html_grant, 20, 2 );
	}

	private function submit_tracking_settings( $form_values ) {
		$this->fake_request( $form_values );

		ob_start();
		try {
			// a new instance, so that it reads the settings again rather than what the tests set up
			( new TrackingSettings( new Settings() ) )->show_settings();
		} finally {
			$output = ob_get_clean();

			$_POST    = [];
			$_REQUEST = [];
		}

		return $output;
	}

	private function delete_temp_wp_config() {
		if ( is_file( $this->get_wp_config_path() ) ) {
			unlink( $this->get_wp_config_path() );
		}
	}

	private function get_wp_config_path() {
		return __DIR__ . '/wp-config.php';
	}
}
