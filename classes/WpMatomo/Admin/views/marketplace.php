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
/** @var string $matomo_marketplace_url */
?>
<?php if ( ! empty( $valid_tabs ) ) { ?>
<h2 class="nav-tab-wrapper" style="margin-bottom:1em;">
	<?php if ( in_array( 'marketplace', $valid_tabs, true ) ) { ?>
		<a href="?page=matomo-marketplace&tab=marketplace"
		   class="nav-tab <?php echo ( 'marketplace' === $active_tab ) ? 'nav-tab-active' : ''; ?>"
		><?php esc_html_e( 'Welcome', 'matomo' ); ?></a>
	<?php } ?>
	<?php if ( in_array( 'install', $valid_tabs, true ) ) { ?>
		<a href="?page=matomo-marketplace&tab=install"
		   class="nav-tab <?php echo ( 'install' === $active_tab ) ? 'nav-tab-active' : ''; ?>"
		><?php esc_html_e( 'Marketplace', 'matomo' ); ?></a>
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
if ( isset( $marketplace_setup_wizard ) && 'marketplace' !== $active_tab ) {
	$marketplace_setup_wizard->show();
	return;
}
?>

<style>
	#matomo-for-marketplace-welcome {
		font-size: 16px;
		margin-left: 8px;
	}

	#matomo-for-marketplace-welcome > h1:first-child {
		margin-top: 20px;
	}

	#matomo-welcome-marketplace-setup {
		display: flex;
		flex-direction: row;
		justify-content: space-between;
		align-items: stretch;
		padding: 2em 1.5em;
		border-radius: 6px;
		background-color: white;
		margin-top: 2em;
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

	#matomo-for-marketplace-welcome > p {
		margin-top: .25em;
		margin-bottom: .5em;
		max-width: 700px;
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
	}

	.step-number:not(.current) {
		background-color: #777;
	}

	.wizard-waiting-for {
		display: inline-flex;
		flex-direction: row;
		align-items: center;
		border-radius: 16px;
		border: solid 1px #deecfe;
		padding: 6px 15px;
		font-size: 14px;
		visibility: hidden;
	}

	.wizard-waiting-for.active {
		visibility: visible;
	}

	.wizard-waiting-for svg {
		width: 16px;
		height: 16px;
		margin-right: 4px;
	}

	.matomo-popular-feature {
		background-color: white;
		border-radius: 6px;
		display: flex;
		flex-direction: row;
		align-items: center;
		padding: 1.6em 1.5em;
		margin-bottom: 20px;
	}

	.matomo-popular-feature h3 {
		margin: 0;
		font-weight: 500;
		font-size: 17px;
	}

	.matomo-popular-feature p {
		margin-bottom: 0;
		max-width: 700px;
	}

	.matomo-popular-feature .description {
		flex: 1;
	}
	/** TODO: responsiveness */
</style>
<script>
	window.jQuery(document).ready(function ($) {
		$('body').on('click', '.download-plugin', function (e) {
			if ($(e.target).is('.button-secondary')) {
				return;
			}

			var step = $(e.target).closest('#matomo-step1');
			step.find('.step-number').removeClass('current').removeClass('matomo-primary-color-bg');
			step.find('.button-primary').removeClass('button-primary').addClass('button-secondary');

			var step2 = step.siblings('#matomo-step2');
			step2.find('.step-number').addClass('current').addClass('matomo-primary-color-bg');
			step2.find('.button-secondary').removeClass('button-secondary').addClass('button-primary');
		});
	});
