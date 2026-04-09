<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions\Suggestions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

use WpMatomo\Admin\PluginSuggestions\Suggestion;

class WpPremiumBundle extends Suggestion {

	const ALL_PREMIUM_FEATURES = [
		'AdvertisingConversionExport',
		'Cohorts',
		'CrashAnalytics',
		'CustomReports',
		'FormAnalytics',
		'Funnels',
		'HeatmapSessionRecording',
		'MediaAnalytics',
		'MultiChannelConversionAttribution',
		'SearchEngineKeywordsPerformance',
		'SEOWebVitals',
		'UsersFlow',
		'AbTesting',
		'ActivityLog',
		'LoginSaml',
		'RollUpReporting',
		'WhiteLabel',
	];

	const INSTALLED_COUNT_THRESHOLD = 3;

	public function should_trigger() {
		$installed_count = 0;
		foreach ( self::ALL_PREMIUM_FEATURES as $plugin_slug ) {
			if ( $this->is_plugin_installed( $plugin_slug ) ) {
				++$installed_count;
			}
		}

		return $installed_count >= self::INSTALLED_COUNT_THRESHOLD;
	}

	public function init() {
		$this->plugin_name        = __( 'WP Premium Bundle', 'matomo' );
		$this->plugin_desc_long   = __( 'Unlock full analytics capacity across your entire site.', 'matomo' );
		$this->plugin_desc_short  = __( 'All premium features in one bundle', 'matomo' );
		$this->trigger_desc_short = __( 'Premium Plugins', 'matomo' );
		$this->trigger_desc_long  = __( 'Premium features activated', 'matomo' );
		$this->image_file         = 'matomo-wordpress-premium-bundle.png';
	}

	public function is_suggestion_applicable() {
		// if the marketplace is not installed, we can't check if the user has the
		// wp premium bundle or not. but it's possible the user installed the plugins
		// manually, so we still check the plugins above.
		if ( ! class_exists( \MatomoMarketplaceApi::class ) ) {
			return true;
		}

		// if the user does not have three premium plugins installed, we don't need
		// to check with the marketplace API if the WP premium bundle is available,
		// since we won't display the suggestion anyway.
		if ( ! $this->should_trigger() ) {
			return false;
		}

		// if the license is not set in the marketplace, we still do the check for
		// the same reason as above
		$marketplace_api = new \MatomoMarketplaceApi();
		$license_key     = $marketplace_api->get_license_key();
		if ( empty( $license_key ) ) {
			return true;
		}

		// makes an HTTP request to the marketplace API
		$available_licenses = $marketplace_api->get_licenses();
		if ( $this->has_wp_premium_bundle_already( $available_licenses ) ) {
			return false;
		}

		return true;
	}

	private function has_wp_premium_bundle_already( $available_licenses ) {
		foreach ( $available_licenses as $license ) {
			if (
				! empty( $license['plugin']['name'] )
				&& 'PremiumBundle' === $license['plugin']['name']
			) {
				return true;
			}
		}
		return false;
	}
}
