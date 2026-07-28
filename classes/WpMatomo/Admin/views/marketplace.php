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
/** @var \WpMatomo\Admin\MarketplaceSetupWizardBody $matomo_marketplace_setup_wizard_body */
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

	<?php
	$matomo_marketplace_setup_wizard_body->show();
	?>

	<h1 style="margin-top: 1em;"><?php esc_html_e( 'Most popular features', 'matomo' ); ?></h1>
	<p style="margin-bottom: 20px;"><?php esc_html_e( 'Developed by Matomo and partners, install these on top of your Matomo plugin for more advanced analytics.', 'matomo' ); ?></p>

	<?php
	( new PopularFeatures() )->show();
	?>
</div>
