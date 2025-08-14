<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Admin\CookieConsent;
use WpMatomo\Settings;
use WpMatomo\TrackingCode\GeneratorOptions;
use WpMatomo\TrackingCode\TrackingCodeGenerator;

class TrackingCodeGeneratorTest extends MatomoUnit_TestCase {

	/**
	 * @var TrackingCodeGenerator
	 */
	private $tracking_code;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings = new Settings();
		$this->settings->set_global_option( 'track_js_endpoint', 'plugin' );
		$this->settings->save();

		WpMatomo\Site::map_matomo_site_id( get_current_blog_id(), 21 );
	}

	private function make_tracking_code() {
		$this->tracking_code = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
	}

	private function get_tracking_code() {
		$this->make_tracking_code();

		return $this->tracking_code->get_tracking_code();
	}

	public function test_get_tracking_code_when_tracking_is_disabled() {
		$this->assertSame( '', $this->get_tracking_code() );
	}

	public function test_get_tracking_code_when_using_default_tracking_code() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT,
			)
		);

		$cdata_start = "/* <![CDATA[ */\n";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$this->assertSame(
			'<!-- Matomo --><script ' . $this->get_type_attribute() . ">\n$cdata_start" . '(function () {
function initTracking() {
var _paq = window._paq = window._paq || [];
_paq.push([\'trackPageView\']);_paq.push([\'enableLinkTracking\']);_paq.push([\'alwaysUseSendBeacon\']);_paq.push([\'setTrackerUrl\', "\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.php"]);_paq.push([\'setSiteId\', \'21\']);var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
g.type=\'text/javascript\'; g.async=true; g.src="\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.js"; s.parentNode.insertBefore(g,s);
}
if (document.prerendering) {
	document.addEventListener(\'prerenderingchange\', initTracking, {once: true});
} else {
	initTracking();
}
})();' . "\n$cdata_end</script>\n<!-- End Matomo Code -->",
			$this->get_tracking_code()
		);
	}

	public function test_get_tracking_code_when_using_default_tracking_code_using_rest_api_and_other_features() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'                => TrackingSettings::TRACK_MODE_DEFAULT,
				'track_js_endpoint'         => 'restapi',
				'track_api_endpoint'        => 'restapi',
				'track_noscript'            => true,
				'track_content'             => 'all',
				'add_download_extensions'   => 'zip|waf',
				'set_link_classes'          => 'clickme|foo',
				'disable_cookies'           => true,
				'track_across'              => true,
				'track_crossdomain_linking' => true,
			)
		);

		$cdata_start = "/* <![CDATA[ */\n";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$this->assertSame(
			'<!-- Matomo --><script ' . $this->get_type_attribute() . '>' . "\n$cdata_start" . '(function () {
function initTracking() {
var _paq = window._paq = window._paq || [];
_paq.push([\'addDownloadExtensions\', "zip|waf"]);
_paq.push([\'setLinkClasses\', "clickme|foo"]);
if (!window._paq.find || !window._paq.find(function (m) { return m[0] === "disableCookies"; })) {
	window._paq.push(["disableCookies"]);
}
_paq.push([\'enableCrossDomainLinking\']);
_paq.push(["setCookieDomain", "*.example.org"]);
_paq.push([\'trackAllContentImpressions\']);_paq.push([\'trackPageView\']);_paq.push([\'enableLinkTracking\']);_paq.push([\'alwaysUseSendBeacon\']);_paq.push([\'setTrackerUrl\', "\/\/example.org\/index.php?rest_route=\/matomo\/v1\/hit\/"]);_paq.push([\'setSiteId\', \'21\']);var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
g.type=\'text/javascript\'; g.async=true; g.src="\/\/example.org\/index.php?rest_route=\/matomo\/v1\/hit\/"; s.parentNode.insertBefore(g,s);
}
if (document.prerendering) {
	document.addEventListener(\'prerenderingchange\', initTracking, {once: true});
} else {
	initTracking();
}
})();' . "\n$cdata_end</script>\n<!-- End Matomo Code -->",
			$this->get_tracking_code()
		);
	}

	public function test_get_tracker_endpoint() {
		$this->make_tracking_code();
		$this->assertSame(
			'//example.org/wp-content/plugins/matomo/app/matomo.php',
			$this->tracking_code->get_tracker_endpoint()
		);

		$this->settings->apply_tracking_related_changes(
			array(
				'track_api_endpoint' => 'restapi',
			)
		);

		$this->assertSame(
			'//example.org/index.php?rest_route=/matomo/v1/hit/',
			$this->tracking_code->get_tracker_endpoint()
		);

		$this->settings->apply_tracking_related_changes(
			array(
				'force_protocol' => 'https',
			)
		);

		$this->assertSame(
			'https://example.org/index.php?rest_route=/matomo/v1/hit/',
			$this->tracking_code->get_tracker_endpoint()
		);
	}

	public function test_get_js_endpoint() {
		$this->make_tracking_code();
		$this->assertSame(
			'//example.org/wp-content/plugins/matomo/app/matomo.js',
			$this->tracking_code->get_js_endpoint()
		);

		$this->settings->apply_tracking_related_changes(
			array(
				'track_js_endpoint' => 'restapi',
			)
		);

		$this->assertSame(
			'//example.org/index.php?rest_route=/matomo/v1/hit/',
			$this->tracking_code->get_js_endpoint()
		);

		$this->settings->apply_tracking_related_changes(
			array(
				'force_protocol' => 'https',
			)
		);

		$this->assertSame(
			'https://example.org/index.php?rest_route=/matomo/v1/hit/',
			$this->tracking_code->get_js_endpoint()
		);
	}

	public function test_get_tracking_code_test_user_id() {
		$id1 = self::factory()->user->create();

		wp_set_current_user( $id1 );
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'    => TrackingSettings::TRACK_MODE_DEFAULT,
				'track_user_id' => 'uid',
			)
		);
		$this->assertStringContainsString( "_paq.push(['setUserId', '$id1']);", $this->get_tracking_code() );
	}

	public function test_get_tracking_code_when_using_manually_tracking_code() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'    => TrackingSettings::TRACK_MODE_MANUALLY,
				'tracking_code' => '<script>foobar</script>',
			)
		);
		$this->assertSame( '<script>foobar</script>', $this->get_tracking_code() );
	}

	public function test_get_tracking_code_when_using_tagmanager_mode() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'              => TrackingSettings::TRACK_MODE_TAGMANAGER,
				'tagmanger_container_ids' => array(
					'abcdefgh' => 1,
					'cfk3jjw'  => 0,
				),
			)
		);

		if ( is_multisite() ) {
			$this->assertSame( '<!-- Matomo: no supported track_mode selected -->', $this->get_tracking_code() );
		} else {
			$this->assertSame(
				'<!-- Matomo Tag Manager -->
<script >
var _mtm = _mtm || [];
_mtm.push({\'mtm.startTime\': (new Date().getTime()), \'event\': \'mtm.Start\'});
var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
g.type=\'text/javascript\'; g.async=true; g.src="http://example.org/wp-content/uploads/matomo/container_abcdefgh.js"; s.parentNode.insertBefore(g,s);
</script><!-- End Matomo Tag Manager -->',
				$this->get_tracking_code()
			);
		}
	}

	public function test_get_tracking_code_when_using_tagmanager_mode_and_no_containers() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'              => TrackingSettings::TRACK_MODE_TAGMANAGER,
				'tagmanger_container_ids' => array(),
			)
		);
		if ( is_multisite() ) {
			$this->assertSame( '<!-- Matomo: no supported track_mode selected -->', $this->get_tracking_code() );
		} else {
			$this->assertSame( '<!-- Matomo Tag Manager --><!-- End Matomo Tag Manager -->', $this->get_tracking_code() );
		}
	}

	public function test_cookie_consent_tagmanager() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => TrackingSettings::TRACK_MODE_TAGMANAGER,
				'cookie_consent' => CookieConsent::REQUIRE_COOKIE_CONSENT,
			)
		);
		$this->assertStringNotContainsString( 'requireCookieConsent', $this->get_tracking_code() );
		$this->assertStringNotContainsString( 'requireConsent', $this->get_tracking_code() );
	}

	public function test_cookie_consent_manually() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => TrackingSettings::TRACK_MODE_MANUALLY,
				'cookie_consent' => CookieConsent::REQUIRE_COOKIE_CONSENT,
			)
		);
		$this->assertStringNotContainsString( 'requireCookieConsent', $this->get_tracking_code() );
		$this->assertStringNotContainsString( 'requireConsent', $this->get_tracking_code() );
	}

	public function test_cookie_consent_none() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => TrackingSettings::TRACK_MODE_DEFAULT,
				'cookie_consent' => CookieConsent::REQUIRE_NONE,
			)
		);
		$this->assertStringNotContainsString( 'requireCookieConsent', $this->get_tracking_code() );
		$this->assertStringNotContainsString( 'requireConsent', $this->get_tracking_code() );
	}

	public function test_cookie_consent_cookie() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => TrackingSettings::TRACK_MODE_DEFAULT,
				'cookie_consent' => CookieConsent::REQUIRE_COOKIE_CONSENT,
			)
		);
		$this->assertStringContainsString( "_paq.push(['requireCookieConsent']);", $this->get_tracking_code() );
	}

	public function test_cookie_consent_tracking() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => TrackingSettings::TRACK_MODE_DEFAULT,
				'cookie_consent' => CookieConsent::REQUIRE_TRACKING_CONSENT,
			)
		);
		$this->assertStringContainsString( "_paq.push(['requireConsent']);", $this->get_tracking_code() );
	}

	public function test_get_tracking_cookie_domain_no_cookie_domain() {
		$this->make_tracking_code();
		$this->assertSame( '', $this->tracking_code->get_tracking_cookie_domain() );
	}

	public function test_get_tracking_cookie_domain_returns_cookie_domain() {
		$this->make_tracking_code();
		$this->settings->set_global_option( 'track_across', true );
		$this->assertSame( '*.example.org', $this->tracking_code->get_tracking_cookie_domain() );
	}

	public function test_prepare_tracking_code_when_using_options_from_request() {
		$cdata_start = "/* <![CDATA[ */\n";
		$cdata_end   = "/* ]]> */\n";
		if ( $this->is_wordpress_not_using_cdata_tags() ) {
			$cdata_start = '';
			$cdata_end   = '';
		}

		$request = [
			'track_mode'      => TrackingSettings::TRACK_MODE_DEFAULT,
			'force_post'      => true,
			'track_heartbeat' => 72,
		];

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings, $request ) );

		$this->assertSame(
			[
				'script'   => '<!-- Matomo --><script ' . $this->get_type_attribute() . ">\n$cdata_start"
					. '(function () {
function initTracking() {
var _paq = window._paq = window._paq || [];
_paq.push([\'setRequestMethod\', \'POST\']);
_paq.push([\'enableHeartBeatTimer\', 72]);_paq.push([\'trackPageView\']);_paq.push([\'enableLinkTracking\']);_paq.push([\'alwaysUseSendBeacon\']);_paq.push([\'setTrackerUrl\', "\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.php"]);_paq.push([\'setSiteId\', \'1\']);var d=document, g=d.createElement(\'script\'), s=d.getElementsByTagName(\'script\')[0];
g.type=\'text/javascript\'; g.async=true; g.src="\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.js"; s.parentNode.insertBefore(g,s);
}
if (document.prerendering) {
	document.addEventListener(\'prerenderingchange\', initTracking, {once: true});
} else {
	initTracking();
}
})();' . "\n$cdata_end</script>\n<!-- End Matomo Code -->",
				'noscript' => '<noscript><p><img referrerpolicy="no-referrer-when-downgrade" src="//example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&amp;rec=1" style="border:0;" alt="" /></p></noscript>',
			],
			$generator->prepare_tracking_code( 1 )
		);
	}

	public function test_update_tracking_code_sets_tracking_code_if_not_generated() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_option( 'tracking_code', null );
		$this->settings->save();

		$this->settings->init_settings();
		$this->assertEmpty( $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'script', $result );
		$this->assertArrayHasKey( 'noscript', $result );
		$this->assertNotEmpty( $result['script'] );
		$this->assertIsString( $result['noscript'] );

		$this->settings->init_settings();

		$this->assertEquals( $result['script'], $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( $result['noscript'], $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_sets_tracking_code_if_generated_is_out_of_date() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->save();

		$this->set_tracking_code_out_of_date();

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'script', $result );
		$this->assertArrayHasKey( 'noscript', $result );
		$this->assertNotEmpty( $result['script'] );
		$this->assertIsString( $result['noscript'] );
		$this->assertNotEquals( 'blahblah', $result['script'] );

		$this->settings->init_settings();

		$this->assertEquals( $result['script'], $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( $result['noscript'], $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_does_nothing_if_generated_and_not_out_of_date() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->set_option( 'noscript_code', 'blahblah2' );
		$this->settings->save();

		$this->set_tracking_code_not_out_of_date();

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();
		$this->assertFalse( $result );

		$this->settings->init_settings();

		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( 'blahblah2', $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_sets_tracking_code_if_forced() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->set_option( 'noscript_code', 'blahblah2' );
		$this->settings->save();

		$this->set_tracking_code_not_out_of_date();

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code( true );

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( 'script', $result );
		$this->assertArrayHasKey( 'noscript', $result );
		$this->assertNotEmpty( $result['script'] );
		$this->assertIsString( $result['noscript'] );
		$this->assertNotEquals( 'blahblah', $result['script'] );
		$this->assertNotEquals( 'blahblah2', $result['noscript'] );

		$this->settings->init_settings();

		$this->assertEquals( $result['script'], $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( $result['noscript'], $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_does_nothing_if_tracking_is_not_enabled() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DISABLED );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->set_option( 'noscript_code', 'blahblah2' );
		$this->settings->save();

		$this->set_tracking_code_out_of_date();

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();
		$this->assertFalse( $result );

		$this->settings->init_settings();

		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( 'blahblah2', $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_does_nothing_if_tracking_mode_is_manual() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_MANUALLY );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->set_option( 'noscript_code', 'blahblah2' );
		$this->settings->save();

		$this->set_tracking_code_out_of_date();

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();
		$this->assertFalse( $result );

		$this->settings->init_settings();

		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( 'blahblah2', $this->settings->get_option( 'noscript_code' ) );
	}

	public function test_update_tracking_code_does_nothing_if_current_site_is_not_mapped() {
		$this->settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_option( 'tracking_code', 'blahblah' );
		$this->settings->set_option( 'noscript_code', 'blahblah2' );
		$this->settings->save();

		$this->set_tracking_code_out_of_date();

		WpMatomo\Site::map_matomo_site_id( get_current_blog_id(), null );

		$this->settings->init_settings();
		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );

		$generator = new TrackingCodeGenerator( $this->settings, new GeneratorOptions( $this->settings ) );
		$result    = $generator->update_tracking_code();
		$this->assertFalse( $result );

		$this->settings->init_settings();

		$this->assertEquals( 'blahblah', $this->settings->get_option( 'tracking_code' ) );
		$this->assertEquals( 'blahblah2', $this->settings->get_option( 'noscript_code' ) );
	}

	private function set_tracking_code_out_of_date() {
		$this->settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 5000 );
		$this->settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() );
		$this->settings->save();
	}

	private function set_tracking_code_not_out_of_date() {
		$this->settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() );
		$this->settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() - 5000 );
		$this->settings->save();
	}
}
