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

/** @var string $matomo_marketplace_url */
/** @var bool $matomo_show_title */
?>
<div
	id="matomo-welcome-marketplace-setup"
	class="matomo-marketplace-wizard-body <?php echo esc_attr( $matomo_show_title ? '' : 'embedded' ); ?>"
>
	<div id="matomo-setup-preface">
		<?php if ( $matomo_show_title ) { ?>
		<div id="matomo-setup-preface-title">
			<img src="<?php echo esc_attr( plugins_url( '/assets/img/logo.png', MATOMO_ANALYTICS_FILE ) ); ?>" alt="Matomo Logo" />
			<h2>
				<?php esc_html_e( 'Setup the Matomo Marketplace in two easy steps', 'matomo' ); ?>
			</h2>
		</div>
		<?php } ?>
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