</script>
<div id="matomo-for-marketplace-welcome">
	<h1><?php matomo_header_icon(); ?><?php esc_html_e( 'What is the Matomo for WordPress Marketplace', 'matomo' ); ?></h1>

	<p>
		<?php esc_html_e( 'Matomo for WordPress includes core analytics to understand your visitors, behaviour, acquisition and ecommerce performance.', 'matomo' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'As your needs grow, you can extend your analytics with additional Matomo features.', 'matomo' ); ?>
	</p>
	<p>
		<?php esc_html_e( 'The Marketplace lets you discover and install these features directly in Matomo for WordPress, so you can unlock more advanced insights when you need them.', 'matomo' ); ?>
	</p>

	<div id="matomo-welcome-marketplace-setup" class="matomo-marketplace-wizard-body">
		<div id="matomo-setup-preface">
			<div id="matomo-setup-preface-title">
				<img src="<?php echo esc_attr( plugins_url( '/assets/img/logo.png', MATOMO_ANALYTICS_FILE ) ); ?>" alt="Matomo Logo" />
				<h2>
					<?php esc_html_e( 'Setup the Matomo Marketplace in two easy steps', 'matomo' ); ?>
				</h2>
			</div>
			<p>
				<?php esc_html_e( 'Discover more than 100 advanced analytics features built by Matomo and its community.', 'matomo' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Install and manage these features directly in Matomo for WordPress to extend your analytics as your needs grow.', 'matomo' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Follow these steps to install the Marketplace and start unlocking additional capabilities.', 'matomo' ); ?>
			</p>

			<div class="matomo-setup-divider"></div>
			<p class="matomo-smaller-text">
				<?php
				echo sprintf(
					esc_html__( 'Don\'t want to use the plugin? Download directly %1$son our marketplace,%2$s but keep in mind, you won\'t receive automatic updates unless you use the Matomo Marketplace plugin.', 'matomo' ),
					'<a href="https://plugins.matomo.org/?wp=1" target="_blank" rel="noreferrer noopener">',
					'</a>'
				);
				?>
			</p>
			<div>
				<div class="wizard-waiting-for matomo-primary-color-fg">
					<!-- TODO: change to css animation -->
					<svg class="matomo-primary-color-fill" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10.72,19.9a8,8,0,0,1-6.5-9.79A7.77,7.77,0,0,1,10.4,4.16a8,8,0,0,1,9.49,6.52A1.54,1.54,0,0,0,21.38,12h.13a1.37,1.37,0,0,0,1.38-1.54,11,11,0,1,0-12.7,12.39A1.54,1.54,0,0,0,12,21.34h0A1.47,1.47,0,0,0,10.72,19.9Z"><animateTransform attributeName="transform" type="rotate" dur="0.75s" values="0 12 12;360 12 12" repeatCount="indefinite"/></path></svg>
					<span class="waiting-for-install" style="display: none;">
						<?php esc_html_e( 'Waiting for plugin installation', 'matomo' ); ?>...
					</span>
					<span class="waiting-for-activation" style="display: none;">
						<?php esc_html_e( 'Waiting for plugin activation', 'matomo' ); ?>...
					</span>
					<span class="wizard-reloading" style="display: none;">
						<?php esc_html_e( 'Reloading page', 'matomo' ); ?>...
					</span>
				</div>
			</div>
		</div>
		<div id="matomo-step1">
			<div>
				<span class="step-number current matomo-primary-color-bg">1</span>
				<span><?php esc_html_e( 'Download Plugin', 'matomo' ); ?></span>
			</div>
			<p>
				<?php esc_html_e( 'Download the Matomo Marketplace for WordPress plugin as a .zip file to your computer.', 'matomo' ); ?>
			</p>
			<div>
				<a href="<?php echo esc_attr( $matomo_marketplace_url ); ?>" rel="noreferrer noopener" class="download-plugin">
					<button class="button-primary"><?php esc_html_e( 'Download .zip', 'matomo' ); ?></button>
				</a>
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
				<a class="open-plugin-upload button-secondary" href="plugin-install.php?tab=upload&mtm_marketplace_install=1" target="_blank">
					<?php esc_html_e( 'Go to Plugins', 'matomo' ); ?>
				</a>
			</div>
		</div>
	</div>

	<h1 style="margin-top: 1em;"><?php esc_html_e( 'Most popular features', 'matomo' ); ?></h1>
	<p style="margin-bottom: 20px;"><?php esc_html_e( 'Developed by Matomo and partners, install these on top of your Matomo plugin for more advanced analytics.', 'matomo' ); ?></p>

	<?php
	$matomo_popular_features = [
		'MarketingCampaignsReporting'     => [
			'name' => __( 'Marketing Campaigns Reporting', 'matomo' ),
			'desc' => __( "Measure the effectiveness of your marketing campaigns. Track up to five channels instead of two: campaign, source, medium, keyword, content.', 'matomo'", 'matomo' ),
		],
		'SearchEngineKeywordsPerformance' => [
			'name'  => __( 'Search Engine Keywords Performance', 'matomo' ),
			'desc'  => __( 'All keywords searched by your users on search engines are now visible into your Referrers reports! The ultimate solution to \'Keyword not defined\'.', 'matomo' ),
			'price' => '79EUR / 89USD',
		],
		'HeatmapSessionRecording'         => [
			'name'  => __( 'Heatmap & Session Recording', 'matomo' ),
			'desc'  => __( 'Truly understand your visitors by seeing where they click, hover, type and scroll. Replay their actions in a video and ultimately increase conversions.', 'matomo' ),
			'price' => '109EUR / 129USD',
		],
		'CustomAlerts'                    => [
			'name' => __( 'Custom Alerts', 'matomo' ),
			'desc' => __( 'Create custom Alerts to be notified of important changes on your website or app!', 'matomo' ),
		],
		'MediaAnalytics'                  => [
			'name'  => __( 'Media Analytics', 'matomo' ),
			'desc'  => __( 'Grow your business with advanced video & audio analytics. Get powerful insights into how your audience watches your videos and listens to your audio.', 'matomo' ),
			'price' => '89EUR / 99USD',
		],
		'CustomReports'                   => [
			'name'  => __( 'Custom Reports', 'matomo' ),
			'desc'  => __( 'Pull out the information you need in order to be successful. Develop your custom strategy to meet your individualized goals while saving money & time.', 'matomo' ),
			'price' => '109EUR / 129USD',
		],
		'WpPremiumBundle'                 => [
			'name'  => __( 'WordPress Premium Bundle', 'matomo' ),
			'desc'  => __( 'All premium features in one bundle, make the most out of your Matomo for WordPress and enjoy discounts of up to 25%!', 'matomo' ),
			'price' => '549EUR / 639USD',
		],
		'UsersFlow'                       => [
			'name'  => __( 'Users Flow', 'matomo' ),
			'desc'  => __( 'Users Flow is a visual representation of the most popular paths your users take through your website & app which lets you understand your users needs.', 'matomo' ),
			'price' => '49EUR / 59USD',
		],
	];
	?>

	<?php foreach ( $matomo_popular_features as $matomo_feature_slug => $matomo_feature_info ) { ?>
	<div class="matomo-popular-feature">
		<div class="description">
			<h3 class="matomo-primary-color-fg"><?php echo esc_html( $matomo_feature_info['name'] ); ?></h3>
			<p><?php echo esc_html( $matomo_feature_info['desc'] ); ?></p>
		</div>

		<a href="<?php echo esc_attr( 'https://plugins.matomo.org/' . $matomo_feature_slug . '?wp=1' ); ?>" target="_blank">
			<button class="button-primary"><?php esc_html_e( 'Learn more', 'matomo' ); ?></button>
		</a>
	</div>
	<?php } ?>
</div>
