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
	[
		'php'        => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
		'matomo'     => $settings->get_global_option( 'core_version' ),
		'wp_version' => ! empty( $GLOBALS['wp_version'] ) ? $GLOBALS['wp_version'] : '',
	]
);

/** @var string $matomo_currency */
?>
<?php if ( ! empty( $valid_tabs ) ) { ?>
<h2 class="nav-tab-wrapper" style="margin-bottom:1em;">
	<?php if ( in_array( 'marketplace', $valid_tabs, true ) ) { ?>
		<a href="?page=matomo-marketplace&tab=marketplace"
		   class="nav-tab <?php echo ( 'marketplace' === $active_tab ) ? 'nav-tab-active' : ''; ?>"
		><?php esc_html_e( 'Overview', 'matomo' ); ?></a>
	<?php } ?>
	<?php if ( in_array( 'install', $valid_tabs, true ) ) { ?>
		<a href="?page=matomo-marketplace&tab=install"
		   class="nav-tab <?php echo ( 'install' === $active_tab ) ? 'nav-tab-active' : ''; ?>"
		><?php esc_html_e( 'Install Plugins', 'matomo' ); ?></a>
	<?php } ?>
	<?php if ( in_array( 'subscriptions', $valid_tabs, true ) ) { ?>
		<a href="?page=matomo-marketplace&tab=subscriptions"
		   class="nav-tab <?php echo 'subscriptions' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Subscriptions', 'matomo' ); ?></a>
	<?php } ?>
</h2>
<?php } ?>

<?php if ( $settings->is_network_enabled() && ! is_network_admin() && is_super_admin() ) { ?>
	<div class="matomo-notice updated notice">
		<p><?php esc_html_e( 'Only WordPress network admins can see this page', 'matomo' ); ?></p>
	</div>
<?php } ?>

<?php
if ( isset( $marketplace_setup_wizard ) ) {
	$marketplace_setup_wizard->show();
	return;
}
?>

<style>
	#matomo-for-marketplace-welcome {
		font-size: 16px;
		margin-left: 8px;
	}

	#matomo-welcome-marketplace-setup {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: stretch;
		padding: 2em 1.5em;
		border-radius: 6px;
		background-color: white;
	}

	#matomo-setup-preface {
		padding-right: 32px;
		border-right: solid 2px #eee;
	}

	#matomo-setup-preface-title {
		margin-top: 0;
		display: flex;
		flex-direction: row;
		align-items: center;
	}

	#matomo-setup-preface-title h2 {
		flex: 1;
		margin: 0 0 0 4px;
		font-weight: 500;
	}

	#matomo-setup-preface-title img {
		height: 42px;
		width: 42px;
		margin-left: -6px;
		margin-top: -16px;
		margin-bottom: -16px;
	}

	#matomo-for-marketplace-welcome p {
		color: #6a6a6a;
		font-size: 15px;
	}

	#matomo-step1, #matomo-step2 {
		width: 40%;
		margin-left: 32px;
		display: flex;
		flex-direction: column;
	}

	#matomo-step1 p, #matomo-step2 p {
		flex: 1;
	}

	.matomo-setup-divider {
		height: 1px;
		width: 100%;
		background-color: #eee;
	}

	#matomo-for-marketplace-welcome p.matomo-smaller-text {
		font-size: 13px;
		line-height: 1.4em;
	}

	#matomo-for-marketplace-welcome h1 + p {
		max-width: 700px;
		margin-top: .25em;
		margin-bottom: 2em;
	}

	.step-number {
		margin-right: 8px;
		font-size: 14px;
		border-radius: 50%;
		width: 28px;
		height: 28px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		color: white;
		background-color: #777;
	}

	.step-number.current {
		background-color: #2271b1;
	}

	.plugin-activation-status {
		display: inline-flex;
		flex-direction: row;
		align-items: center;
		border-radius: 16px;
		border: solid 1px #deecfe;
		padding: 6px 15px;
		font-size: 14px;
	}

	.plugin-activation-status > svg {
		width: 16px;
		height: 16px;
		margin-right: 4px;
	}

	/** TODO: responsiveness */
