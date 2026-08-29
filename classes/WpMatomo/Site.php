<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WP_Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Site {
	const SITE_MAPPING_PREFIX = 'matomo-site-id-';

	/**
	 * Whether a blog is out of service or not. Out of service blogs are not synced.
	 *
	 * A blog that is deleted, archived or flagged as spam serves no traffic and is not tracked,
	 * nor is it guaranteed to be in a working state.
	 *
	 * WP_Site_Query does not filter any of the three by default, so every loop over get_sites()
	 * has to ask for itself.
	 *
	 * @param WP_Site|int|null $site a blog, or the id of one to look up
	 * @return bool
	 */
	public static function is_blog_out_of_service( $site ) {
		if ( ! $site instanceof WP_Site ) {
			if ( ! function_exists( 'get_site' ) ) {
				// we're not in a multisite install, so there is only one blog, and it can't
				// be out of service
				return false;
			}

			$site = get_site( $site );
		}

		if ( ! $site instanceof WP_Site ) {
			// cannot lookup the site, treat it as out of service
			return true;
		}

		return 1 === (int) $site->deleted
			|| 1 === (int) $site->archived
			|| 1 === (int) $site->spam;
	}

	/**
	 * @api
	 */
	public function get_current_matomo_site_id() {
		return self::get_matomo_site_id( get_current_blog_id() );
	}

	public static function get_matomo_site_id( $blog_id ) {
		return (int) get_site_option( self::SITE_MAPPING_PREFIX . $blog_id );
	}

	public static function map_matomo_site_id( $blog_id, $matomo_id_site ) {
		$key = self::SITE_MAPPING_PREFIX . $blog_id;

		if ( null === $matomo_id_site || false === $matomo_id_site ) {
			delete_site_option( $key );
		} else {
			update_site_option( $key, $matomo_id_site );
		}
	}

	public function uninstall() {
		Uninstaller::uninstall_site_meta( self::SITE_MAPPING_PREFIX );
	}
}
