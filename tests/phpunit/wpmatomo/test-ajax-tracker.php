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

/**
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 */
class AjaxTrackerTest extends MatomoAnalytics_TestCase {

	use MatomoWooCommerceAwareTest;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var null|int
	 */
	private $blogid;

	private $old_referrer;

	public function setUp(): void {
		parent::setUp();

		$_COOKIE            = [];
		$this->blogid       = null;
		$this->old_referrer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : null;
		unset( $_SERVER['HTTP_REFERER'] );

		$this->manually_load_woocommerce();
		$this->disable_woocommerce_cookies();

		$this->settings = new Settings();
	}

	public function tearDown(): void {
		if ( $this->old_referrer ) {
			$_SERVER['HTTP_REFERER'] = $this->old_referrer;
		}

		unset( $_SERVER['HTTP_SEC_PURPOSE'] );

		$this->unset_wc_session();

		if ( $this->blogid ) {
			wpmu_delete_blog( $this->blogid );
		}

		parent::tearDown();
	}

	public function test_construct_when_no_matomo_site_id() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'multisite test' );
			return;
		}

		$idblog = $this->create_blog();
		switch_to_blog( $idblog );

		$tracker = new AjaxTracker( $this->settings );

		$this->assertEmpty( $tracker->idSite );
		$this->assertEmpty( $tracker->pageUrl );
	}

	public function test_construct_when_using_rest_endpoint() {
		$this->settings->set_global_option( 'track_api_endpoint', 'restapi' );

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/index.php?rest_route=/matomo/v1/hit/', MatomoTracker::$URL );
		$this->assertEquals( false, $tracker->pageUrl );
	}

	public function test_construct_when_referrer_specified() {
		$_SERVER['HTTP_REFERER'] = 'http://whatever.com/path';

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/wp-content/plugins/matomo/app/matomo.php', MatomoTracker::$URL );
		$this->assertEquals( 'http://whatever.com/path', $tracker->pageUrl );
	}

	public function test_construct_when_cookies_are_disabled() {
		$this->settings->set_global_option( 'disable_cookies', true );

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/wp-content/plugins/matomo/app/matomo.php', MatomoTracker::$URL );
		$this->assertEquals( false, $tracker->pageUrl );
		$this->assertTrue( $tracker->configCookiesDisabled );
	}

	public function test_construct_when_cookies_are_enabled_and_cookie_exists() {
		$visitor_id               = '0123456789abcdef';
		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/wp-content/plugins/matomo/app/matomo.php', MatomoTracker::$URL );
		$this->assertEquals( false, $tracker->pageUrl );
		$this->assertFalse( $tracker->configCookiesDisabled );
		$this->assertEquals( $visitor_id, $tracker->cookieVisitorId );
		$this->assertEquals( $visitor_id, $tracker->forcedVisitorId );
	}

	public function test_construct_when_cookies_are_enabled_and_cookie_doesnt_exist() {
		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/wp-content/plugins/matomo/app/matomo.php', MatomoTracker::$URL );
		$this->assertEquals( false, $tracker->pageUrl );
		$this->assertFalse( $tracker->configCookiesDisabled );
		$this->assertEmpty( $tracker->cookieVisitorId );
		$this->assertEmpty( $tracker->forcedVisitorId );
	}

	public function test_construct_when_cookies_are_enabled_and_cookie_is_in_wc_session() {
		$visitor_id = '2223456789abcdef';

		$this->initialize_wc_session();
		WC()->session->set( \WpMatomo\Ecommerce\ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME, $visitor_id );

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEquals( 1, $tracker->idSite );
		$this->assertEquals( 'http://example.org/wp-content/plugins/matomo/app/matomo.php', MatomoTracker::$URL );
		$this->assertEquals( false, $tracker->pageUrl );
		$this->assertFalse( $tracker->configCookiesDisabled );
		$this->assertEmpty( $tracker->cookieVisitorId );
		$this->assertEquals( $visitor_id, $tracker->forcedVisitorId );
	}

	public function test_construct_when_cookies_are_enabled_and_cookie_value_is_invalid() {
		$visitor_id = 'blah';

		$_COOKIE['_pk_id_1_3678'] = $visitor_id . '.' . time();
		$tracker                  = new AjaxTracker( $this->settings );
		$this->assertEmpty( $tracker->cookieVisitorId );
		$this->assertEmpty( $tracker->forcedVisitorId );

		$_COOKIE = [];

		$this->initialize_wc_session();
		WC()->session->set( \WpMatomo\Ecommerce\ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME, $visitor_id );

		$tracker = new AjaxTracker( $this->settings );
		$this->assertEmpty( $tracker->cookieVisitorId );
		$this->assertEmpty( $tracker->forcedVisitorId );
	}

	/**
	 * @dataProvider get_sec_purpose_test_values
	 */
	public function test_ajax_tracker_with_sec_purpose_header( $header_value, $expected_requests ) {
		$tracker = new class( $this->settings ) extends AjaxTracker {
			public $sent_requests = [];

			protected function wp_remote_request( $url, $args ) {
				// remove random query params
				$url = preg_replace( '/&_id=[^&]+/', '', $url );
				$url = preg_replace( '/&r=[^&]+/', '', $url );
				$url = preg_replace( '/&_idts=[^&]+/', '', $url );
				$url = preg_replace( '/&pv_id=[^&]+/', '', $url );

				$this->sent_requests[] = $url;
				return null;
			}
		};

		if ( empty( $header_value ) ) { // test without sec-purpose
			unset( $_SERVER['HTTP_SEC_PURPOSE'] );
		} else {
			$_SERVER['HTTP_SEC_PURPOSE'] = $header_value;
		}

		$tracker->setUrl( 'https://testurl' );
		$tracker->doTrackPageView( 'test document' );

		$this->assertEquals( $expected_requests, $tracker->sent_requests );
	}

	public function get_sec_purpose_test_values() {
		return [
			[
				null,
				[
					'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&url=https%3A%2F%2Ftesturl&urlref=&action_name=test+document&bots=1',
				],
			],
			[
				'randomvalue',
				[
					'http://example.org/wp-content/plugins/matomo/app/matomo.php?idsite=1&rec=1&apiv=1&url=https%3A%2F%2Ftesturl&urlref=&action_name=test+document&bots=1',
				],
			],

			[
				'prefetch',
				[],
			],
			[
				'prefetch;prerender',
				[],
			],
			[
				'prerender',
				[],
			],
			[
				'astrangeprefetchvalue',
				[],
			],
		];
	}

	private function create_blog() {
		$this->blogid = self::factory()->blog->create();
		return $this->blogid;
	}
}
