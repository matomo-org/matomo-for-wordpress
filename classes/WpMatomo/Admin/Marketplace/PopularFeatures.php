<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\Marketplace;

class PopularFeatures {
	public function show() {
		$matomo_popular_features = $this->get_popular_features();

		include __DIR__ . '/views/popular-features.php';
	}

	private function get_popular_features() {
		return [
			'MarketingCampaignsReporting'     => [
				'name' => __( 'Marketing Campaigns Reporting', 'matomo' ),
				'desc' => __( "Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.'", 'matomo' ),
				'img'  => 'marketing-campaign-analytics.png',
			],
			'SearchEngineKeywordsPerformance' => [
				'name'  => __( 'Search Engine Keywords Performance', 'matomo' ),
				'desc'  => __( 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.', 'matomo' ),
				'price' => '79EUR / 89USD',
				'img'   => 'search-engine-keywords-performance.webp',
			],
			'HeatmapSessionRecording'         => [
				'name'  => __( 'Heatmap & Session Recording', 'matomo' ),
				'desc'  => __( 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.', 'matomo' ),
				'price' => '109EUR / 129USD',
				'img'   => 'heatmap-session-recording.webp',
			],
			'CustomAlerts'                    => [
				'name' => __( 'Custom Alerts', 'matomo' ),
				'desc' => __( 'Create custom Alerts to be notified of important changes on your website or app!', 'matomo' ),
				'img'  => 'custom-alerts.png',
			],
			'MediaAnalytics'                  => [
				'name'  => __( 'Media Analytics', 'matomo' ),
				'desc'  => __( 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.', 'matomo' ),
				'price' => '89EUR / 99USD',
				'img'   => 'media-analytics.jpg',
			],
			'CustomReports'                   => [
				'name'  => __( 'Custom Reports', 'matomo' ),
				'desc'  => __( 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.', 'matomo' ),
				'price' => '109EUR / 129USD',
				'img'   => 'custom-reports.png',
			],
			'WpPremiumBundle'                 => [
				'name'  => __( 'WordPress Premium Bundle', 'matomo' ),
				'desc'  => __( 'All premium features in one bundle, make the most out of your Matomo for WordPress and enjoy discounts of up to 25%!', 'matomo' ),
				'price' => '549EUR / 639USD',
				'img'   => 'matomo-wordpress-premium-bundle.png',
			],
			'UsersFlow'                       => [
				'name'  => __( 'Users Flow', 'matomo' ),
				'desc'  => __( 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.', 'matomo' ),
				'price' => '49EUR / 59USD',
				'img'   => 'users-flow.webp',
			],
		];
	}
}
