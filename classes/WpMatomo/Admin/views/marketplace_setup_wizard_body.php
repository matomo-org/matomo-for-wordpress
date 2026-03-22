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

/**
 * @var bool $user_can_upload_plugins
 * @var bool $user_can_activate_plugins
 * @var bool $is_plugin_installed
 * @var bool $matomo_show_title
 * @var bool $matomo_is_plugin_active
 */
?>
<div class="matomo-marketplace-wizard-body">
	<?php if ( $user_can_upload_plugins && ! $is_plugin_installed ) { ?>
		<?php if ( $matomo_show_title ) { ?>
		<h1><?php esc_html_e( 'Setup the Matomo Marketplace in two easy steps', 'matomo' ); ?></h1>
		<?php } ?>

		<div class="wizard-steps-header">
			<p class="step-title">Step 1</p>
			<div class="divider"></div>
			<p class="step-title">Step 2</p>
		</div>
		<div class="wizard-steps">
			<div class="step">

				<p><?php echo sprintf( esc_html__( 'Download the %1$sMatomo Marketplace for WordPress%2$s plugin.', 'matomo' ), '<em>', '</em>' ); ?></p>

				<a class="button-primary download-plugin" rel="noreferrer noopener" target="_blank" href="https://builds.matomo.org/matomo-marketplace-for-wordpress-latest.zip">
					<?php esc_html_e( 'Download', 'matomo' ); ?>
				</a>
			</div>

			<div class="divider"></div>

			<div class="step">
				<p><?php esc_html_e( 'Upload and install the plugin.', 'matomo' ); ?></p>

				<a class="button-primary open-plugin-upload" target="_blank" href="plugin-install.php?tab=upload">
					<?php esc_html_e( 'Go to plugins admin', 'matomo' ); ?> →
				</a>
			</div>
		</div>
		<div class="wizard-footer">
			<p><em>
					<?php
					echo sprintf(
						esc_html__( 'Don\'t want to use the Matomo Marketplace? You can download Matomo plugins directly on %1$sour marketplace%2$s, but keep in mind, you won\'t receive automatic updates unless you use the Matomo Marketplace plugin.', 'matomo' ),
						'<a target="_blank" rel="noreferrer noopener" href="https://plugins.matomo.org/?wp=1">',
						'</a>'
					);
					?>
				</em></p>
			<p class="wizard-waiting-for" style="display:none;">
				<strong><?php esc_html_e( 'Waiting for plugin activation...', 'matomo' ); ?></strong>
			</p>
			<p class="wizard-reloading" style="display:none;">
				<strong><?php esc_html_e( 'Loading marketplace...', 'matomo' ); ?></strong>
			</p>
		</div>
	<?php } elseif ( $user_can_activate_plugins && $is_plugin_installed && ! $matomo_is_plugin_active ) { ?>
		<?php if ( $matomo_show_title ) { ?>
		<h1><?php esc_html_e( 'Activate the Matomo Marketplace for WordPress plugin', 'matomo' ); ?></h1>
		<?php } ?>

		<p><?php esc_html_e( 'The Matomo Marketplace plugin is installed but not active. Activate it by clicking the button below.', 'matomo' ); ?></p>

		<p>
			<a class="button-primary activate-plugin" rel="noreferrer noopener" href="">
				<?php esc_html_e( 'Activate', 'matomo' ); ?>
			</a>
		</p>
		<p class="wizard-waiting-for" style="display:none;">
			<?php esc_html_e( 'Waiting for plugin activation...', 'matomo' ); ?>
		</p>
		<p class="wizard-reloading" style="display:none;">
			<?php esc_html_e( 'Loading marketplace...', 'matomo' ); ?>
		</p>
	<?php } elseif ( ! $matomo_is_plugin_active ) { ?>
		<p>
			<?php
			echo sprintf(
				esc_html__( 'To manage Matomo plugins from the Matomo Marketplace, the %1$sMatomo Marketplace for WordPress%2$s must be installed.', 'matomo' ),
				'<a href="https://matomo.org/faq/wordpress/how-do-i-install-a-matomo-marketplace-plugin-in-matomo-for-wordpress/" target="_blank" rel="noreferrer noopener">',
				'</a>'
			);
			?>
		</p>
		<p><?php esc_html_e( 'Unfortunately, you do not appear to have the ability to upload plugin archives. Please ask your WordPress site administrator to complete this setup for you.', 'matomo' ); ?></p>
	<?php } ?>
</div>
