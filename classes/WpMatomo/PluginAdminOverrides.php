<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

/**
 * Customizations for the WordPress plugins page.
 */
class PluginAdminOverrides extends Feature {
	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function is_active() {
		return is_admin();
	}

	public function register_hooks() {
		if ( $this->is_plugins_php_page() ) {
			add_action( 'admin_footer', [ $this, 'add_data_deletion_notice_if_plugins_php' ] );
		}
	}

	public function add_data_deletion_notice_if_plugins_php() {
		$note                          = esc_html__( 'Note', 'matomo' );
		$change_settings_url           = home_url( '/wp-admin/admin.php?page=matomo-settings&tab=advanced' );
		$change_data_deletion_settings = esc_html__( 'Change data deletion settings.', 'matomo' );

		if ( $this->settings->should_delete_all_data_on_uninstall() ) {
			$deletion_setting_notice = esc_html__( 'Data will be permanently deleted upon plugin deletion.', 'matomo' );
		} else {
			$deletion_setting_notice = esc_html__( 'Data will %1$snot%2$s be deleted upon plugin deletion.', 'matomo' );
			$deletion_setting_notice = sprintf( $deletion_setting_notice, '<strong style="display:inline;">', '</strong>' );
		}

		// the interpolated values above are already escaped
		// phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
		echo <<<EOF
<script>
jQuery(document).ready(
  function () {
	var pStyles = '';

    var \$title = window.jQuery('body.plugins-php tr[data-slug="matomo"] td.plugin-title > strong:first-child');
	if (!\$title.length) { // WP > 7.0.2
		\$title = window.jQuery('body.plugins-php tr[data-slug="matomo"] th.plugin-title > strong:first-child');
		pStyles = ' style="margin: .5em 0;"';
	}
    \$title.after(`<p\${pStyles}><span style="margin: 0 2px 2px 0; display: inline-block; vertical-align: middle;">ℹ️</span> $note: $deletion_setting_notice<br/><a href="$change_settings_url" id="mwp-data-deletion-settings">$change_data_deletion_settings</a></p>`);
  }
);
</script>
EOF;
		// phpcs:enable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
	}

	private function is_plugins_php_page() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri      = wp_unslash( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
		$plugins_php_path = wp_parse_url( home_url(), PHP_URL_PATH ) . '/wp-admin/plugins.php';
		$current_path     = wp_parse_url( $request_uri, PHP_URL_PATH );

		return $plugins_php_path === $current_path;
	}
}
