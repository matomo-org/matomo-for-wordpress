<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
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
		if ( get_current_user_id() ) {
			return self::get_matomo_user_login( get_current_user_id() );
		}
	}

	public static function get_matomo_user_login( $wp_user_id ) {
		return get_option( self::USER_MAPPING_PREFIX . $wp_user_id );
	}

	public static function map_matomo_user_login( $wp_user_id, $matomo_user_login ) {
		if ( empty( $matomo_user_login ) ) {
			delete_option( self::USER_MAPPING_PREFIX . $wp_user_id );
		} else {
			update_option( self::USER_MAPPING_PREFIX . $wp_user_id, $matomo_user_login );
		}
	}

	/**
	 * @param string $matomo_user_login
	 * @return int[]
	 */
	public function get_wp_user_ids_for_matomo_login( $matomo_user_login ) {
		global $wpdb;

		if ( empty( $matomo_user_login ) ) {
			return [];
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s",
				$wpdb->esc_like( self::USER_MAPPING_PREFIX ) . '%',
				$matomo_user_login
			)
		);

		$wp_user_ids = [];
		foreach ( $option_names as $option_name ) {
			$wp_user_id = (int) substr( $option_name, strlen( self::USER_MAPPING_PREFIX ) );
			if ( $wp_user_id ) {
				$wp_user_ids[] = $wp_user_id;
			}
		}

		return array_unique( $wp_user_ids );
	}

	/**
	 * @param string $matomo_user_login
	 */
	public function delete_mappings_for_matomo_login( $matomo_user_login ) {
		if ( empty( $matomo_user_login ) ) {
			return;
		}

		foreach ( $this->get_wp_user_ids_for_matomo_login( $matomo_user_login ) as $wp_user_id ) {
			delete_option( self::USER_MAPPING_PREFIX . $wp_user_id );
		}
	}

	public function uninstall() {
		Uninstaller::uninstall_options( self::USER_MAPPING_PREFIX );
	}
}
