<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\PluginSuggestions;
use WpMatomo\Admin\PluginSuggestions\Suggestions\AdvertisingConversionExport;
use WpMatomo\Admin\PluginSuggestions\Suggestions\Funnels;
use WpMatomo\Admin\PluginSuggestions\Suggestions\HeatmapSessionRecording;

class PluginSuggestionsTest extends MatomoAnalytics_TestCase {

	public function setUp(): void {
		parent::setUp();

		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;
		$this->create_set_super_admin();
	}

	public function test_check_finds_the_first_applicable_suggestion_that_has_not_been_dismissed() {
		$this->create_goal(); // for Funnels suggestion
		$this->track_paid_traffic(); // for AdvertisingConversionExport function

		// set AdvertisingConversionExport as dismissed
		update_option(
			PluginSuggestions::DISMISSED_SUGGESTIONS_OPTION_NAME,
			[
				AdvertisingConversionExport::class,
			]
		);

		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->check();

		$triggered_suggestion = get_option( PluginSuggestions::SUGGESTION_TRIGGERED_OPTION_NAME );
		$this->assertEquals( Funnels::class, $triggered_suggestion );
	}

	public function test_check_does_nothing_if_no_suggestion_is_applicable_or_should_trigger() {
		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->check();

		$triggered_suggestion = get_option( PluginSuggestions::SUGGESTION_TRIGGERED_OPTION_NAME );
		$this->assertEmpty( $triggered_suggestion );
	}

	public function test_check_does_nothing_if_all_suggestions_are_dismissed() {
		$this->create_goal(); // for Funnels suggestion
		$this->track_paid_traffic(); // for AdvertisingConversionExport function

		// set AdvertisingConversionExport as dismissed
		update_option(
			PluginSuggestions::DISMISSED_SUGGESTIONS_OPTION_NAME,
			[
				AdvertisingConversionExport::class,
				Funnels::class,
			]
		);

		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->check();

		$triggered_suggestion = get_option( PluginSuggestions::SUGGESTION_TRIGGERED_OPTION_NAME );
		$this->assertEmpty( $triggered_suggestion );
	}

	public function test_dismiss_suggestion_does_nothing_if_suggestion_id_is_invalid() {
		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->dismiss_suggestion( 'invalidsuggestion' );

		$dismissed_suggestions = get_option( PluginSuggestions::DISMISSED_SUGGESTIONS_OPTION_NAME );
		$this->assertEmpty( $dismissed_suggestions );
	}

	public function test_dismiss_suggestion_adds_a_suggestion_to_the_dismissed_list_when_suggestion_is_valid() {
		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->dismiss_suggestion( 'AdvertisingConversionExport' );
		$plugin_suggestions->dismiss_suggestion( 'HeatmapSessionRecording' );

		$dismissed_suggestions = get_option( PluginSuggestions::DISMISSED_SUGGESTIONS_OPTION_NAME );
		$this->assertEquals( [ AdvertisingConversionExport::class, HeatmapSessionRecording::class ], $dismissed_suggestions );
	}

	public function test_dismiss_suggestion_works_if_the_suggestion_is_already_dismissed() {
		$plugin_suggestions = new PluginSuggestions();
		$plugin_suggestions->dismiss_suggestion( 'AdvertisingConversionExport' );
		$plugin_suggestions->dismiss_suggestion( 'HeatmapSessionRecording' );
		$plugin_suggestions->dismiss_suggestion( 'AdvertisingConversionExport' );
		$plugin_suggestions->dismiss_suggestion( 'AdvertisingConversionExport' );
		$plugin_suggestions->dismiss_suggestion( 'HeatmapSessionRecording' );

		$dismissed_suggestions = get_option( PluginSuggestions::DISMISSED_SUGGESTIONS_OPTION_NAME );
		$this->assertEquals( [ AdvertisingConversionExport::class, HeatmapSessionRecording::class ], $dismissed_suggestions );
	}

	private function create_goal() {
		\Piwik\Plugins\Goals\API::getInstance()->addGoal( 1, 'test goal', 'url', 'http', 'contains' );
	}

	private function track_paid_traffic() {
		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );
		$tracker->setUrl( 'http://example.com/test/url?gclid=123&param=def' );

		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );

		$tracker->setUrl( 'http://example.com/another/url' ); // so it's not counted as a bounce
		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );
	}
}
