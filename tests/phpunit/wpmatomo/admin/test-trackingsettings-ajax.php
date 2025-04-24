<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Site;

class AdminTrackingSettingsAjaxTest extends MatomoUnit_Ajax_TestCase {
	public function setUp(): void {
		parent::setUp();
		TrackingSettings::register_ajax();
		$this->wordpress_fixture->switch_to_admin_page();
		$this->settings = new Settings();
		$this->settings->set_global_option( 'track_js_endpoint', 'plugin' );
		$this->settings->save();
	}

	public function test_generate_tracking_code_fails_if_an_incorrect_nonce_is_given() {
		wp_create_nonce( TrackingSettings::NONCE_NAME_GENERATE_TRACKING_CODE_AJAX );

		try {
			$this->call_ajax( 'matomo_generate_tracking_code', [], [ '_ajax_nonce' => 'garbagevalue' ] );
			$this->fail( 'ajax method did not fail as expected' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertEquals( '-1', $e->getMessage() ); // see check_ajax_referer()
		}
	}

	public function test_generate_tracking_code_returns_generated_code_using_settings() {
		$nonce = wp_create_nonce( TrackingSettings::NONCE_NAME_GENERATE_TRACKING_CODE_AJAX );

		$response = $this->call_ajax( 'matomo_generate_tracking_code', [], [ '_ajax_nonce' => $nonce ] );

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		$space = '';
		if ( $this->is_wordpress5() ) {
			$space = ' ';
		}

		$expected_js_code = <<<JS
<!-- Matomo --><script{$space}>
(function () {
function initTracking() {
var _paq = window._paq = window._paq || [];
_paq.push(['trackPageView']);_paq.push(['enableLinkTracking']);_paq.push(['alwaysUseSendBeacon']);_paq.push(['setTrackerUrl', "\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.php"]);_paq.push(['setSiteId', '$idsite']);var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
g.type='text/javascript'; g.async=true; g.src="\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.js"; s.parentNode.insertBefore(g,s);
}
if (document.prerendering) {
	document.addEventListener('prerenderingchange', initTracking, {once: true});
} else {
	initTracking();
}
})();
</script>
<!-- End Matomo Code -->
JS;

		$expected_noscript_code = <<<JS
<noscript><p><img referrerpolicy="no-referrer-when-downgrade" src="//example.org/wp-content/plugins/matomo/app/matomo.php?idsite=$idsite&amp;rec=1" style="border:0;" alt="" /></p></noscript>
JS;

		$this->assertEquals(
			[
				'script'   => $expected_js_code,
				'noscript' => $expected_noscript_code,
			],
			$response
		);
	}

	public function test_generate_tracking_code_returns_generated_code_with_overrides_from_request() {
		$nonce = wp_create_nonce( TrackingSettings::NONCE_NAME_GENERATE_TRACKING_CODE_AJAX );

		$response = $this->call_ajax(
			'matomo_generate_tracking_code',
			[],
			[
				'_ajax_nonce'     => $nonce,
				'track_mode'      => TrackingSettings::TRACK_MODE_DEFAULT,
				'force_post'      => true,
				'track_heartbeat' => 72,
			]
		);

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		$space = '';
		if ( $this->is_wordpress5() ) {
			$space = ' ';
		}

		$expected_js_code = <<<JS
<!-- Matomo --><script{$space}>
(function () {
function initTracking() {
var _paq = window._paq = window._paq || [];
_paq.push(['setRequestMethod', 'POST']);
_paq.push(['enableHeartBeatTimer', 72]);_paq.push(['trackPageView']);_paq.push(['enableLinkTracking']);_paq.push(['alwaysUseSendBeacon']);_paq.push(['setTrackerUrl', "\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.php"]);_paq.push(['setSiteId', '$idsite']);var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
g.type='text/javascript'; g.async=true; g.src="\/\/example.org\/wp-content\/plugins\/matomo\/app\/matomo.js"; s.parentNode.insertBefore(g,s);
}
if (document.prerendering) {
	document.addEventListener('prerenderingchange', initTracking, {once: true});
} else {
	initTracking();
}
})();
</script>
<!-- End Matomo Code -->
JS;

		$expected_noscript_code = <<<JS
<noscript><p><img referrerpolicy="no-referrer-when-downgrade" src="//example.org/wp-content/plugins/matomo/app/matomo.php?idsite=$idsite&amp;rec=1" style="border:0;" alt="" /></p></noscript>
JS;

		$this->assertEquals(
			[
				'script'   => $expected_js_code,
				'noscript' => $expected_noscript_code,
			],
			$response
		);
	}

	private function is_wordpress5() {
		$version = getenv( 'WORDPRESS_VERSION' );
		return 'latest' !== $version
			&& 'trunk' !== $version
			&& version_compare( $version, '5.2', '<=' );
	}
}
