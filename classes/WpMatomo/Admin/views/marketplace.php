<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\Marketplace\PopularFeatures;

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
					<svg class="matomo-primary-color-fill" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M10.72,19.9a8,8,0,0,1-6.5-9.79A7.77,7.77,0,0,1,10.4,4.16a8,8,0,0,1,9.49,6.52A1.54,1.54,0,0,0,21.38,12h.13a1.37,1.37,0,0,0,1.38-1.54,11,11,0,1,0-12.7,12.39A1.54,1.54,0,0,0,12,21.34h0A1.47,1.47,0,0,0,10.72,19.9Z"></path></svg>
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
		<div class="matomo-steps">
			<div id="matomo-step1" class="matomo-step">
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
			<div id="matomo-step2" class="matomo-step">
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
	</div>

	<h1 style="margin-top: 1em;"><?php esc_html_e( 'Most popular features', 'matomo' ); ?></h1>
	<p style="margin-bottom: 20px;"><?php esc_html_e( 'Developed by Matomo and partners, install these on top of your Matomo plugin for more advanced analytics.', 'matomo' ); ?></p>

	<?php
	( new PopularFeatures() )->show();
	?>
</div>
