<?php
/*
 * Plugin Name: Matomo Analytics & Tag Manager
 * Description: Most powerful web analytics for WordPress giving you 100% data ownership and privacy protection
 * Author: Matomo
 * Author URI: https://matomo.org
 * Version: 0.2.4
 * Domain Path: /languages
 * WC requires at least: 2.4.0
 * WC tested up to: 3.2.6
 *
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

load_plugin_textdomain( 'matomo', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'MATOMO_ANALYTICS_FILE' ) ) {
	define( 'MATOMO_ANALYTICS_FILE', __FILE__ );
}

$GLOBALS['MATOMO_PLUGINS_ENABLED'] = array();

/** MATOMO_PLUGIN_FILES => used to check for updates etc */
$GLOBALS['MATOMO_PLUGIN_FILES'] = array( MATOMO_ANALYTICS_FILE );

function has_matomo_compatible_content_dir() {
	return defined( 'WP_CONTENT_DIR' ) && ABSPATH . 'wp-content' === rtrim( WP_CONTENT_DIR, '/' );
}

function is_matomo_app_request() {
	return ! empty( $_SERVER['SCRIPT_NAME'] )
	&& ( substr( $_SERVER['SCRIPT_NAME'], - 1 * strlen( 'matomo/app/index.php' ) ) === 'matomo/app/index.php' );
}

function has_matomo_tag_manager() {
	if ( defined( 'MATOMO_DISABLE_TAG_MANAGER' ) && MATOMO_DISABLE_TAG_MANAGER ) {
		return false;
	}

	$is_multisite = function_exists( 'is_multisite' ) && is_multisite();
	if ( $is_multisite ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		$network_enabled = is_plugin_active_for_network( 'matomo/matomo.php' );

		return false;
	}

	return true;
}

function add_matomo_plugin( $plugins_directory, $wp_plugin_file ) {
	if ( ! in_array( $wp_plugin_file, $GLOBALS['MATOMO_PLUGIN_FILES'] ) ) {
		$GLOBALS['MATOMO_PLUGIN_FILES'][] = $wp_plugin_file;
	}

	if ( empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ) {
		$GLOBALS['MATOMO_PLUGIN_DIRS'] = array();
	}

	$GLOBALS['MATOMO_PLUGINS_ENABLED'][] = basename( $plugins_directory );
	$rootDir                             = dirname( $plugins_directory );
	foreach ( $GLOBALS['MATOMO_PLUGIN_DIRS'] as $path ) {
		if ( $path['pluginsPathAbsolute'] === $rootDir ) {
			return; // already added
		}
	}

	$matomoDir      = __DIR__ . '/app';
	$matomoDirParts = explode( '/', $matomoDir );
	$rootDirParts   = explode( '/', $rootDir );
	$webrootDir     = '';
	foreach ( $matomoDirParts as $index => $part ) {
		if ( isset( $rootDirParts[ $index ] ) && $rootDirParts[ $index ] === $part ) {
			continue;
		}
		$webrootDir .= '../';
	}
	$GLOBALS['MATOMO_PLUGIN_DIRS'][] = array(
		'pluginsPathAbsolute'        => $rootDir,
		'webrootDirRelativeToMatomo' => $webrootDir,
	);
}


require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'WpMatomo.php';
include 'shared.php';
add_matomo_plugin( __DIR__ . '/plugins/WordPress', MATOMO_ANALYTICS_FILE );
new WpMatomo();

// todo remove this before release
require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://builds.matomo.org/wordpress-beta.json',
	__FILE__,
	'matomo'
);
