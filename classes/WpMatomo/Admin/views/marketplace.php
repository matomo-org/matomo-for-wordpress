<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use WpMatomo\Admin\Marketplace;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $can_view_subscription_tab */
/** @var string|bool|null $active_tab */
/** @var \WpMatomo\Settings $settings */
$licenseKey = $settings->get_license_key();
?>
<div class="wrap">

	<?php if ( $settings->is_network_enabled() && ! is_network_admin() && is_super_admin() ) { ?>
        <div class="updated notice">
            <p><?php _e( 'Only super users can see this page', 'matomo' ); ?></p>
        </div>
	<?php } ?>
    <div id="icon-plugins" class="icon32"></div>
    <h2 class="nav-tab-wrapper">
        <a href="?page=matomo-plugins" class="nav-tab <?php echo empty( $active_tab ) ? 'nav-tab-active' : ''; ?>"
        ><?php _e( 'Browse Marketplace', 'matomo' ); ?></a>
		<?php if ( $can_view_subscription_tab ) { ?>
            <a href="?page=matomo-plugins&tab=subscriptions"
               class="nav-tab <?php echo $active_tab == 'subscriptions' ? 'nav-tab-active' : ''; ?>">Subscriptions</a>
		<?php } ?>
    </h2>
	<?php if ( empty( $active_tab ) || ! $can_view_subscription_tab ) { ?>
        <h1><?php echo __( 'Discover new functionality for your Matomo', 'matomo' ) ?></h1>
        <p><?php _e( 'Take your Matomo (formerly Piwik) to the next level and drive your conversions & revenue with these premium features. All features are fully hosted on your WordPress and come with 100% data ownership and no limitations.', 'matomo' ); ?></p>
		<?php
		$featureSections = array(
			array(
				'title'    => 'Most popular premium features',
				'features' =>
					array(
						array(
							'name'        => 'Heatmap & Session Recording',
							'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.',
							'price'       => '99EUR / 119USD',
							'url'         => 'https://plugins.matomo.org/HeatmapSessionRecording',
							'image'       => plugins_url( 'assets/img/heatmap.jpg', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Search Engine Keywords Performance',
							'description' => 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.',
							'price'       => '69EUR / 79USD',
							'url'         => 'https://plugins.matomo.org/SearchEngineKeywordsPerformance',
							'image'       => plugins_url( 'assets/img/search_engine_keywords.png', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Custom Reports',
							'description' => 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.',
							'price'       => '99EUR / 119USD',
							'url'         => 'https://plugins.matomo.org/CustomReports',
							'image'       => plugins_url( 'assets/img/custom_reports.png', MATOMO_ANALYTICS_FILE )
						)
					)
			),
			array(
				'title'    => 'Most popular conversion optimisation',
				'features' =>
					array(
						array(
							'name'        => 'Funnels',
							'description' => 'Identify and understand where your visitors drop off to increase your conversions, sales and revenue with your existing traffic.',
							'price'       => '89EUR / 99USD',
							'url'         => 'https://plugins.matomo.org/Funnels',
							'image'       => plugins_url( 'assets/img/funnels.png', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Multi Attribution',
							'description' => 'Get a clear understanding of how much credit each of your marketing channel is actually responsible for to shift your marketing efforts wisely.',
							'price'       => '49EUR / 59USD',
							'url'         => 'https://plugins.matomo.org/MultiChannelConversionAttribution',
							'image'       => plugins_url( 'assets/img/multi_attribution.png', MATOMO_ANALYTICS_FILE )
						)
					)
			),
			array(
				'title'    => 'Most popular content engagement',
				'features' =>
					array(
						array(
							'name'        => 'Form Analytics',
							'description' => 'Increase conversions on your online forms and lose less visitors by learning everything about your users behavior and their pain points on your forms.',
							'price'       => '69EUR / 79USD',
							'url'         => 'https://plugins.matomo.org/FormAnalytics',
							'image'       => plugins_url( 'assets/img/form_analytics.jpg', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Media Analytics',
							'description' => 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.',
							'price'       => '69EUR / 79USD',
							'url'         => 'https://plugins.matomo.org/MediaAnalytics',
							'image'       => plugins_url( 'assets/img/media_analytics.jpg', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Users Flow',
							'description' => 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.',
							'price'       => '69EUR / 79USD',
							'url'         => 'https://plugins.matomo.org/UsersFlow',
							'image'       => plugins_url( 'assets/img/users_flow.png', MATOMO_ANALYTICS_FILE )
						)
					)
			),
			array(
				'title'    => 'Other Premium Features',
				'features' =>
					array(
						array(
							'name'        => 'Cohorts',
							'description' => 'Track your retention efforts over time and keep your visitors engaged and coming back for more.',
							'price'       => '49EUR / 59USD',
							'url'         => 'https://plugins.matomo.org/Cohorts',
							'image'       => plugins_url( 'assets/img/cohorts.png', MATOMO_ANALYTICS_FILE )
						),
						array(
							'name'        => 'Activity Log',
							'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions',
							'price'       => '19EUR / 19USD',
							'url'         => 'https://plugins.matomo.org/ActivityLog',
							'image'       => plugins_url( 'assets/img/activity_log.jpg', MATOMO_ANALYTICS_FILE )
						)
					)
			),
		);
		foreach ( $featureSections as $featureSection ) {
			echo '<h2>' . esc_html( $featureSection['title'] ) . '</h2>';
			echo '<div class="wp-list-table widefat plugin-install"><div id="the-list">';
			foreach ( $featureSection['features'] as $index => $feature ) {
				$style      = '';
				$is3Columns = count( $featureSection['features'] ) === 3;
				if ( $is3Columns ) {
					$style = 'width: calc(33% - 8px);min-width:282px;max-width:350px;';
					if ( $index % 3 === 2 ) {
						$style .= 'clear: inherit;margin-right: 0;margin-left: 16px;';
					}
				}
				?>
                <div class="plugin-card" style="<?php echo $style ?>">
					<?php if ( $is3Columns && ! empty( $feature['image'] ) ) { ?><a
                        href="<?php echo esc_url( $feature['url'] ) ?>"
                        rel="noreferrer noopener" target="_blank"
                        class="thickbox open-plugin-details-modal"><img
                                src="<?php echo esc_url( $feature['image'] ) ?>"
                                style="height: 80px;width:100%;object-fit: cover;" alt=""></a><?php } ?>

                    <div class="plugin-card-top">
                        <div class="<?php if ( ! $is3Columns ) { ?>name column-name<?php } ?>" style="margin-right: 0">
                            <h3>
                                <a href="<?php echo esc_url( $feature['url'] ) ?>"
                                   rel="noreferrer noopener" target="_blank"
                                   class="thickbox open-plugin-details-modal">
									<?php echo esc_html( $feature['name'] ) ?>
                                </a>
								<?php if ( ! $is3Columns && ! empty( $feature['image'] ) ) { ?><a
                                    href="<?php echo esc_url( $feature['url'] ) ?>"
                                    rel="noreferrer noopener" target="_blank"
                                    class="thickbox open-plugin-details-modal"><img
                                            src="<?php echo esc_url( $feature['image'] ) ?>" class="plugin-icon"
                                            style="object-fit: cover;"
                                            alt=""></a><?php } ?>
                            </h3>
                        </div>
                        <div class="<?php if ( ! $is3Columns ) { ?>desc column-description<?php } ?>"
                             style="margin-right: 0">
                            <p><?php echo esc_html( $feature['description'] ) ?></p>
                            <p class="authors"><a class="button-primary"
                                                  rel="noreferrer noopener" target="_blank"
                                                  href="<?php echo esc_url( $feature['url'] ) ?>"><?php if ( ! empty( $feature['price'] ) ) { ?>From <?php echo esc_html( $feature['price'] ) ?><?php } else { ?>Download<?php } ?></a>
                            </p>
                        </div>
                    </div>
                </div>
				<?php
			}
			echo '<div style="clear:both;"></div></div></div>';
		} ?>


	<?php } elseif ( $can_view_subscription_tab ) { ?>

		<?php if ( $settings->is_multisite() ) { ?>
            <div class="updated notice">
                <p><?php _e( 'Only super users can see this page', 'matomo' ); ?></p>
            </div>
		<?php } ?>

        <h1><?php _e( 'Premium Feature Subscriptions', 'matomo' ); ?></h1>
        <p><?php _e( 'If you have purchased Matomo Premium Features, please enter your license key below.', 'matomo' ); ?></p>
        <form method="post">
			<?php wp_nonce_field( Marketplace::NONCE_LICENSE ); ?>

            <p>
                <label><?php _e( 'License key', 'matomo' ); ?></label>
                <input type="text" maxlength="80" name="<?php echo Marketplace::FORM_NAME; ?>" style="width:300px;">
                <br/>
                <br/>
                <input type="submit" class="button-primary"
                       value="<?php echo( ! empty( $licenseKey ) ? __('Update License Key', 'matomo') : __('Save License Key', 'matomo') ) ?>">
            </p>
        </form>

		<?php

		if ( ! empty( $licenseKey ) ) {
			$api      = new \WpMatomo\Marketplace\Api( $settings );
			$licenses = $api->get_licenses();
			?>
            <h2><?php _e( 'Your subscriptions', 'matomo' ); ?></h2>
            <p><?php _e('Here\'s a summary of your subscriptions.', 'matomo'); ?>
	            <?php echo sprintf( __( 'You can find all details, download Premium Features and change your subscriptions by %1$slogging in to your account on the Matomo Marketplace%2$s.', 'matomo' ),
                    '<a rel="noreferrer noopener" target="_blank" href="https://shop.matomo.org/my-account/">',
                    '</a>' ); ?>
            </p>
            <table class="widefat">
                <thead>
                <tr>
                    <th><?php _e( 'Name' ) ?></th>
                    <th><?php _e( 'Status', 'matomo' ); ?></th>
                    <th><?php _e( 'Start date', 'matomo' ); ?></th>
                    <th><?php _e( 'End date', 'matomo' ); ?></th>
                    <th><?php _e( 'Next payment date', 'matomo' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $licenses as $license ) { ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $license['plugin']['htmlUrl'] ) ?>"
                               target="_blank"
                               rel="noreferrer noopener"><?php echo esc_html( $license['plugin']['displayName'] ) ?></a>
                        </td>
                        <td><?php echo esc_html( $license['status'] ) ?></td>
                        <td><?php echo esc_html( $license['startDate'] ) ?></td>
                        <td><?php echo( ! empty( $license['endDate'] ) ? esc_html( $license['endDate'] ) : '' ) ?></td>
                        <td><?php echo( ! empty( $license['nextPaymentDate'] ) ? esc_html( $license['nextPaymentDate'] ) : '' ) ?></td>
                    </tr>
				<?php } ?>
                </tbody>
            </table>
			<?php
		}
		?>

	<?php } ?>
</div>
