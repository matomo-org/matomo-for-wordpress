<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Db;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Settings {

	/**
	 * This feature can be used to read data from matomo tables without needing to bootstrap matomo
	 *
	 * @param string $table_name_to_prefix
	 *
	 * @return string
	 * @api
	 */
	public function prefix_table_name( $table_name_to_prefix ) {
		global $wpdb;

		return $wpdb->prefix . MATOMO_DATABASE_PREFIX . $table_name_to_prefix;
	}

}
