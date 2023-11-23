<?php
/**
 * PHPUnit bootstrap file
 *
 * @package matomo
 */
/**
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 *
 * @todo find why this warning is triggered
 * phpcs:disable WordPress.Security.EscapeOutput.DeprecatedWhitelistCommentFound
 */
$tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $tests_dir ) {
	$tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

if ( ! defined( 'MATOMO_PHPUNIT_TEST' ) ) {
	define( 'MATOMO_PHPUNIT_TEST', true );
}

// Give access to tests_add_filter() function.
require_once $tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function manually_load_plugin() {
	if ( is_file( ABSPATH . '/wp-content/plugins/matomo/matomo.php' ) ) { // when the plugin is symlinked into wordpress
		define( 'MATOMO_ANALYTICS_FILE', ABSPATH . '/wp-content/plugins/matomo/matomo.php' );
	}

	require dirname( dirname( dirname( __FILE__ ) ) ) . '/matomo.php';
}

tests_add_filter( 'muplugins_loaded', 'manually_load_plugin' );

// Start up the WP testing environment.
require $tests_dir . '/includes/bootstrap.php';

require 'framework/test-case.php';
require 'framework/test-matomo-test-case.php';
