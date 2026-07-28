<?php

use WpMatomo\Capabilities;

/**
 * @package matomo
 */
class MatomoUnit_WordPress_Fixture {
	public function set_up() {
		if ( ! function_exists( 'wp_delete_site' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
			function wp_delete_site( $site_id ) {
				wpmu_delete_blog( $site_id, true );
			}
		}

		$this->ensure_wp_version_check_is_not_run();

		set_current_screen( 'front' );

		$this->reset_roles();

		add_filter(
			'plugins_url',
			function ( $url ) {
				// workaround for https://github.com/wp-cli/wp-cli/issues/1037
				// WP is installed in tmp dir, but our plugin must be symlinked in actual dir, then plugin_basename
				// fails to remove the actual plugin path
				// replaces eg http://example.org/wp-content/plugins/Users/foobar/www/wordpress-tests/src/wp-content/plugins/matomo/app/matomo.js
				// with http://example.org/wp-content/plugins/matomo/app/matomo.js
				return str_replace( rtrim( dirname( plugin_dir_path( MATOMO_ANALYTICS_FILE ) ), '/' ), '', $url );
			}
		);
	}

	public function tear_down() {
		// empty
	}

	/**
	 * Reset roles so they won't be stored across tests...
	 */
	public function reset_roles() {
		foreach ( array( 'editor', 'author', 'contributor' ) as $role ) {
			get_role( $role )->remove_cap( Capabilities::KEY_SUPERUSER );
			get_role( $role )->remove_cap( Capabilities::KEY_WRITE );
			get_role( $role )->remove_cap( Capabilities::KEY_ADMIN );
			get_role( $role )->remove_cap( Capabilities::KEY_VIEW );
		}
	}

	public function switch_to_admin_page() {
		set_current_screen( 'edit-post' );
	}

	private function ensure_wp_version_check_is_not_run() {
		// the version check sends an HTTP request, and we don't need that to run during unit tests
		// see wp_version_check() in wp-includes/update.php
		$current                  = new stdClass();
		$current->updates         = array();
		$current->version_checked = function_exists( 'wp_get_wp_version' ) ? wp_get_wp_version() : getenv( 'WORDPRESS_VERSION' );
		$current->last_checked    = time();
		set_site_transient( 'update_core', $current );
	}
}
