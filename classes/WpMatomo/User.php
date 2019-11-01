<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class User {

	const USER_MAPPING_PREFIX = 'matomo-user-login-';

	/**
	 * @api
	 */
	public function get_current_matomo_user_login() {
		if (get_current_user_id()) {
			return self::get_matomo_user_login( get_current_user_id() );
		}
	}

	public static function get_matomo_user_login( $wpUserId ) {
		return get_option( self::USER_MAPPING_PREFIX . $wpUserId );
	}

	public static function map_matomo_user_login( $wpUserId, $matomoUserLogin ) {
		if ( empty( $matomoUserLogin ) ) {
			delete_option( self::USER_MAPPING_PREFIX . $wpUserId );
		} else {
			update_option( self::USER_MAPPING_PREFIX . $wpUserId, $matomoUserLogin );
		}
	}

	public function uninstall() {
		Uninstaller::uninstall_options( self::USER_MAPPING_PREFIX );
	}


}
