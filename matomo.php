<?php
/**
 * Plugin Name: Matomo Analytics - Ethical Stats. Powerful Insights.
 * Description: The #1 Google Analytics alternative that gives you full control over your data and protects the privacy for your users. Free, secure and open.
 * Author: Matomo
 * Author URI: https://matomo.org
 * Version: 4.15.1
 * Domain Path: /languages
 * WC requires at least: 2.4.0
 * WC tested up to: 7.7.0
 *
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

load_plugin_textdomain( 'matomo', false, basename( dirname( __FILE__ ) ) . '/languages' );

if ( ! defined( 'MATOMO_ANALYTICS_FILE' ) ) {
	define( 'MATOMO_ANALYTICS_FILE', __FILE__ );
}

if ( ! defined( 'MATOMO_MARKETPLACE_PLUGIN_NAME' ) ) {
	define( 'MATOMO_MARKETPLACE_PLUGIN_NAME', 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' );
}

$GLOBALS['MATOMO_PLUGINS_ENABLED'] = array();

/** MATOMO_PLUGIN_FILES => used to check for updates etc */
$GLOBALS['MATOMO_PLUGIN_FILES'] = array( MATOMO_ANALYTICS_FILE );

function matomo_has_compatible_content_dir() {
	if ( ! empty( $_SERVER['MATOMO_WP_ROOT_PATH'] )
		 && file_exists( rtrim( $_SERVER['MATOMO_WP_ROOT_PATH'], '/' ) . '/wp-load.php' ) ) {
		return true;
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		return false;
	}

	$content_dir = rtrim( rtrim( WP_CONTENT_DIR, '/' ), DIRECTORY_SEPARATOR );
	$content_dir = wp_normalize_path( $content_dir );
	$abs_path    = wp_normalize_path( ABSPATH );

	$abs_paths = array(
		$abs_path . 'wp-content',
		$abs_path . '/wp-content',
		$abs_path . DIRECTORY_SEPARATOR . 'wp-content',
	);

	if ( in_array( $content_dir, $abs_paths, true ) ) {
		 return true;
	}

	$wpload_base = '../../../wp-load.php';
	$wpload_full = dirname( __FILE__ ) . '/' . $wpload_base;
	if ( file_exists( $wpload_full ) && is_readable( $wpload_full ) ) {
		return true;
	} elseif ( realpath( $wpload_full ) && file_exists( realpath( $wpload_full ) ) && is_readable( realpath( $wpload_full ) ) ) {
		return true;
	} elseif ( ! empty( $_SERVER['SCRIPT_FILENAME'] ) && file_exists( $_SERVER['SCRIPT_FILENAME'] ) ) {
		// seems symlinked... eg the wp-content dir or wp-content/plugins dir is symlinked from some very much other place...
		$wpload_full = dirname( $_SERVER['SCRIPT_FILENAME'] ) . '/' . $wpload_base;
		if ( file_exists( $wpload_full ) ) {
			return true;
		} elseif ( realpath( $wpload_full ) && file_exists( realpath( $wpload_full ) ) ) {
			return true;
		} elseif ( file_exists( dirname( $_SERVER['SCRIPT_FILENAME'] ) ) . '/wp-load.php' ) {
			return true;
		}
	}

	// look in plugins directory if there is a config file for us
	$wpload_config = dirname( __FILE__ ) . '/../matomo.wpload_dir.php';
	if ( file_exists( $wpload_config ) && is_readable( $wpload_config ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = @file_get_contents( $wpload_config ); // we do not include that file for security reasons
		if ( ! empty( $content ) ) {
			$content = str_replace( array( '<?php', 'exit;' ), '', $content );
			$content = preg_replace( '/\s/', '', $content );
			$content = trim( ltrim( trim( $content ), '#' ) ); // the path may be commented out # /abs/path
			if ( strpos( $content, DIRECTORY_SEPARATOR ) === 0 ) {
				$wpload_file = rtrim( $content, DIRECTORY_SEPARATOR ) . '/wp-load.php';
				return file_exists( $wpload_file ) && is_readable( $wpload_file );
			}
		}
	}

	return false;
}

function matomo_header_icon( $full = false ) {
	$file = 'logo';
	if ( $full ) {
		$file = 'logo-full';
	}
	echo '<img height="32" src="' . esc_url( plugins_url( 'assets/img/' . $file . '.png', MATOMO_ANALYTICS_FILE ) ) . '" class="matomo-header-icon">';
}

function matomo_is_app_request() {
	return ! empty( $_SERVER['SCRIPT_NAME'] )
	&& ( substr( $_SERVER['SCRIPT_NAME'], - 1 * strlen( 'matomo/app/index.php' ) ) === 'matomo/app/index.php' );
}

function matomo_has_tag_manager() {
	if ( defined( 'MATOMO_ENABLE_TAG_MANAGER' ) ) {
		return ! empty( MATOMO_ENABLE_TAG_MANAGER );
	}

	$is_multisite = function_exists( 'is_multisite' ) && is_multisite();
	if ( $is_multisite ) {
		return false;
	}

	return true;
}

function matomo_anonymize_value( $value ) {
	if ( is_string( $value ) && ! empty( $value ) ) {
		$values_to_anonymize = array(
			ABSPATH                                  => '$abs_path/',
			str_replace( '/', '\/', ABSPATH )        => '$abs_path\/',
			str_replace( '/', '\\', ABSPATH )        => '$abs_path\/',
			WP_CONTENT_DIR                           => '$WP_CONTENT_DIR/',
			str_replace( '/', '\\', WP_CONTENT_DIR ) => '$WP_CONTENT_DIR\\',
			home_url()                               => '$home_url',
			site_url()                               => '$site_url',
			DB_PASSWORD                              => '$DB_PASSWORD',
			DB_USER                                  => '$DB_USER',
			DB_HOST                                  => '$DB_HOST',
			DB_NAME                                  => '$DB_NAME',
		);
		$keys                = array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'AUTH_SALT', 'NONCE_KEY', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' );
		foreach ( $keys as $key ) {
			if ( defined( $key ) ) {
				$const_value = constant( $key );
				if ( ! empty( $const_value ) && is_string( $const_value ) && strlen( $key ) > 3 ) {
					$values_to_anonymize[ $const_value ] = '$' . $key;
				}
			}
		}
		foreach ( $values_to_anonymize as $search => $replace ) {
			if ( $search ) {
				$value = str_replace( $search, $replace, $value );
			}
		}
		// replace anything like token_auth etc or md5 or sha1 ...
		$value = preg_replace( '/[[:xdigit:]]{31,80}/', 'TOKEN_REPLACED', $value );
	}

	return $value;
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

	$matomo_dir       = __DIR__ . DIRECTORY_SEPARATOR . 'app';
	$matomo_dir_parts = explode( DIRECTORY_SEPARATOR, $matomo_dir );
	$root_dir_parts   = explode( DIRECTORY_SEPARATOR, $root_dir );
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

if ( matomo_is_app_request() || ! empty( $GLOBALS['MATOMO_LOADED_DIRECTLY'] ) ) {
	// prevent layout being broken when thegem theme is used. their lazy items class causes the reporting UI to not appear
	// because it creates a JS error because of escaping " too often. only breaks when " Activate image loading optimization (for desktops)"
	// is enabled in the general theme settings
	add_filter( 'thegem_lazy_items_need_process_content', '__return_false', 99999999, $args = 0 );
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'WpMatomo.php';
require 'shared.php';
matomo_add_plugin( __DIR__ . '/plugins/WordPress', MATOMO_ANALYTICS_FILE );
new WpMatomo();
