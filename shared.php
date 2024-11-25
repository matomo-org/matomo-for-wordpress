<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
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

function matomo_is_plugin_compatible( $wp_plugin_file ) {
	require_once __DIR__ . '/app/core/Version.php';

	$plugin_manifest_path = dirname( $wp_plugin_file ) . '/plugin.json';
	clearstatcache( false, $plugin_manifest_path );

	if ( ! is_file( $plugin_manifest_path )
		|| ! is_readable( $plugin_manifest_path )
	) {
		return false;
	}

	$modified_time = filemtime( $plugin_manifest_path );
	if ( false === $modified_time ) {
		return false;
	}

	$cache_key   = 'matomo_plugin_compatible_' . basename( $wp_plugin_file ) . '_' . \Piwik\Version::VERSION . '_' . $modified_time;
	$cache_value = get_transient( $cache_key );
	if ( false === $cache_value ) {
		// assume the plugin is not compatible in case the below code fails.
		// this way, the next request will work rather than trigger the same
		// error.
		$one_day = 24 * 60 * 60;
		set_transient( $cache_key, 0, $one_day );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$plugin_manifest = file_get_contents( $plugin_manifest_path );
		$plugin_manifest = json_decode( $plugin_manifest, true );
		if ( empty( $plugin_manifest['require']['matomo'] )
			&& empty( $plugin_manifest['require']['piwik'] )
		) {
			return false;
		}

		$core_requirement = isset( $plugin_manifest['require']['matomo'] )
			? $plugin_manifest['require']['matomo']
			: $plugin_manifest['require']['piwik'];

		require_once __DIR__ . '/app/vendor/autoload.php';

		$dependency           = new \Piwik\Plugin\Dependency();
		$missing_dependencies = $dependency->getMissingDependencies( [ 'matomo' => $core_requirement ] );

		$is_compatible = empty( $missing_dependencies );
		$cache_value   = (int) $is_compatible;

		$two_months = 60 * 60 * 24 * 60;
		set_transient( $cache_key, $cache_value, $two_months );
	}

	return 1 === (int) $cache_value;
}

function matomo_filter_incompatible_plugins( &$plugin_list ) {
	if ( empty( $GLOBALS['MATOMO_MARKETPLACE_PLUGINS'] ) ) {
		return;
	}

	$incompatible_plugins = [];
	foreach ( $GLOBALS['MATOMO_MARKETPLACE_PLUGINS'] as $wp_plugin_file ) {
		if ( matomo_is_plugin_compatible( $wp_plugin_file ) ) {
			continue;
		}

		$plugin_name            = basename( dirname( $wp_plugin_file ) );
		$incompatible_plugins[] = $plugin_name;
	}

	$plugin_list = array_values( array_diff( $plugin_list, $incompatible_plugins ) );
}

/**
 * @param string $class_name
 */
function matomo_plugin_autoloader( $class_name ) {
	$root_namespace      = 'WpMatomo';
	$root_len            = strlen( $root_namespace ) + 1; // +1 for namespace separator
	$namespace_separator = '\\';

	if ( substr( $class_name, 0, $root_len ) === $root_namespace . $namespace_separator ) {
		$class_name = str_replace( '.', '', str_replace( $namespace_separator, DIRECTORY_SEPARATOR, substr( $class_name, $root_len ) ) );
		require_once __DIR__ . '/classes' . DIRECTORY_SEPARATOR . $root_namespace . DIRECTORY_SEPARATOR . $class_name . '.php';
	}
}

spl_autoload_register( 'matomo_plugin_autoloader' );
