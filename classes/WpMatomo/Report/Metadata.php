<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Report;

use Piwik\Access;
use Piwik\API\Request;
use WpMatomo\Bootstrap;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Metadata {
	public static $CACHE_ALL_REPORTS = array();

	public function get_all_reports() {
		if ( ! empty( self::$CACHE_ALL_REPORTS ) ) {
			return self::$CACHE_ALL_REPORTS;
		}

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( $idsite ) {
			Bootstrap::do_bootstrap();

			$all_reports = Request::processRequest( 'API.getReportMetadata', array( 'idSite' => $idsite ) );
			foreach ( $all_reports as $single_report ) {
				if ( isset( $single_report['uniqueId'] ) ) {
					self::$CACHE_ALL_REPORTS[ $single_report['uniqueId'] ] = $single_report;
				}
			}
		}

		return self::$CACHE_ALL_REPORTS;
	}

	/**
	 * @internal
	 * tests only
	 */
	public static function clear_cache() {
		self::$CACHE_ALL_REPORTS = array();
	}

	public function find_report_by_unique_id( $unique_id ) {
		$all_reports = self::get_all_reports();

		if ( isset( $all_reports[ $unique_id ] ) ) {
			return $all_reports[ $unique_id ];
		}
	}

}
