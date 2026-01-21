<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\AjaxTracker;
use WpMatomo\Settings;
use WpMatomo\AIBotTracking;

require_once __DIR__ . '/../framework/mocks/mock-ajax-tracker.php';

class AIBotTrackingTest extends \MatomoUnit_TestCase {

	/**
	 * @var AIBotTracking
	 */
	private $ai_bot_tracking;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var TestAjaxTracker
	 */
	private $tracker;

	public function setUp(): void {
		parent::setUp();

		$this->set_current_url( 'https://mysite.com/some/page' );

		$this->settings = $this->make_settings();
		$this->tracker  = $this->make_mock_tracker();

		AIBotTracking::set_is_ai_bot_tracked( false );

		$this->ai_bot_tracking = new AIBotTracking( $this->settings, $this->tracker );

		unset( $GLOBALS['MATOMO_IN_AI_ESI'] );
		unset( $_COOKIE['matomo_has_js'] );
	}

	public function tearDown(): void {
		unset( $GLOBALS['MATOMO_IN_AI_ESI'] );
		unset( $_COOKIE['matomo_has_js'] );

		parent::tearDown();
	}

	public function test_should_track_current_page_returns_false_for_admin_pages() {
		$this->enable_ai_bot_tracking();

		$this->assume_admin_page();

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function test_should_track_current_page_returns_false_if_no_request_uri_is_set() {
		$this->enable_ai_bot_tracking();

		$this->unset_current_url();

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	/**
	 * @dataProvider getTestDataForShouldTrackCurrentPageWithNonHtmlFiles
	 */
	public function test_should_track_current_page_should_return_false_for_non_html_files( $request_uri ) {
		$this->enable_ai_bot_tracking();

		$this->set_current_url( $request_uri );

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function getTestDataForShouldTrackCurrentPageWithNonHtmlFiles() {
		return [
			[ 'https://mysite.com/path/to/image.png' ],
			[ 'https://mysite.com/path/to/image.jpg' ],
			[ '/path/to/image.wepb' ],
			[ '/path/to/myfile.pdf' ],
		];
	}

	public function test_should_track_current_page_should_return_false_for_robots_txt() {
		$this->enable_ai_bot_tracking();

		$this->set_current_url( 'https://somesite.com/robots.txt' );

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function test_should_track_current_page_should_return_false_for_sitemap_xml() {
		$this->enable_ai_bot_tracking();

		$this->set_current_url( 'https://somesite.com/folder/sitemap.xml' );

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_tracking_disabled() {
		$this->settings->set_global_option( 'track_mode', 'disabled' );
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS, true );
		$this->settings->save();

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/folder/';

		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertEmpty( $this->tracker->captured_urls );
	}

	public function test_do_ai_bot_tracking_should_return_false_if_tracking_enabled_but_ai_bot_tracking_disabled() {
		$this->settings->set_global_option( 'track_mode', 'disabled' );
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS, false );
		$this->settings->save();

		$this->set_current_url( 'https://somesite.com/folder/' );

		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertEmpty( $this->tracker->captured_urls );
	}

	/**
	 * @dataProvider getTestDataForShouldTrackCurrentPageWithWebPages
	 */
	public function test_should_track_current_page_should_return_true_for_normal_pages( $request_uri ) {
		$this->enable_ai_bot_tracking();

		$_SERVER['REQUEST_URI'] = $request_uri;

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertTrue( $should_track );
	}

	public function getTestDataForShouldTrackCurrentPageWithWebPages() {
		return [
			[ 'https://somesite.com/' ],
			[ 'https://somesite.com' ],
			[ 'https://somesite.com/my/page' ],
			[ 'https://somesite.com/my/page.html' ],
			[ 'https://somesite.com/my/page.htm' ],
		];
	}

	public function test_do_ai_bot_tracking_page_does_nothing_if_ai_tracking_already_done() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/folder/' );

		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertCount( 1, $this->tracker->captured_urls );

		$this->tracker->captured_urls = [];
		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertCount( 0, $this->tracker->captured_urls );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_current_page_should_not_be_tracked() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/robots.txt' );

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_current_user_agent_is_not_for_ai_bot() {
		$this->enable_ai_bot_tracking();

		$this->set_current_url( 'https://somesite.com/some/page' );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_should_output_esi_include_if_configured_to_use_esi() {
		$this->enable_ai_bot_tracking();
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS_USING_ESI, true );

		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/some/page' );

		ob_start();
		try {
			$this->ai_bot_tracking->do_ai_bot_tracking();
		} finally {
			$actual = ob_get_contents();
			ob_end_clean();
		}

		$actual = preg_replace( '/mtm_elapsed=\d+/', 'mtm_elapsed=REMOVED', $actual );

		$expected = '<esi:include src="https://example.org/wp-content/plugins/matomo/misc/track_ai_bot.php?mtm_esi=1&amp;mtm_url=https%3A%2F%2Fsomesite.com%2Fsome%2Fpage" cache-control="no-cache" />';

		$this->assertEquals( $expected, $actual );
	}

	public function test_do_ai_bot_tracking_should_not_output_esi_include_if_configured_but_within_standalone_track_ai_script() {
		$GLOBALS['MATOMO_IN_AI_ESI'] = true;

		$this->enable_ai_bot_tracking();
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS_USING_ESI, true );

		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/some/page' );

		ob_start();
		try {
			$this->ai_bot_tracking->do_ai_bot_tracking();
		} finally {
			$actual = ob_get_contents();
			ob_end_clean();
		}

		$this->assertEquals( '', $actual );

		$this->assertCount( 1, $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_ai_bot_tracking_is_not_enabled() {
		$this->set_ai_bot_user_agent();

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/some/page';

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_should_send_ai_tracking_request_if_feature_is_enabled_and_current_user_agent_is_ai_bot() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/some/page' );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$expected = [
			[
				'https://matomo.mysite.com/matomo.php?idsite=1&rec=1&apiv=1&_idts=&_id=&url=' . rawurlencode( 'https://somesite.com/some/page' ) . '&urlref=&recMode=1&http_status=200&pf_srv=REMOVED&source=wordpress&bots=1',
				[
					'method'   => 'GET',
					'headers'  => [
						'User-Agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)',
					],
					'blocking' => false,
				],
			],
		];

		$captured_requests = $this->tracker->captured_requests;
		foreach ( $captured_requests as &$captured ) {
			$captured[0] = preg_replace( '/&pf_srv=[^&]+/', '&pf_srv=REMOVED', $captured[0] );
		}

		$this->assertEquals( $expected, $captured_requests );
	}

	public function test_do_ai_bot_tracking_does_nothing_if_matomo_has_js_cookie_is_present() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();
		$this->set_has_js_cookie();

		$this->set_current_url( 'https://somesite.com/some/page' );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_adds_elapsed_time_tracking_url() {
		$_SERVER['REQUEST_TIME_FLOAT'] = microtime( true ) - 10;

		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/some/page2' );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$expected = [
			[
				'https://matomo.mysite.com/matomo.php?idsite=1&rec=1&apiv=1&_idts=&_id=&url=' . rawurlencode( 'https://somesite.com/some/page2' ) . '&urlref=&recMode=1&http_status=200&pf_srv=10000&source=wordpress&bots=1',
				[
					'method'   => 'GET',
					'headers'  => [
						'User-Agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)',
					],
					'blocking' => false,
				],
			],
		];

		$captured_requests = $this->tracker->captured_requests;
		foreach ( $captured_requests as &$captured ) {
			$captured[0] = preg_replace( '/&pf_srv=1000\d/', '&pf_srv=10000', $captured[0] );
		}

		$this->assertEquals( $expected, $captured_requests );
	}

	public function test_do_ai_bot_tracking_uses_custom_url_if_supplied() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$this->set_current_url( 'https://somesite.com/some/page' );

		$this->ai_bot_tracking->do_ai_bot_tracking( 'https://anothersite.com/page' );

		$expected = [
			[
				'https://matomo.mysite.com/matomo.php?idsite=1&rec=1&apiv=1&_idts=&_id=&url=' . rawurlencode( 'https://anothersite.com/page' ) . '&urlref=&recMode=1&http_status=200&pf_srv=REMOVED&source=wordpress&bots=1',
				[
					'method'   => 'GET',
					'headers'  => [
						'User-Agent' => 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)',
					],
					'blocking' => false,
				],
			],
		];

		$captured_requests = $this->tracker->captured_requests;
		foreach ( $captured_requests as &$captured ) {
			$captured[0] = preg_replace( '/&pf_srv=[^&]+/', '&pf_srv=REMOVED', $captured[0] );
		}

		$this->assertEquals( $expected, $captured_requests );
	}

