<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestions\HeatmapSessionRecording;

class HeatmapSessionRecordingTest extends MatomoAnalytics_TestCase {

	public function setUp(): void {
		parent::setUp();

		$this->skip_if_old_wordpress();

		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;
		$this->create_set_super_admin();
	}

	public function test_should_trigger_returns_true_if_bounce_rate_is_high_enough() {
		$this->track_test_data();

		$suggestion = new HeatmapSessionRecording( 0 );
		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function test_should_trigger_returns_false_if_bounce_rate_is_not_high_enough() {
		$this->track_test_data( 1 );

		$suggestion = new HeatmapSessionRecording( 0 );
		$this->assertFalse( $suggestion->should_trigger() );
	}

	/**
	 * @group only
	 */
	public function test_get_trigger_desc_long_includes_bounce_rate_in_text() {
		$this->track_test_data();

		$suggestion = new HeatmapSessionRecording( 0 );
		$suggestion->init();
		$this->assertEquals( 'Bounce rate is 75% — above average', $suggestion->get_trigger_desc_long() );
	}

	private function track_test_data( $bounce_count = 3 ) {
		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );

		// track bounces
		for ( $i = 0; $i < $bounce_count; ++$i ) {
			$tracker->setNewVisitorId();
			$tracker->setIp( '34.57.23.' . $i );
			$tracker->setUrl( 'http://example.com/test/bounce' . $bounce_count );
			$output = $tracker->doTrackPageView( 'test page' );
			$this->assert_tracking_response( $output );
		}

		// 1 visit with two actions
		$tracker->setNewVisitorId();
		$tracker->setIp( '34.57.25.4' );
		$tracker->setUrl( 'http://example.com/test/nobounce' );
		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tracker->setForceVisitDateTime( \Piwik\Date::factory( $tracker->forcedDatetime )->addHour( 0.1 )->getDatetime() );
		$tracker->setUrl( 'http://example.com/test/nobounce2' );
		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );
	}
}
