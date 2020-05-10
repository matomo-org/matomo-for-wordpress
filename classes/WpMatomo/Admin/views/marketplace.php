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
        <a target="_blank" href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/"><span class="dashicons-before dashicons-video-alt3"></span></a> <a target="_blank" href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/"><?php esc_html_e( 'Install instructions', 'matomo' ); ?></a>
		<a target="_blank" href="https://plugins.matomo.org/?wp=1" rel="noreferrer noopener" class="matomo-next-link"><?php esc_html_e( 'Browse Marketplace', 'matomo' ); ?></a>
	</div>
	<?php } ?>

	<?php
    function matomo_show_tables($matomo_feature_sections, $matomo_show_offer) {

	    foreach ( $matomo_feature_sections as $matomo_feature_section ) {
		    echo '<h2>' . esc_html( $matomo_feature_section['title'] ) . '</h2>';
		    echo '<div class="wp-list-table widefat plugin-install"><div id="the-list">';
		    foreach ( $matomo_feature_section['features'] as $matomo_index => $matomo_feature ) {
			    if ($matomo_show_offer && $matomo_feature['name'] === 'Premium Bundle') {
				    ?><div class="plugin-card" style="width: calc(33% - 8px);min-width:282px;max-width:350px;">
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
                                echo ' <a target="_blank" rel="noreferrer noopener" href="'. esc_url($matomo_feature['video']).'"><span class="dashicons dashicons-video-alt3"></span></a>';
                            } elseif (!empty($matomo_feature['url'])) {
		                            echo ' <a target="_blank" rel="noreferrer noopener" href="'. esc_url($matomo_feature['url']).'">'. esc_html__( 'Learn more', 'matomo' ).'</a>';
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
		    echo '<div style="clear:both;"></div>';
		    echo '</div>';
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
						'name'         => 'Custom Dimensions',
						'description'  => 'Extend Matomo to your needs by defining and tracking Custom Dimensions in scope Action or Visit',
						'price'        => 'free',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomDimensions/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/CustomDimensions?wp=1',
						'image'        => '',
					),
					array(
						'name'         => 'Custom Alerts',
						'description'  => 'Create custom Alerts to be notified of important changes on your website or app!',
						'price'        => 'free',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomAlerts/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/CustomAlerts?wp=1',
						'image'        => '',
					),
					array(
						'name'         => 'Marketing Campaigns Reporting',
						'description'  => 'Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.',
						'price'        => 'free',
						'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/MarketingCampaignsReporting/download/latest?wp=1' . $matomo_extra_url_params,
						'url'          => 'https://plugins.matomo.org/MarketingCampaignsReporting?wp=1',
						'image'        => '',
					),
				),
		),
	);

    matomo_show_tables($matomo_feature_sections, $matomo_show_offer);

	$matomo_feature_sections = array(
		array(
			'title'    => '',
			'features' =>
				array(
					array(
						'name'        => 'Heatmaps',
						'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Find confusing elements, discover useless parts and find out what content your users actually engage with.',
						'price'       => '',
						'url'         => 'https://matomo.org/heatmaps/',
						'video'       => 'https://matomo.org/docs/video-matomos-heatmaps-feature/',
						'image'       => '',
					),
					array(
						'name'        => 'Session Recording',
						'description' => 'Watch videos of how your real visitors use your website and what experience they have. Find out why they leave and what they are looking for so you can improve the usability of your site.',
						'price'       => '',
						'url'         => 'https://matomo.org/session-recordings/',
						'video'       => 'https://matomo.org/docs/matomos-session-recordings-feature/',
						'image'       => '',
					),
					array(
						'name'        => 'Users Flow',
						'description' => 'A visual representation of the most popular paths your users take through your website & app which lets you understand your users needs and where they leave.',
						'price'       => '',
						'url'         => 'https://matomo.org/docs/users-flow/',
						'video'       => '',
						'image'       => '',
					),
				),
		),
		array(
			'title'    => '',
			'features' =>
				array(
					array(
						'name'        => 'Form Analytics',
						'description' => 'Increase conversions on your online forms and lose less visitors by learning everything about your users behavior and their pain points on your forms. No setup needed.',
						'price'       => '',
						'url'         => 'https://matomo.org/form-analytics/',
						'image'       => '',
						'video'       => 'https://matomo.org/docs/video-matomos-form-analytics-feature/',
					),
					array(
						'name'        => 'Video & Audio Analytics',
						'description' => 'Get extensive insights into every detail of how your audience watches your videos and listens to your audio. No setup needed.',
						'price'       => '',
						'url'         => 'https://matomo.org/media-analytics/',
						'image'       => '',
						'video'       => 'https://matomo.org/docs/video-media-analytics/',
					),
					array(
						'name'        => 'Funnels',
						'description' => 'Identify and understand where your visitors drop off to increase your conversions, sales and revenue with your existing traffic.',
						'price'       => '',
						'url'         => 'https://matomo.org/funnels/',
						'video'       => 'https://matomo.org/docs/video-matomo-analytics-funnels-feature/',
						'image'       => '',
					),
				),
		),
		array(
			'title'    => '',
			'features' =>
				array(
					array(
						'name'        => 'Search Engine Keywords',
						'description' => 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.',
						'price'       => '',
						'url'         => 'https://matomo.org/docs/search-engine-keywords-performance/',
						'video'       => '',
						'image'       => '',
					),
					array(
						'name'        => 'Google Ads Integration',
						'description' => 'Analyse the success of your Google Ads campaigns and how well they contribute to your goals. See what keywords and search queries are leading to clicks for your paid a   ds and bringing your business the highest ROI.',
						'price'       => '',
						'url'         => 'https://matomo.org/docs/paid-advertising-performance/',
						'video'       => '',
						'image'       => '',
					),
					array(
						'name'        => 'Multi Attribution',
						'description' => 'Get a clear understanding of how much credit each of your marketing channel is actually responsible for to shift your marketing efforts wisely.',
						'price'       => '',
						'url'         => 'https://matomo.org/multi-attribution/',
						'video'       => 'https://matomo.org/docs/video-matomo-analytics-attribution-feature/',
						'image'       => '',
					),
				),
		),
		array(
			'title'    => '',
			'features' =>
				array(
					array(
						'name'        => 'Custom Reports',
						'description' => 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.',
						'price'       => '',
						'url'         => 'https://matomo.org/custom-reports/',
						'image'       => '',
						'video'       => 'https://matomo.org/docs/video-matomos-custom-reports-feature/',
					),
					array(
						'name'        => 'Cohorts',
						'description' => 'Track your retention efforts over time and keep your visitors engaged and coming back for more.',
						'price'       => '',
						'url'         => 'https://matomo.org/docs/cohorts/',
						'image'       => '',
						'video'       => '',
					),
				),
		),
	);
	?>

    <div style="border: 6px dashed limegreen;padding: 20px;margin-top: 30px;background: white;text-align: center;max-width: 1100px;">
    <h1 style="color: red">Limited time offer! Matomo Premium Bundle only 199€/year (300€ off)</h1>
    <h3>Your marketing efforts are too valuable to focus on the wrong things.<br> Take your Matomo for WordPress to the next level to push out content and changes to your website that make you consistently more successful for less than 17€/month. 🚀</h3>
    <a href="https://matomo.org/wp-premium-bundle/" class="button button-primary"
       style="background: limegreen;border-color: limegreen;font-size: 18px;"
       target="_blank" rel="noreferrer noopener" role="button">Learn more</a>

    <h2>What's included in this bundle?</h2>
    <?php

	matomo_show_tables($matomo_feature_sections, $matomo_show_offer);
	?>
    <h3>All features come with no data limits, max privacy protection and are fully hosted within your WordPress. You own 100% of the data. No data is shared with any other party, ever.
     </h3>
        <h3>Matomo is free open source software. <strong>Purchasing this bundle will help fund the future of the Matomo open-source project.</strong><br>Thank you for your support!</h3>
    <a href="https://matomo.org/wp-premium-bundle/"
       style="background: limegreen;border-color: limegreen;font-size: 18px;" class="button button-primary" target="_blank" rel="noreferrer noopener" role="button">Learn more</a>
    </div>
</div>
