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
 * TODO
 */
class PluginAdminOverrides {
	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'add_plugins_php_override_script' ] );
	}

	public function add_plugins_php_override_script() {
		if ( $this->is_plugins_php_page() ) {
			wp_enqueue_script( 'matomo_plugins_php_override', plugins_url( 'assets/js/plugins-admin.js', MATOMO_ANALYTICS_FILE ), [ 'jquery', 'jquery-ui-dialog' ], '1.0', true );
		}
	}

	private function is_plugins_php_page() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_uri      = wp_unslash( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '' );
		$plugins_php_path = wp_parse_url( home_url(), PHP_URL_PATH ) . '/wp-admin/plugins.php';
		$current_path     = wp_parse_url( $request_uri, PHP_URL_PATH );

		return $plugins_php_path === $current_path;
	}
}
