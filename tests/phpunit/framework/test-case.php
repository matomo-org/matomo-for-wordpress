<?php
/**
 * @package matomo
 */

use \WpMatomo\Capabilities;

class MatomoUnit_TestCase extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_delete_site' ) ) {
			function wp_delete_site( $site_id ) {
				wpmu_delete_blog( $site_id, true );
			}
		}

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
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

	protected function create_set_super_admin() {
		$logger = new \WpMatomo\Logger();
		$logger->log( 'creating super admin' );
		$id = self::factory()->user->create();

		wp_set_current_user( $id );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			grant_super_admin( $id );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		} else {
			$user->add_role( 'administrator' );
			$user->add_role( \WpMatomo\Roles::ROLE_SUPERUSER );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		}

		return $id;
	}

	/**
	 * Reset roles so they won't be stored across tests...
	 */
	protected function reset_roles() {
		foreach ( array( 'editor', 'author', 'contributor' ) as $role ) {
			get_role( $role )->remove_cap( Capabilities::KEY_SUPERUSER );
			get_role( $role )->remove_cap( Capabilities::KEY_WRITE );
			get_role( $role )->remove_cap( Capabilities::KEY_ADMIN );
			get_role( $role )->remove_cap( Capabilities::KEY_VIEW );
		}
	}

	/**
	 * @return string
	 */
	protected function get_type_attribute() {
		$type = '';
		if ( function_exists( 'wp_get_inline_script_tag' ) && ! is_admin() && ! current_theme_supports( 'html5', 'script' ) ) {
			$type = 'type="text/javascript"';
		}
		return $type;
	}
}
