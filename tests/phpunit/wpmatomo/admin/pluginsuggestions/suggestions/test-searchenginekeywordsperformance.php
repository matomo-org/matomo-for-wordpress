<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use Piwik\Common;
use WpMatomo\Admin\PluginSuggestions\Suggestions\SearchEngineKeywordsPerformance;

class SearchEngineKeywordsPerformanceTest extends MatomoAnalytics_TestCase {

	public function setUp(): void {
		parent::setUp();

		$this->skip_if_old_wordpress();

		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;
		$this->create_set_super_admin();
	}

	public function test_should_trigger_returns_true_if_visits_from_search_engines_is_high_enough() {
		$this->track_data( 4, 3 );

		$suggestion = new SearchEngineKeywordsPerformance();
		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function test_should_trigger_returns_false_if_visits_from_search_engines_is_low() {
		$this->track_data( 1, 3 );

		$suggestion = new SearchEngineKeywordsPerformance();
		$this->assertFalse( $suggestion->should_trigger() );
	}

	public function test_should_trigger_returns_false_if_no_data() {
		$this->assertEquals( 0, Piwik\Db::fetchOne( 'SELECT COUNT(*) FROM ' . Common::prefixTable( 'log_visit' ) ) );

		$suggestion = new SearchEngineKeywordsPerformance();
		$this->assertFalse( $suggestion->should_trigger() );
	}

	private function track_data( $search_engine_visits, $direct_visits ) {
		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );

		for ( $i = 0; $i < $search_engine_visits; ++$i ) {
			$tracker->setNewVisitorId();
			$tracker->setIp( '23.44.98.' . $i );
			$tracker->setUrl( 'http://example.com/some/page' );
			$tracker->setUrlReferrer( 'https://google.com/search' );
			$output = $tracker->doTrackPageView( 'some page' );
			$this->assert_tracking_response( $output );
		}

		for ( $i = 0; $i < $direct_visits; ++$i ) {
			$tracker->setNewVisitorId();
			$tracker->setIp( '23.44.99.' . $i );
			$tracker->setUrl( 'http://example.com/some/page' );
			$tracker->setUrlReferrer( '' );
			$output = $tracker->doTrackPageView( 'some page' );
			$this->assert_tracking_response( $output );
		}
	}
}