	private function make_settings() {
		return new Settings();
	}

	private function make_mock_tracker() {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		MatomoTracker::$URL = 'https://matomo.mysite.com/';
		$tracker            = new TestAjaxTracker( $this->settings );
		$tracker->setIdSite( 1 );
		return $tracker;
	}

	private function enable_ai_bot_tracking() {
		$this->settings->set_global_option( 'track_mode', \WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT );
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS, true );
		$this->settings->save();
	}

	private function set_ai_bot_user_agent() {
		$user_agent = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)';

		$_SERVER['HTTP_USER_AGENT'] = $user_agent;
		$this->tracker->setUserAgent( $user_agent );
	}

	private function set_has_js_cookie() {
		$_COOKIE['matomo_has_js'] = '1';
	}

	private function set_current_url( $url ) {
		$url_parts = wp_parse_url( $url );

		$scheme = isset( $url_parts['scheme'] ) ? $url_parts['scheme'] : null;
		$host   = isset( $url_parts['host'] ) ? $url_parts['host'] : null;

		$_SERVER['HTTPS']        = 'https' === $scheme;
		$_SERVER['HTTP_HOST']    = $host;
		$_SERVER['PATH_INFO']    = $url_parts['path'];
		$_SERVER['QUERY_STRING'] = isset( $url_parts['query'] ) ? $url_parts['query'] : '';

		$_SERVER['REQUEST_URI'] = $url;
	}

	private function unset_current_url() {
		unset( $_SERVER['HTTPS'] );
		unset( $_SERVER['HTTP_HOST'] );
		unset( $_SERVER['PATH_INFO'] );
		unset( $_SERVER['QUERY_STRING'] );
		unset( $_SERVER['REQUEST_URI'] );
	}
}
