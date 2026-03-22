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
class AdminTrackingSettingsTest extends MatomoAnalytics_TestCase {

	/**
	 * @var TrackingSettings
	 */
	private $tracking_settings;

	/**
	 * @var Settings
	 */
	private $settings;

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

	private function delete_temp_wp_config() {
		if ( is_file( $this->get_wp_config_path() ) ) {
			unlink( $this->get_wp_config_path() );
		}
	}

	private function get_wp_config_path() {
		return __DIR__ . '/wp-config.php';
	}
}
