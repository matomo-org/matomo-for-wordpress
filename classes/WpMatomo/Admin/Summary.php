<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use Piwik\API\Request;
use WpMatomo\Bootstrap;
use WpMatomo\Report\Data;
use WpMatomo\Report\Dates;
use WpMatomo\Report\Metadata;
use WpMatomo\Settings;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Summary {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function show() {
		$settings = $this->settings;

		$reports_to_show = $this->get_reports_to_show();
		$filter_limit    = apply_filters( 'matomo_report_summary_filter_limit', 10 );

		$report_dates = new Dates();
		$report_dates = $report_dates->get_supported_dates();

		$report_date = Dates::YESTERDAY;
		if ( isset( $_GET['report_date'] ) && isset( $report_dates[ $_GET['report_date'] ] ) ) {
			$report_date = $_GET['report_date'];
		}

		$is_tracking            = $this->settings->is_tracking_enabled();

		include_once( dirname( __FILE__ ) . '/views/summary.php' );
	}

	private function get_reports_to_show() {
		$reports_to_show = array(
			'VisitsSummary_get',
			'UserCountry_getCountry',
			'DevicesDetection_getType',
			'Resolution_getResolution',
			'DevicesDetection_getOsFamilies',
			'DevicesDetection_getBrowsers',
			'VisitTime_getVisitInformationPerServerTime',
			'Actions_get',
			'Actions_getPageTitles',
			'Actions_getEntryPageTitles',
			'Actions_getExitPageTitles',
			'Actions_getDownloads',
			'Actions_getOutlinks',
			'Referrers_getAll',
			'Referrers_getSocials',
			'Referrers_getCampaigns',
			'Goals_get',
			'Goals_get_idGoal--0',
			'Goals_get_idGoal--ecommerceOrder',
			'Goals_getItemsName',
		);
		$reports_to_show = apply_filters( 'matomo_report_summary_report_ids', $reports_to_show );

		$report_metadata = array();
		$metadata        = new Metadata();
		foreach ( $reports_to_show as $report_unique_id ) {
			$report = $metadata->find_report_by_unique_id( $report_unique_id );
			if ( $report ) {
				$report_metadata[] = $report;
			}
		}

		return $report_metadata;
	}

}
