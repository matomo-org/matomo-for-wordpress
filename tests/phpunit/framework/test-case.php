<?php
/**
 * @package Matomo_Analytics
 */


class MatomoUnit_TestCase extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		add_filter( 'plugins_url', function ( $url ) {
			// workaround for https://github.com/wp-cli/wp-cli/issues/1037
			// WP is installed in tmp dir, but our plugin must be symlinked in actual dir, then plugin_basename
			// fails to remove the actual plugin path
			// replaces eg http://example.org/wp-content/plugins/Users/foobar/www/wordpress-tests/src/wp-content/plugins/matomo/app/matomo.js
			// with http://example.org/wp-content/plugins/matomo/app/matomo.js
			return str_replace( rtrim( dirname( plugin_dir_path( MATOMO_ANALYTICS_FILE ) ), '/' ), '', $url );
		} );
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

}
