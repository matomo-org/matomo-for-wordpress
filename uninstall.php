<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

// if uninstall.php is not called by WordPress, die
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

include 'shared.php';

$isUsingMultiSite    = function_exists( 'is_multisite' ) && is_multisite();
$shouldRemoveAllData = defined( 'MATOMO_REMOVE_ALL_DATA' ) && MATOMO_REMOVE_ALL_DATA === true;

$uninstaller = new \WpMatomo\Uninstaller();
$uninstaller->uninstall( $shouldRemoveAllData );