</style>
<div id="matomo-for-marketplace-welcome">
	<h1><?php matomo_header_icon(); ?><?php esc_html_e( 'What is the Matomo for WordPress Marketplace', 'matomo' ); ?></h1>

	<p>
		<?php esc_html_e( 'Matomo for WordPress includes Matomo core analytics.', 'matomo' ); ?>
		<?php esc_html_e( 'You started there but now you are getting stronger and more advanced! Congrats!', 'matomo' ); ?>
		<?php esc_html_e( 'You can extend it with additional Matomo Analytics modules.', 'matomo' ); ?>
	</p>

	<div id="matomo-welcome-marketplace-setup">
		<div id="matomo-setup-preface">
			<div id="matomo-setup-preface-title">
				<img src="<?php echo esc_attr( plugins_url( '/assets/img/logo.png', MATOMO_ANALYTICS_FILE ) ); ?>" alt="Matomo Logo" />
				<h2>
					<?php esc_html_e( 'Setup the Matomo Marketplace in two easy steps', 'matomo' ); ?>
				</h2>
			</div>
			<p>
				<?php esc_html_e( 'Follow these simple steps to install extensions and enhance your analytics capabilities.', 'matomo' ); ?>
			</p>
			<div class="matomo-setup-divider"></div>
			<p class="matomo-smaller-text">
				<?php echo sprintf(
					esc_html__( 'Don\'t want to use the plugin? Download directly %1$son our marketplace,%2$s but keep in mind, you won\'t receive automatic updates unless you use the Matomo Marketplace plugin.', 'matomo' ),
					'<a href="https://plugins.matomo.org/?wp=1" target="_blank">',
					'</a>'
				); ?>
			</p>
			<div>
				<div class="plugin-activation-status">
					<svg fill="hsl(228, 97%, 42%)" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10.72,19.9a8,8,0,0,1-6.5-9.79A7.77,7.77,0,0,1,10.4,4.16a8,8,0,0,1,9.49,6.52A1.54,1.54,0,0,0,21.38,12h.13a1.37,1.37,0,0,0,1.38-1.54,11,11,0,1,0-12.7,12.39A1.54,1.54,0,0,0,12,21.34h0A1.47,1.47,0,0,0,10.72,19.9Z"><animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/></path></svg>

					<?php esc_html_e( 'Waiting for plugin activation', 'matomo' ); ?>...
				</div>
			</div>
		</div>
		<div id="matomo-step1">
			<div>
				<span class="step-number current">1</span>
				<span><?php esc_html_e( 'Download Plugin', 'matomo' ); ?></span>
			</div>
			<p>
				<?php esc_html_e( 'Download the Matomo Marketplace for WordPress plugin as a .zip file to your computer.', 'matomo' ); ?>
			</p>
			<div>
				<button class="button-primary"><?php esc_html_e( 'Download .zip', 'matomo' ); ?></button>
			</div>
		</div>
		<div id="matomo-step2">
			<div>
				<span class="step-number">2</span>
				<span><?php esc_html_e( 'Upload & Install', 'matomo' ); ?></span>
			</div>
			<p>
				<?php esc_html_e( 'Go to your WordPress plugins admin page. Upload and install the plugin you just downloaded.', 'matomo' ); ?>
			</p>
			<div>
				<button class="button-secondary"><?php esc_html_e( 'Go to Plugins', 'matomo' ); ?></button>
			</div>
		</div>
	</div>

	<h1 style="margin-top: 1em;"><?php esc_html_e( 'Most popular features', 'matomo' ); ?></h1>
	<p><?php esc_html_e( 'Developed by Matomo and partners, install these on top of your Matomo plugin for more advanced analytics.', 'matomo' ); ?></p>

	<div class="matomo-popular-feature">
		<div class="description">
			<h2><?php esc_html_e( 'Marketing Campaigns Reporting', 'matomo' ); ?></h2>
			<p><?php esc_html_e( 'Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.', 'matomo' ); ?></p>
		</div>

		<a href="/">
			<button class="btn-primary"><?php esc_html_e( 'Learn more', 'matomo' ); ?></button>
		</a>
	</div>
