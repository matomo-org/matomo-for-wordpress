<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestions\AdvertisingConversionExport;

class AdvertisingConversionExportTest extends MatomoAnalytics_TestCase {

	public function test_should_trigger_returns_false_when_no_tracked_url_has_a_clickid() {
		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;

		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );
		$tracker->setUrl( 'http://example.com/test/url' );

		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );

		$suggestion = new AdvertisingConversionExport();
		$this->assertFalse( $suggestion->should_trigger() );
	}

	/**
	 * @dataProvider get_test_data_for_should_trigger
	 */
	public function test_should_trigger_returns_true_when_at_least_one_tracked_url_has_a_clickid( $url ) {
		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;

		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );
		$tracker->setUrl( $url );

		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );

		$suggestion = new AdvertisingConversionExport();
		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function get_test_data_for_should_trigger() {
		return [
			[ 'http://example.com/test/url?msclkid=123' ],
			[ 'http://example.com/test/url?param=abc&fbclid=123' ],
			[ 'http://example.com/test/url?gclid=123&param=def' ],
			[ 'http://example.com/test/url?li_fat_id=123' ],
			[ 'http://example.com/test/url?yclid=123' ],
		];
	}
}
