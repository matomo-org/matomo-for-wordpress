<?php
/**
 * Plugin Name: Matomo Analytics & Tag Manager
 * Description: Most powerful web analytics for WordPress giving you 100% data ownership and privacy protection
 * Author: Matomo
 * Author URI: https://matomo.org
 * Version: 0.2.11
 * Domain Path: /languages
 * WC requires at least: 2.4.0
 * WC tested up to: 3.2.6
 *
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

load_plugin_textdomain( 'matomo', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'MATOMO_ANALYTICS_FILE' ) ) {
	define( 'MATOMO_ANALYTICS_FILE', __FILE__ );
}

if ( ! defined('MATOMO_MARKETPLACE_PLUGIN_NAME' )) {
	define( 'MATOMO_MARKETPLACE_PLUGIN_NAME', 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' );
}

$GLOBALS['MATOMO_PLUGINS_ENABLED'] = array();

/** MATOMO_PLUGIN_FILES => used to check for updates etc */
$GLOBALS['MATOMO_PLUGIN_FILES'] = array( MATOMO_ANALYTICS_FILE );

function matomo_has_compatible_content_dir() {
	return (defined( 'WP_CONTENT_DIR' )
	       && ABSPATH . 'wp-content' === rtrim( WP_CONTENT_DIR, '/' ))
	       || ( !empty( $_ENV['MATOMO_WP_ROOT_PATH'] ) && is_dir( $_ENV['MATOMO_WP_ROOT_PATH'] ) );
}

function matomo_is_app_request() {
	return ! empty( $_SERVER['SCRIPT_NAME'] )
	&& ( substr( $_SERVER['SCRIPT_NAME'], - 1 * strlen( 'matomo/app/index.php' ) ) === 'matomo/app/index.php' );
}

function matomo_has_tag_manager() {
	if ( defined( 'MATOMO_DISABLE_TAG_MANAGER' ) && MATOMO_DISABLE_TAG_MANAGER ) {
		return false;
	}

	$is_multisite = function_exists( 'is_multisite' ) && is_multisite();
	if ( $is_multisite ) {
		return false;
	}

	return true;
}

$GLOBALS['MATOMO_MARKETPLACE_PLUGINS'] = array();

function matomo_add_plugin( $plugins_directory, $wp_plugin_file, $is_marketplace_plugin = false ) {
	if ( ! in_array( $wp_plugin_file, $GLOBALS['MATOMO_PLUGIN_FILES'], true ) ) {
		$GLOBALS['MATOMO_PLUGIN_FILES'][] = $wp_plugin_file;
	}

	if ( empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ) {
		$GLOBALS['MATOMO_PLUGIN_DIRS'] = array();
	}

	if ( $is_marketplace_plugin && dirname( $wp_plugin_file ) === $plugins_directory ) {
		$GLOBALS['MATOMO_MARKETPLACE_PLUGINS'][] = $wp_plugin_file;
	}

	$GLOBALS['MATOMO_PLUGINS_ENABLED'][] = basename( $plugins_directory );
	$root_dir                            = dirname( $plugins_directory );
	foreach ( $GLOBALS['MATOMO_PLUGIN_DIRS'] as $path ) {
		if ( $path['pluginsPathAbsolute'] === $root_dir ) {
			return; // already added
		}
	}

	$matomo_dir       = __DIR__ . '/app';
	$matomo_dir_parts = explode( '/', $matomo_dir );
	$root_dir_parts   = explode( '/', $root_dir );
	$webroot_dir      = '';
	foreach ( $matomo_dir_parts as $index => $part ) {
		if ( isset( $root_dir_parts[ $index ] ) && $root_dir_parts[ $index ] === $part ) {
			continue;
		}
		$webroot_dir .= '../';
	}
	$GLOBALS['MATOMO_PLUGIN_DIRS'][] = array(
		'pluginsPathAbsolute'        => $root_dir,
		'webrootDirRelativeToMatomo' => $webroot_dir,
	);
}


require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'WpMatomo.php';
require 'shared.php';
matomo_add_plugin( __DIR__ . '/plugins/WordPress', MATOMO_ANALYTICS_FILE );
new WpMatomo();

// todo remove this before release
require 'plugin-update-checker/plugin-update-checker.php';
$matomo_update_checker = Puc_v4_Factory::buildUpdateChecker(
	'https://builds.matomo.org/wordpress-beta.json',
	__FILE__,
	'matomo'
);