</div>

<?php
function matomo_show_tables( $matomo_feature_sections, $matomo_version, $matomo_currency ) {
	foreach ( $matomo_feature_sections as $matomo_feature_section ) {
		$matomo_feature_section['features'] = array_filter( $matomo_feature_section['features'] );
		$matomo_num_features_in_block       = count( $matomo_feature_section['features'] );
		$matomo_feature_section_class       = isset( $matomo_feature_section['class'] ) ? $matomo_feature_section['class'] : '';
		$matomo_extra_card_html             = isset( $matomo_feature_section['extra_card_html'] ) ? $matomo_feature_section['extra_card_html'] : '';

		echo '<h2>' . esc_html( $matomo_feature_section['title'] ) . '</h2>';
		echo '<div class="wp-list-table widefat plugin-install matomo-plugin-list matomo-plugin-row-' . esc_html( $matomo_num_features_in_block ) . ' ' . esc_attr( $matomo_feature_section_class ) . '"><div id="the-list">';

		foreach ( $matomo_feature_section['features'] as $matomo_index => $matomo_feature ) {
			$matomo_style        = '';
			$matomo_is_3_columns = 3 === $matomo_num_features_in_block;
			if ( $matomo_is_3_columns ) {
				$matomo_style = 'width: calc(33% - 8px);min-width:282px;max-width:350px;';
				if ( 2 === $matomo_index % 3 ) {
					$matomo_style .= 'clear: inherit;margin-right: 0;margin-left: 16px;';
				}
			}
			$plugin_url = empty( $matomo_feature['url'] ) ? null : $matomo_feature['url'] . '&matomoversion=' . $matomo_version;
			?>
			<div class="plugin-card" style="<?php echo esc_attr( $matomo_style ); ?>">
				<?php
				if ( $matomo_is_3_columns && ! empty( $matomo_feature['image'] ) ) {
					?>
				<a
						href="<?php echo esc_url( $plugin_url ); ?>"
						rel="noreferrer noopener" target="_blank"
						class="thickbox open-plugin-details-modal"><img
							src="<?php echo esc_url( $matomo_feature['image'] ); ?>"
							style="height: 80px;width:100%;object-fit: cover;" alt=""></a>
							<?php
				}
				?>

				<div class="plugin-card-top">
					<div class="
				<?php
				if ( ! $matomo_is_3_columns ) {
					?>
					name column-name
					<?php
				}
				?>
					" style="margin-right: 0;
					<?php
					if ( empty( $matomo_feature['image'] ) ) {
						echo 'margin-left: 0;';
					}
					?>
							">
						<h3>
							<a href="<?php echo esc_url( ! empty( $matomo_feature['video'] ) ? $matomo_feature['video'] : $plugin_url ); ?>"
							   rel="noreferrer noopener" target="_blank"
							   class="thickbox open-plugin-details-modal">
								<?php echo esc_html( $matomo_feature['name'] ); ?>
							</a>
							<?php
							if ( ! $matomo_is_3_columns && ! empty( $matomo_feature['image'] ) ) {
								?>
							<a
									href="<?php echo esc_url( $plugin_url ); ?>"
									rel="noreferrer noopener" target="_blank"
									class="thickbox open-plugin-details-modal"><img
										src="<?php echo esc_url( $matomo_feature['image'] ); ?>" class="plugin-icon"
										style="object-fit: cover;"
										alt=""></a>
										<?php
							}
							?>
						</h3>
					</div>
					<div class="
				<?php
				if ( ! $matomo_is_3_columns ) {
					?>
					desc column-description
					<?php
				}
				?>
					"
						 style="margin-right: 0;
						 <?php
							if ( empty( $matomo_feature['image'] ) ) {
								echo 'margin-left: 0;';
							}
							?>
								 ">
						<?php
						if ( ! empty( $matomo_feature['price'] ) && 'free' !== $matomo_feature['price'] ) {
							?>
							<span class="plugin-price"><?php echo esc_html( $matomo_feature['price'] ); ?></span>
							<?php
						}
						?>
						<p class="matomo-description"><?php echo esc_html( $matomo_feature['description'] ); ?>
							<?php
							if ( ! empty( $matomo_feature['video'] ) ) {
								echo ' <a target="_blank" rel="noreferrer noopener" style="white-space: nowrap;" href="' . esc_url( $matomo_feature['video'] ) . '"><span class="dashicons dashicons-video-alt3"></span> ' . esc_html__( 'Learn more', 'matomo' ) . '</a>';
							} elseif ( ! empty( $matomo_feature['url'] ) ) {
								echo ' <a target="_blank" rel="noreferrer noopener" style="white-space: nowrap;" href="' . esc_url( $plugin_url ) . '">' . esc_html__( 'Learn more', 'matomo' ) . '</a>';
							}
							?>
						</p>
						<?php
						if ( ! empty( $matomo_feature['price'] ) ) {
							$matomo_button_url = ! empty( $matomo_feature['download_url'] ) ? $matomo_feature['download_url'] : $plugin_url;
							if ( 'free' !== $matomo_feature['price'] ) {
								$matomo_button_url .= '&add-to-cart=ws&currency=' . $matomo_currency;
							}
							?>
							<p class="authors">
								<a class="button-primary"
									rel="noreferrer noopener" target="_blank"
									href="<?php echo esc_url( $matomo_button_url ); ?>">
								<?php
								if ( 'free' === $matomo_feature['price'] ) {
									esc_html_e( 'Download', 'matomo' );
								} else {
									?>
									<span
										class="dashicons dashicons-cart"
										<?php if ( ! function_exists( 'wp_get_wp_version' ) || version_compare( wp_get_wp_version(), '7', '<' ) ) { ?>
											style="vertical-align: middle;"
										<?php } ?>
									></span>
									<?php
									esc_html_e( 'Start free trial...', 'matomo' );
								}
								?>
								</a>
							</p>
							<?php
						}
						?>
					</div>
				</div>
				<?php
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $matomo_extra_card_html;
				?>
			</div>
			<?php
		}
		echo '';
		echo '</div><div style="clear: both"></div>';
		if ( ! empty( $matomo_feature_section['more_url'] ) ) {
			echo '<a target="_blank" rel="noreferrer noopener" href="' . esc_attr( $matomo_feature_section['more_url'] ) . '"><span class="dashicons dashicons-arrow-right-alt2"></span>' . esc_html( $matomo_feature_section['more_text'] ) . '</a>';
		}
		echo '</div>';
	}
}

