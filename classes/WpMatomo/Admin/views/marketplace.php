<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */
/** @var bool $matomo_show_offer */

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

	<h1><?php matomo_header_icon(); ?> <?php esc_html_e( 'Discover new functionality for your Matomo', 'matomo' ); ?></h1>

	<?php if ( ! is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) { ?>
        <div class="updated notice matomo-marketplace-notice">
            <p><?php echo sprintf( esc_html__( 'Easily install over 100 free plugins & %1$spremium features%2$s for Matomo with just a click' ), '<span style="white-space: nowrap;">', '</span>' ); ?>
            </p>
            <p><a href="https://builds.matomo.org/matomo-marketplace-for-wordpress-latest.zip" rel="noreferrer noopener" class="button"><?php esc_html_e( 'Download Matomo Marketplace for WordPress', 'matomo' ); ?></a>

            <a target="_blank" href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/"><span class="dashicons-before dashicons-video-alt3"></span></a> <a target="_blank" href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/"><?php esc_html_e( 'Install instructions', 'matomo' ); ?></a>
           </p>
        </div>
	<?php } ?>

	<?php
    function matomo_show_tables($matomo_feature_sections, $matomo_show_offer) {

	    foreach ( $matomo_feature_sections as $matomo_feature_section ) {
		    $matomo_num_features_in_block = count( $matomo_feature_section['features'] );

		    echo '<h2>' . esc_html( $matomo_feature_section['title'] ) . '</h2>';
		    echo '<div class="wp-list-table widefat plugin-install matomo-plugin-list matomo-plugin-row-' . $matomo_num_features_in_block . '"><div id="the-list">';
		    foreach ( $matomo_feature_section['features'] as $matomo_index => $matomo_feature ) {
			    $matomo_style        = '';
			    $matomo_is_3_columns = $matomo_num_features_in_block === 3;
			    if ( $matomo_is_3_columns ) {
				    $matomo_style = 'width: calc(33% - 8px);min-width:282px;max-width:350px;';
				    if ( $matomo_index % 3 === 2 ) {
					    $matomo_style .= 'clear: inherit;margin-right: 0;margin-left: 16px;';
				    }
			    }

			    if ($matomo_show_offer && $matomo_feature['name'] === 'Premium Bundle') {
				    ?><div class="plugin-card" style="<?php echo $matomo_style; ?>">
                        <div style="border: 6px dashed red;text-align: center">
                            <h2 style="font-size: 24px;">
                                <a href="https://matomo.org/wp-premium-bundle/" target="_blank" rel="noreferrer noopener"><span style="color: black;">Limited time!</span><br><br><span style="color:red">300€ Off Premium Bundle</span></a></h2>
                            <p>All premium features in one bundle.<br>
                                No risk 100% money back guarantee.<br><br>
                                <a class="button-primary" href="https://matomo.org/wp-premium-bundle/" target="_blank" rel="noreferrer noopener">Get it for only 199€/year</a>
                                <br>
                            </p>
                        </div>
                    </div><?php
				    continue;
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
						name column-name<?php } ?>" style="margin-right: 0;<?php if ( empty( $matomo_feature['image'] )) { echo 'margin-left: 0;'; } ?>">
                            <h3>
                                <a href="<?php echo esc_url( !empty($matomo_feature['video']) ? $matomo_feature['video'] : $matomo_feature['url'] ); ?>"
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
                             style="margin-right: 0;<?php if ( empty( $matomo_feature['image'] )) { echo 'margin-left: 0;'; } ?>">
                            <p class="matomo-description"><?php echo esc_html( $matomo_feature['description'] ); ?>
                            <?php if (!empty($matomo_feature['video'])) {
                                echo ' <a target="_blank" rel="noreferrer noopener" style="white-space: nowrap;" href="'. esc_url($matomo_feature['video']).'"><span class="dashicons dashicons-video-alt3"></span> '. esc_html__( 'Learn more', 'matomo' ).'</a>';
                            } elseif (!empty($matomo_feature['url'])) {
		                            echo ' <a target="_blank" rel="noreferrer noopener" style="white-space: nowrap;" href="'. esc_url($matomo_feature['url']).'">'. esc_html__( 'Learn more', 'matomo' ).'</a>';
	                            } ?></p>
	                        <?php if ( ! empty( $matomo_feature['price'] )) {?><p class="authors"><a class="button-primary"
                                                  rel="noreferrer noopener" target="_blank"
                                                  href="<?php echo esc_url( ! empty( $matomo_feature['download_url'] ) ? $matomo_feature['download_url'] : $matomo_feature['url'] ); ?>">
								    <?php
									    if ($matomo_feature['price'] === 'free' ) {
									        esc_html_e('Download', 'matomo');
									    } else {
										    echo esc_html( $matomo_feature['price'] );
									    } ?>
								 </a>
                            </p><?php   } ?>
                        </div>
                    </div>
                </div>
			    <?php
		    }
		    echo '';
		    echo '</div><div style="clear: both"></div>';
		    if (!empty($matomo_feature_section['more_url'])) {
			    echo '<a target="_blank" rel="noreferrer noopener" href="'.esc_attr($matomo_feature_section['more_url']).'"><span class="dashicons dashicons-arrow-right-alt2"></span>'. esc_html($matomo_feature_section['more_text']).'</a>';
		    }
		    echo '</div>';
	    }
    }

	$matomo_feature_sections = array(
		array(
			'title'    => 'Top free plugins',
			'more_url' => 'https://plugins.matomo.org/free?wp=1',
			'more_text' => 'Browse all free plugins',
			'features' =>
				array(
					array(
						'name'         => 'Marketing Campaigns Reporting',
						'description'  => 'Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.',
						'price'        => 'free',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/MarketingCampaignsReporting/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/MarketingCampaignsReporting?wp=1&pk_campaign=WP&pk_source=Plugin',
						'image'        => '',
					),
					array(
						'name'         => 'Custom Alerts',
						'description'  => 'Create custom Alerts to be notified of important changes on your website or app!',
						'price'        => 'free',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomAlerts/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/CustomAlerts?wp=1&pk_campaign=WP&pk_source=Plugin',
						'image'        => '',
					),
				),
		),
	);

    matomo_show_tables($matomo_feature_sections, $matomo_show_offer);

    echo '<br>';

	$matomo_feature_sections = array(
        array(
		'title'    => 'Most popular premium features',
		'features' =>
			array(
				array(
					'name'        => 'Heatmap & Session Recording',
					'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.',
					'price'       => '99EUR / 119USD',
					'url'         => 'https://plugins.matomo.org/HeatmapSessionRecording?wp=1',
					'image'       => '',
				),
				array(
					'name'        => 'Custom Reports',
					'description' => 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.',
					'price'       => '99EUR / 119USD',
					'url'         => 'https://plugins.matomo.org/CustomReports?wp=1',
					'image'       => '',
				),

				array(
					'name'        => 'Premium Bundle',
					'description' => 'All premium features in one bundle, make the most out of your Matomo for WordPress and enjoy discounts of over 20%!',
					'price'       => '499EUR / 579USD',
					'url'         => 'https://plugins.matomo.org/WpPremiumBundle?wp=1',
					'image'       => '',
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
						'image'       => '',
					),
					array(
						'name'        => 'Video & Audio Analytics',
						'description' => 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.',
						'price'       => '79EUR / 89USD',
						'url'         => 'https://plugins.matomo.org/MediaAnalytics?wp=1',
						'image'       => '',
					),
					array(
						'name'        => 'Users Flow',
						'description' => 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.',
						'price'       => '39EUR / 39USD',
						'url'         => 'https://plugins.matomo.org/UsersFlow?wp=1',
						'image'       => '',
					),
				),
		),
		array(
			'title'    => 'Most popular acquisition & SEO features',
			'features' =>
				array(
					array(
						'name'        => 'Search Engine Keywords Performance',
						'description' => 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.',
						'price'       => '69EUR / 79USD',
						'url'         => 'https://plugins.matomo.org/SearchEngineKeywordsPerformance?wp=1',
						'image'       => '',
					),
					array(
						'name'        => 'Paid Advertising Performance',
						'description' => 'Analyse the success of your Google Ads campaigns directly in your Matomo. See what keywords and search queries are leading to clicks for your paid ads and bringing your business the highest ROI.',
						'price'       => '79EUR / 89USD',
						'url'         => 'https://plugins.matomo.org/PaidAdvertisingPerformance?wp=1',
						'image'       => '',
					),
					array(
						'name'        => 'Multi Attribution',
						'description' => 'Get a clear understanding of how much credit each of your marketing channel is actually responsible for to shift your marketing efforts wisely.',
						'price'       => '39EUR / 39USD',
						'url'         => 'https://plugins.matomo.org/MultiChannelConversionAttribution?wp=1',
						'image'       => '',
					),
					/*
					array(
						'name'        => 'Activity Log',
						'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions',
						'price'       => '19EUR / 19USD',
						'url'         => 'https://plugins.matomo.org/ActivityLog?wp=1',
						'image'       => '',
					),*/
				),
		),
		array(
			'title'    => 'Other premium features',
			'features' =>
				array(
					array(
						'name'        => 'Funnels',
						'description' => 'Identify and understand where your visitors drop off to increase your conversions, sales and revenue with your existing traffic.',
						'price'       => '89EUR / 99USD',
						'url'         => 'https://plugins.matomo.org/Funnels?wp=1',
						'image'       => '',
					),
					array(
						'name'        => 'Cohorts',
						'description' => 'Track your retention efforts over time and keep your visitors engaged and coming back for more.',
						'price'       => '49EUR / 59USD',
						'url'         => 'https://plugins.matomo.org/Cohorts?wp=1',
						'image'       => '',
					),
				),
		),
	);

		matomo_show_tables($matomo_feature_sections, $matomo_show_offer);

	?>

</div>
