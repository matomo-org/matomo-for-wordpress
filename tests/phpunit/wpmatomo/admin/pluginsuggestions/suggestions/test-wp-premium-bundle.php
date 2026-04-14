<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\PluginSuggestions\Suggestions\WpPremiumBundle;

class WpPremiumBundleTest extends MatomoAnalytics_TestCase {
	public function test_should_trigger_should_return_true_if_more_than_three_plugins_installed() {
		$suggestion = $this->make_suggestion(
			[
				'AdvertisingConversionExport',
				'Funnels',
				'ActivityLog',
				'MediaAnalytics',
				'CustomReports',
			]
		);

		$this->assertTrue( $suggestion->should_trigger() );
	}

	public function test_should_trigger_should_return_false_if_less_than_three_plugins_installed() {
		$suggestion = $this->make_suggestion(
			[
				'MediaAnalytics',
				'CustomReports',
			]
		);

		$this->assertFalse( $suggestion->should_trigger() );
	}

	private function make_suggestion( $installed_plugins ) {
		return new class( $installed_plugins ) extends WpPremiumBundle {

			private $installed_plugins;

			public function __construct( $installed_plugins ) {
				$this->installed_plugins = $installed_plugins;
			}

			protected function is_plugin_installed( $plugin_slug ) {
				return in_array( $plugin_slug, $this->installed_plugins, true );
			}
		};
	}
}
