<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Report;

use Piwik\API\Request;
use WpMatomo\Bootstrap;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Metadata {
	public static $CACHE_ALL_REPORTS      = array();
	public static $CACHE_ALL_REPORT_PAGES = array();

	public function get_all_reports() {
		if ( ! empty( self::$CACHE_ALL_REPORTS ) ) {
			return self::$CACHE_ALL_REPORTS;
		}

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( $idsite ) {
			Bootstrap::do_bootstrap();

			$all_reports = Request::processRequest(
				'API.getReportMetadata',
				array(
					'idSite'       => $idsite,
					'filter_limit' => - 1,
				)
			);
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
		self::$CACHE_ALL_REPORTS      = array();
		self::$CACHE_ALL_REPORT_PAGES = array();
	}

	public function find_report_by_unique_id( $unique_id ) {
		if ($unique_id === Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME) {
			return array('uniqueId' => Renderer::CUSTOM_UNIQUE_ID_VISITS_OVER_TIME, 'name' => 'Visits over time');
		}
		$all_reports = self::get_all_reports();

		if ( isset( $all_reports[ $unique_id ] ) ) {
			return $all_reports[ $unique_id ];
		}
	}

	public function get_all_report_pages() {
		if ( ! empty( self::$CACHE_ALL_REPORT_PAGES ) ) {
			return self::$CACHE_ALL_REPORT_PAGES;
		}

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( $idsite ) {
			Bootstrap::do_bootstrap();

			self::$CACHE_ALL_REPORT_PAGES = Request::processRequest(
				'API.getReportPagesMetadata',
				array(
					'idSite'       => $idsite,
					'filter_limit' => - 1,
				)
			);
		}

		return self::$CACHE_ALL_REPORT_PAGES;
	}

	public function find_report_page_params_by_report_metadata( $report_metadata ) {
		if ( empty( $report_metadata['module'] )
			 || empty( $report_metadata['action'] ) ) {
			return array();
		}

		$report_pages = self::get_all_report_pages();

		foreach ( $report_pages as $report_page ) {
			if ( ! empty( $report_page['widgets'] ) ) {
				foreach ( $report_page['widgets'] as $widget ) {
					if ( ! empty( $widget['module'] ) && $widget['module'] === $report_metadata['module']
						 && ! empty( $widget['action'] ) && $widget['action'] === $report_metadata['action'] ) {
						return array(
							'category'    => $report_page['category']['id'],
							'subcategory' => $report_page['subcategory']['id'],
						);
					}
				}
			}
		}

		// we can't resolve all automatically since reportId != widgetId and the used action may differe etc...
		// we're hard coding some manually

		if ( 'Actions_get' === $report_metadata['uniqueId'] ) {
			return array(
				'category'    => 'General_Visitors',
				'subcategory' => 'General_Overview',
			);
		} elseif ( 'Goals_get' === $report_metadata['uniqueId'] ) {
			return array(
				'category'    => 'Goals_Goals',
				'subcategory' => 'General_Overview',
			);
		} elseif ( 'Goals_get_idGoal--ecommerceOrder' === $report_metadata['uniqueId'] ) {
			return array(
				'category'    => 'Goals_Ecommerce',
				'subcategory' => 'General_Overview',
			);
		} elseif ( 'Goals_getItemsName' === $report_metadata['uniqueId'] ) {
			return array(
				'category'    => 'Goals_Ecommerce',
				'subcategory' => 'Goals_Products',
			);
		}

		return array();
	}

}
