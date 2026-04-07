<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestion;

/**
 * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
 */
class TestSuggestion extends Suggestion {

	public function __construct( $plugin_slug, $plugin_name = '' ) {
		$this->plugin_slug = $plugin_slug;
		$this->plugin_name = $plugin_name;
	}

	public function should_trigger() {
		return true;
	}

	public function init() {
		// empty
	}

	public function get_last_month_data( $method, $filter_limit = 500, $sort_by_column = 'label', $extra_params = [] ) {
		return parent::get_last_month_data( $method, $filter_limit, $sort_by_column, $extra_params );
	}

	public function is_plugin_installed( $plugin_slug ) {
		return parent::is_plugin_installed( $plugin_slug );
	}
}

class SuggestionTest extends MatomoAnalytics_TestCase {


	public function test_is_suggestion_applicable_returns_true_if_the_suggestion_plugin_is_not_installed() {
		$suggestion = new TestSuggestion( 'nonexistentplugin' );
		$this->assertTrue( $suggestion->is_suggestion_applicable() );
	}

	public function test_is_suggestion_applicable_returns_false_if_the_suggestion_plugin_is_installed() {
		$suggestion = new TestSuggestion( 'matomo' ); // using matomo plugin so active plugin check passes
		$this->assertFalse( $suggestion->is_suggestion_applicable() );
	}

	public function test_get_explore_url_returns_correct_default_url() {
		$suggestion = new TestSuggestion( 'UsersFlow' );
		$this->assertEquals( 'https://plugins.matomo.org/UsersFlow?wp=1&source=wordpress', $suggestion->get_explore_url() );
	}

	public function test_get_unlock_url_returns_correct_default_url() {
		$suggestion = new TestSuggestion( 'UsersFlow', 'Users Flow' );
		$this->assertEquals( 'http://example.org/wp-admin/admin.php?page=matomo-marketplace&tab=install&search=' . rawurlencode( 'Users Flow' ), $suggestion->get_unlock_url() );
	}

	public function test_get_last_month_data_fetches_the_specified_report_data_for_the_last_30_days() {
		\Piwik\Config::getInstance()->General['enable_browser_archiving_triggering'] = 1;

		$tracker = $this->make_local_tracker( ( new DateTime() )->sub( DateInterval::createFromDateString( '2 days' ) )->format( 'Y-m-d H:i:s' ), true );
		$tracker->setUrl( 'http://example.com/test/url' );

		$output = $tracker->doTrackPageView( 'test page' );
		$this->assert_tracking_response( $output );

		$suggestion = new TestSuggestion( 'SomePlugin' );
		$data       = $suggestion->get_last_month_data( 'VisitsSummary.get' );

		$this->assertInstanceOf( \Piwik\DataTable::class, $data );
		$this->assertEquals( 1, $data->getRowsCount() );
		$this->assertEquals( 1, $data->getFirstRow()->getColumn( 'nb_visits' ) );
		$this->assertInstanceOf( \Piwik\Period\Range::class, $data->getMetadata( 'period' ) );
		$this->assertEquals(
			( new DateTime() )->sub( DateInterval::createFromDateString( '30 days' ) )->format( 'Y-m-d' ) . ',' . ( new DateTime( 'yesterday' ) )->format( 'Y-m-d' ),
			$data->getMetadata( 'period' )->getRangeString()
		);
	}

	public function test_is_plugin_installed_returns_true_if_the_supplied_plugin_is_installed() {
		$suggestion = new TestSuggestion( 'SomePlugin' );
		$this->assertTrue( $suggestion->is_plugin_installed( 'matomo' ) );
	}

	public function test_is_plugin_installed_returns_false_if_the_supplied_plugin_is_not_installed() {
		$suggestion = new TestSuggestion( 'SomePlugin' );
		$this->assertFalse( $suggestion->is_plugin_installed( 'SomePlugin' ) );
	}

	public function test_get_short_id_returns_simple_class_name() {
		$suggestion = new TestSuggestion( 'SomePlugin' );
		$this->assertEquals( 'TestSuggestion', $suggestion->get_short_id() );
	}
}
