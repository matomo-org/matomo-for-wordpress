<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

// if uninstall.php is not called by WordPress, die.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

require 'shared.php';

$matomo_is_using_multi_site    = function_exists( 'is_multisite' ) && is_multisite();
$matomo_should_remove_all_data = defined( 'MATOMO_REMOVE_ALL_DATA' ) && MATOMO_REMOVE_ALL_DATA === true;

$matomo_uninstaller = new \WpMatomo\Uninstaller();
$matomo_uninstaller->uninstall( $matomo_should_remove_all_data );
