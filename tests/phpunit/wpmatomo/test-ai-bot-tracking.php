<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Settings;
use WpMatomo\AIBotTracking;

require_once __DIR__ . '/../framework/mocks/mock-ajax-tracker.php';

/**
 * @group only
 */
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

		$_SERVER['REQUEST_URI'] = 'https://mysite.com/some/page';

		$this->settings = $this->make_settings();
		$this->tracker  = $this->make_mock_tracker();

		AIBotTracking::set_is_ai_bot_tracked( false );

		$this->ai_bot_tracking = new AIBotTracking( $this->settings, $this->tracker );
	}

	public function test_should_track_current_page_returns_false_for_admin_pages() {
		$this->enable_ai_bot_tracking();

		$this->assume_admin_page();

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function test_should_track_current_page_returns_false_if_no_request_uri_is_set() {
		$this->enable_ai_bot_tracking();

		unset( $_SERVER['REQUEST_URI'] );

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	/**
	 * @dataProvider getTestDataForShouldTrackCurrentPageWithNonHtmlFiles
	 */
	public function test_should_track_current_page_should_return_false_for_non_html_files( $request_uri ) {
		$this->enable_ai_bot_tracking();

		$_SERVER['REQUEST_URI'] = $request_uri;

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

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/robots.txt';

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
	}

	public function test_should_track_current_page_should_return_false_for_sitemap_xml() {
		$this->enable_ai_bot_tracking();

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/folder/sitemap.xml';

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );
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

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/folder/';

		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertCount( 1, $this->tracker->captured_urls );

		$this->tracker->captured_urls = [];
		$this->ai_bot_tracking->do_ai_bot_tracking();
		$this->assertCount( 0, $this->tracker->captured_urls );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_current_page_should_not_be_tracked() {
		$this->enable_ai_bot_tracking();
		$this->set_ai_bot_user_agent();

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/robots.txt';

		$should_track = $this->ai_bot_tracking->should_track_current_page();
		$this->assertFalse( $should_track );

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
	}

	public function test_do_ai_bot_tracking_should_do_nothing_if_current_user_agent_is_not_for_ai_bot() {
		$this->enable_ai_bot_tracking();

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/some/page';

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$this->assertEmpty( $this->tracker->captured_requests );
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

		$_SERVER['REQUEST_URI'] = 'https://somesite.com/some/page';

		$this->ai_bot_tracking->do_ai_bot_tracking();

		$expected = [
			[
				'https://matomo.mysite.com/matomo.php?idsite=1&rec=1&apiv=1&_idts=&_id=&url=&urlref=&http_status=200&pf_srv=REMOVED&source=wordpress&bots=1',
				[
					'method' => 'GET',
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
		$this->settings->set_global_option( Settings::TRACK_AI_BOTS, true );
		$this->settings->save();
	}

	private function set_ai_bot_user_agent() {
		$user_agent = 'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0; +Claude-User@anthropic.com)';

		$_SERVER['HTTP_USER_AGENT'] = $user_agent;
		$this->tracker->setUserAgent( $user_agent );
	}
}
