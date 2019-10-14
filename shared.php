<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // if accessed directly
}

if ( ! defined( 'MATOMO_UPLOAD_DIR' ) ) {
	define( 'MATOMO_UPLOAD_DIR', 'matomo' );
}
if ( ! defined( 'MATOMO_CONFIG_PATH' ) ) {
	define( 'MATOMO_CONFIG_PATH', 'config/config.ini.php' );
}
if ( ! defined( 'MATOMO_JS_NAME' ) ) {
	define( 'MATOMO_JS_NAME', 'matomo.js' );
}
if ( ! defined( 'MATOMO_DATABASE_PREFIX' ) ) {
	define( 'MATOMO_DATABASE_PREFIX', 'matomo_' );
}
/**
 * @param string $className
 */
function matomo_plugin_autoloader( $className ) {
	$rootNamespace      = 'WpMatomo';
	$rootLen            = strlen( $rootNamespace ) + 1; // +1 for namespace separator
	$namespaceSeparator = '\\';

	if ( substr( $className, 0, $rootLen ) === $rootNamespace . $namespaceSeparator ) {
		$className = str_replace( '.', '', str_replace( $namespaceSeparator, DIRECTORY_SEPARATOR, substr( $className, $rootLen ) ) );
		require_once 'classes' . DIRECTORY_SEPARATOR . $rootNamespace . DIRECTORY_SEPARATOR . $className . '.php';
	}
}

spl_autoload_register( 'matomo_plugin_autoloader' );