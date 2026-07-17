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
	 * @var Settings
	 */
	private $settings;

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

		// on network-enabled multisite WP, there is one Matomo per network, so
		// iterating over all blogs is not needed.
		if ( ! $this->get_settings()->is_network_enabled() ) {
			$this->delete_mappings_for_matomo_login_on_current_blog( $matomo_user_login );
			return;
		}

		global $wpdb;

		// the same Matomo login can be mapped from several blogs, so the mapping needs to be deleted
		// for every blog it has been mapped within
		$cache_key   = 'blog_ids_for_mapping_cleanup';
		$cache_group = 'matomo';

		$blogs = wp_cache_get( $cache_key, $cache_group );
		if ( false === $blogs ) {
			// short lived cache so a sync deleting many users does not re-run this once per user;
			// a slightly stale blog list is harmless here (deleted blogs are skipped below).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$blogs = $wpdb->get_results( 'SELECT blog_id, deleted FROM ' . $wpdb->blogs . ' ORDER BY blog_id', ARRAY_A );

			if ( is_array( $blogs ) ) {
				wp_cache_set( $cache_key, $blogs, $cache_group, 5 * MINUTE_IN_SECONDS );
			}
		}

		if ( ! is_array( $blogs ) ) {
			return;
		}

		foreach ( $blogs as $blog ) {
			if ( 1 === (int) $blog['deleted'] ) {
				continue;
			}

			switch_to_blog( $blog['blog_id'] );
			try {
				$this->delete_mappings_for_matomo_login_on_current_blog( $matomo_user_login );
			} finally {
				restore_current_blog();
			}
		}
	}

	/**
	 * @param string $matomo_user_login
	 */
	private function delete_mappings_for_matomo_login_on_current_blog( $matomo_user_login ) {
		foreach ( $this->get_wp_user_ids_for_matomo_login( $matomo_user_login ) as $wp_user_id ) {
			delete_option( self::USER_MAPPING_PREFIX . $wp_user_id );
		}
	}

	public function uninstall() {
		Uninstaller::uninstall_options( self::USER_MAPPING_PREFIX );
	}

	private function get_settings() {
		if ( ! empty( $this->settings ) ) {
			return $this->settings;
		}

		$this->settings = \WpMatomo::$settings ? \WpMatomo::$settings : new Settings();
		return $this->settings;
	}
}
