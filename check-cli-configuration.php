<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) { // should only be called via CLI
	return;
}

function check_cli_configuration() {
	global $argv;

	if ( empty( $argv[0] ) ) { // sanity check
		return;
	}

	// dirname won't resolve symlinks, so using this instead of realpath + "/.."
	$path_to_wp = dirname( dirname( dirname( dirname( $argv[0] ) ) ) );
	if ( ! is_file( $path_to_wp . '/wp-config.php' ) ) {
		return;
	}

	require_once $path_to_wp . '/wp-config.php';
}

check_cli_configuration();
