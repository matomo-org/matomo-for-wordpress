<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var \WpMatomo\Settings $settings */
$matomo_extra_url_params = '&' . http_build_query(
	array(
		'php'        => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
		'matomo'     => $settings->get_global_option( 'core_version' ),
		'wp_version' => ! empty( $GLOBALS['wp_version'] ) ? $GLOBALS['wp_version'] : '',
	)
);
?>
<div class="wrap">

	<?php if ( $settings->is_network_enabled() && ! is_network_admin() && is_super_admin() ) { ?>
		<div class="updated notice">
			<p><?php esc_html_e( 'Only super users can see this page', 'matomo' ); ?></p>
		</div>
	<?php } ?>

	<div id="icon-plugins" class="icon32"></div>

	<h1><?php esc_html_e( 'Discover new functionality for your Matomo', 'matomo' ); ?></h1>
	<p>
		<?php esc_html_e( 'Take your Matomo (formerly Piwik) to the next level and drive your conversions & revenue with these premium features. All features are fully hosted on your WordPress and come with 100% data ownership and no limitations.', 'matomo' ); ?>
		<?php if ( is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) { ?>
			<a href="https://plugins.matomo.org/?wp=1" target="_blank" rel="noreferrer noopener">&#187; <?php esc_html_e( 'Browse our Marketplace with over 100 free plugins and premium features', 'matomo' ); ?></a>
		<?php } ?>
	</p>

	<?php if ( ! is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) { ?>
	<div class="matomo-hero">
		<h2><?php echo sprintf( esc_html__( 'Easily install over 100 free plugins & %1$spremium features%2$s for Matomo with just a click' ), '<span style="white-space: nowrap;">', '</span>' ); ?></h2>
		<a href="https://builds.matomo.org/matomo-marketplace-for-wordpress-latest.zip" rel="noreferrer noopener" class="button matomo-cta-button"><?php esc_html_e( 'Download Matomo Marketplace for WordPress', 'matomo' ); ?></a>
		<br>
		<a target="_blank" href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/"><?php esc_html_e( 'Learn more', 'matomo' ); ?></a>
		<a target="_blank" href="https://plugins.matomo.org/?wp=1" rel="noreferrer noopener" class="matomo-next-link"><?php esc_html_e( 'Browse Marketplace', 'matomo' ); ?></a>
	</div>
	<?php } ?>

	<?php
	$matomo_feature_sections = array(
		array(
			'title'    => 'Top free plugins',
			'features' =>
				array(
					array(
						'name'         => 'Custom Dimensions',
						'description'  => 'Extend Matomo to your needs by defining and tracking Custom Dimensions in scope Action or Visit',
						'price'        => '',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomDimensions/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/CustomDimensions?wp=1',
						'image'        => '',
					),
					array(
						'name'         => 'Custom Alerts',
						'description'  => 'Create custom Alerts to be notified of important changes on your website or app!',
						'price'        => '',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomAlerts/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/CustomAlerts?wp=1',
						'image'        => '',
					),
					array(
						'name'         => 'Google Analytics Importer',
						'description'  => 'Import reports from a Google Analytics account into Matomo.',
						'price'        => '',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/GoogleAnalyticsImporter/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/GoogleAnalyticsImporter?wp=1',
						'image'        => '',
					),
				),
		),
		array(
			'title'    => 'Most popular premium features',
			'features' =>
				array(
					array(
						'name'        => 'Heatmap & Session Recording',
						'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.',
						'price'       => '99EUR / 119USD',
						'url'         => 'https://plugins.matomo.org/HeatmapSessionRecording?wp=1',
						'image'       => plugins_url( 'assets/img/heatmap.jpg', MATOMO_ANALYTICS_FILE ),
					),
					array(
						'name'        => 'Custom Reports',
						'description' => 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.',
						'price'       => '99EUR / 119USD',
						'url'         => 'https://plugins.matomo.org/CustomReports?wp=1',
						'image'       => plugins_url( 'assets/img/custom_reports.png', MATOMO_ANALYTICS_FILE ),
					),

					array(
						'name'        => 'Premium Bundle',
						'description' => 'All premium features in one bundle, make the most out of your Matomo for WordPress and enjoy discounts of over 20%!',
						'price'       => '499EUR / 579USD',
						'url'         => 'https://plugins.matomo.org/WpPremiumBundle?wp=1',
						'image'       => plugins_url( 'assets/img/premiumbundle.png', MATOMO_ANALYTICS_FILE ),
					),
				),
		),
		array(
			'title'    => 'Most popular content engagement',
			'features' =>
				array(
					array(
						'name'        => 'Form Analytics',
						'description' => 'Increase conversions on your online forms and lose less visitors by learning everything about your users behavior and their pain points on your forms.',
						'price'       => '79EUR / 89USD',
						'url'         => 'https://plugins.matomo.org/FormAnalytics?wp=1',
						'image'       => plugins_url( 'assets/img/form_analytics.jpg', MATOMO_ANALYTICS_FILE ),
					),
					array(
						'name'        => 'Media Analytics',
						'description' => 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.',
						'price'       => '79EUR / 89USD',
						'url'         => 'https://plugins.matomo.org/MediaAnalytics?wp=1',
						'image'       => plugins_url( 'assets/img/media_analytics.jpg', MATOMO_ANALYTICS_FILE ),
					),
					array(
						'name'        => 'Users Flow',
						'description' => 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.',
						'price'       => '39EUR / 39USD',
						'url'         => 'https://plugins.matomo.org/UsersFlow?wp=1',
						'image'       => plugins_url( 'assets/img/users_flow.png', MATOMO_ANALYTICS_FILE ),
					),
				),
		),
		array(
			'title'    => 'Most popular conversion optimisation',
			'features' =>
				array(
					array(
						'name'        => 'Funnels',
						'description' => 'Identify and understand where your visitors drop off to increase your conversions, sales and revenue with your existing traffic.',
						'price'       => '89EUR / 99USD',
						'url'         => 'https://plugins.matomo.org/Funnels?wp=1',
						'image'       => plugins_url( 'assets/img/funnels.png', MATOMO_ANALYTICS_FILE ),
					),
					array(
						'name'        => 'Multi Attribution',
						'description' => 'Get a clear understanding of how much credit each of your marketing channel is actually responsible for to shift your marketing efforts wisely.',
						'price'       => '39EUR / 39USD',
						'url'         => 'https://plugins.matomo.org/MultiChannelConversionAttribution?wp=1',
						'image'       => plugins_url( 'assets/img/multi_attribution.png', MATOMO_ANALYTICS_FILE ),
					),
				),
		),
		array(
			'title'    => 'Other Premium Features',
			'features' =>
				array(
					array(
						'name'        => 'Cohorts',
						'description' => 'Track your retention efforts over time and keep your visitors engaged and coming back for more.',
						'price'       => '49EUR / 59USD',
						'url'         => 'https://plugins.matomo.org/Cohorts?wp=1',
						'image'       => plugins_url( 'assets/img/cohorts.png', MATOMO_ANALYTICS_FILE ),
					),
					array(
						'name'        => 'Search Engine Keywords Performance',
						'description' => 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.',
						'price'       => '69EUR / 79USD',
						'url'         => 'https://plugins.matomo.org/SearchEngineKeywordsPerformance?wp=1',
						'image'       => plugins_url( 'assets/img/search_engine_keywords.png', MATOMO_ANALYTICS_FILE ),
					),
					/*
					array(
						'name'        => 'Activity Log',
						'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions',
						'price'       => '19EUR / 19USD',
						'url'         => 'https://plugins.matomo.org/ActivityLog?wp=1',
						'image'       => plugins_url( 'assets/img/activity_log.jpg', MATOMO_ANALYTICS_FILE ),
					),*/
				),
		),
	);
	foreach ( $matomo_feature_sections as $matomo_feature_section ) {
		echo '<h2>' . esc_html( $matomo_feature_section['title'] ) . '</h2>';
		echo '<div class="wp-list-table widefat plugin-install"><div id="the-list">';
		foreach ( $matomo_feature_section['features'] as $matomo_index => $matomo_feature ) {
			$matomo_style        = '';
			$matomo_is_3_columns = count( $matomo_feature_section['features'] ) === 3;
			if ( $matomo_is_3_columns ) {
				$matomo_style = 'width: calc(33% - 8px);min-width:282px;max-width:350px;';
				if ( $matomo_index % 3 === 2 ) {
					$matomo_style .= 'clear: inherit;margin-right: 0;margin-left: 16px;';
				}
			}
			?>
			<div class="plugin-card" style="<?php echo $matomo_style; ?>">
				<?php
				if ( $matomo_is_3_columns && ! empty( $matomo_feature['image'] ) ) {
					?>
					<a
					href="<?php echo esc_url( $matomo_feature['url'] ); ?>"
					rel="noreferrer noopener" target="_blank"
					class="thickbox open-plugin-details-modal"><img
						src="<?php echo esc_url( $matomo_feature['image'] ); ?>"
						style="height: 80px;width:100%;object-fit: cover;" alt=""></a><?php } ?>

				<div class="plugin-card-top">
					<div class="
					<?php
					if ( ! $matomo_is_3_columns ) {
						?>
						name column-name<?php } ?>" style="margin-right: 0">
						<h3>
							<a href="<?php echo esc_url( $matomo_feature['url'] ); ?>"
							   rel="noreferrer noopener" target="_blank"
							   class="thickbox open-plugin-details-modal">
								<?php echo esc_html( $matomo_feature['name'] ); ?>
							</a>
							<?php
							if ( ! $matomo_is_3_columns && ! empty( $matomo_feature['image'] ) ) {
								?>
								<a
								href="<?php echo esc_url( $matomo_feature['url'] ); ?>"
								rel="noreferrer noopener" target="_blank"
								class="thickbox open-plugin-details-modal"><img
										src="<?php echo esc_url( $matomo_feature['image'] ); ?>" class="plugin-icon"
										style="object-fit: cover;"
										alt=""></a><?php } ?>
						</h3>
					</div>
					<div class="
					<?php
					if ( ! $matomo_is_3_columns ) {
						?>
						desc column-description<?php } ?>"
						 style="margin-right: 0">
						<p class="matomo-description"><?php echo esc_html( $matomo_feature['description'] ); ?></p>
						<p class="authors"><a class="button-primary"
											  rel="noreferrer noopener" target="_blank"
											  href="<?php echo esc_url( ! empty( $matomo_feature['download_url'] ) ? $matomo_feature['download_url'] : $matomo_feature['url'] ); ?>">
															   <?php
																if ( ! empty( $matomo_feature['price'] ) ) {
																	?>
													From <?php echo esc_html( $matomo_feature['price'] ); ?>
																	<?php
																} else {
																	?>
													Download<?php } ?></a>
						</p>
					</div>
				</div>
			</div>
			<?php
		}
		echo '<div style="clear:both;"></div></div></div>';
	}
	?>
</div>