$matomo_feature_sections = [
	[
		'title'           => 'What\'s New',
		'class'           => 'matomo-new-plugins',
		'extra_card_html' => '<span class="matomo-new-marker">' . esc_html__( 'New!', 'matomo' ) . '</span>',
		'features'        =>
			[
				[
					'name'        => 'Crash Analytics',
					'description' => 'Detect crashes to improve the user experience, increase conversions and recover revenue. Resolve them with insights to minimise developer hours.',
					'price'       => '79EUR / 89USD',
					'url'         => 'https://plugins.matomo.org/CrashAnalytics?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
	[
		'title'     => 'Top free plugins',
		'more_url'  => 'https://plugins.matomo.org/free?wp=1&pk_campaign=WP&pk_source=Plugin',
		'more_text' => 'Browse all free plugins',
		'features'  =>
			[
				[
					'name'         => 'Marketing Campaigns Reporting',
					'description'  => 'Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.',
					'price'        => 'free',
					'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/MarketingCampaignsReporting/download/latest?wp=1' . $matomo_extra_url_params,
					'url'          => 'https://plugins.matomo.org/MarketingCampaignsReporting?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'        => '',
				],
				[
					'name'         => 'Custom Alerts',
					'description'  => 'Create custom Alerts to be notified of important changes on your website or app!',
					'price'        => 'free',
					'download_url' => 'https://plugins.matomo.org/api/2.0/plugins/CustomAlerts/download/latest?wp=1' . $matomo_extra_url_params,
					'url'          => 'https://plugins.matomo.org/CustomAlerts?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'        => '',
				],
			],
	],
];

/** @var \WpMatomo\Settings $settings */
$matomo_version = $settings->get_matomo_major_version();

matomo_show_tables( $matomo_feature_sections, $matomo_version, $matomo_currency );

echo '<br>';

$matomo_feature_sections = [
	[
		'title'    => 'Most popular premium features',
		'features' =>
			[
				[
					'name'        => 'Heatmap & Session Recording',
					'description' => 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.',
					'price'       => '109EUR / 129USD',
					'url'         => 'https://plugins.matomo.org/HeatmapSessionRecording?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Custom Reports',
					'description' => 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.',
					'price'       => '109EUR / 129USD',
					'url'         => 'https://plugins.matomo.org/CustomReports?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],

				[
					'name'        => 'Premium Bundle',
					'description' => 'All premium features in one bundle, make the most out of your Matomo for WordPress and enjoy discounts of over 25%!',
					'price'       => '549EUR / 639USD',
					'url'         => 'https://plugins.matomo.org/WpPremiumBundle?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
	[
		'title'    => 'Most popular content engagement',
		'features' =>
			[
				[
					'name'        => 'Form Analytics',
					'description' => 'Increase conversions on your online forms and lose less visitors by learning everything about your users behavior and their pain points on your forms.',
					'price'       => '89EUR / 99USD',
					'url'         => 'https://plugins.matomo.org/FormAnalytics?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Video & Audio Analytics',
					'description' => 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.',
					'price'       => '89EUR / 99USD',
					'url'         => 'https://plugins.matomo.org/MediaAnalytics?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Users Flow',
					'description' => 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.',
					'price'       => '49EUR / 59USD',
					'url'         => 'https://plugins.matomo.org/UsersFlow?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
	[
		'title'    => 'Most popular acquisition & SEO features',
		'features' =>
			[
				[
					'name'        => 'Search Engine Keywords Performance',
					'description' => 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.',
					'price'       => '79EUR / 89USD',
					'url'         => 'https://plugins.matomo.org/SearchEngineKeywordsPerformance?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'SEO Web Vitals',
					'description' => 'Improve your website performance, rank higher in search results and optimise your visitor experience with SEO Web Vitals.',
					'price'       => '49EUR / 59USD',
					'url'         => 'https://plugins.matomo.org/SEOWebVitals?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
	[
		'title'    => '',
		'features' =>
			[
				[
					'name'        => 'Advertising Conversion Export',
					'description' => 'Provides an export of attributed goal conversions for usage in ad networks like Google Ads so you no longer need a conversion pixel.',
					'price'       => '89EUR / 99USD',
					'url'         => 'https://plugins.matomo.org/AdvertisingConversionExport?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Multi Attribution',
					'description' => 'Get a clear understanding of how much credit each of your marketing channel is actually responsible for to shift your marketing efforts wisely.',
					'price'       => '49EUR / 59USD',
					'url'         => 'https://plugins.matomo.org/MultiChannelConversionAttribution?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
	[
		'title'    => 'Other premium features',
		'features' =>
			[
				[
					'name'        => 'Funnels',
					'description' => 'Identify and understand where your visitors drop off to increase your conversions, sales and revenue with your existing traffic.',
					'price'       => '99EUR / 119USD',
					'url'         => 'https://plugins.matomo.org/Funnels?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Cohorts',
					'description' => 'Track your retention efforts over time and keep your visitors engaged and coming back for more.',
					'price'       => '49EUR / 59USD',
					'url'         => 'https://plugins.matomo.org/Cohorts?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
				[
					'name'        => 'Crash Analytics',
					'description' => 'Detect crashes to improve the user experience, increase conversions and recover revenue. Resolve them with insights to minimise developer hours.',
					'price'       => '79EUR / 89USD',
					'url'         => 'https://plugins.matomo.org/CrashAnalytics?wp=1&pk_campaign=WP&pk_source=Plugin',
					'image'       => '',
				],
			],
	],
];

matomo_show_tables( $matomo_feature_sections, $matomo_version, $matomo_currency );

?>
