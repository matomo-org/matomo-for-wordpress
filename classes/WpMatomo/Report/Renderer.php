<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Report;

use Piwik\API\Request;
use Piwik\SettingsServer;
use WpMatomo\Capabilities;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Renderer {

	public function register_hooks() {
		add_shortcode( 'matomo_report', array( $this, 'show_report' ) );
	}

	public function show_report( $atts ) {
		$a = shortcode_atts( array(
			'unique_id'   => '',
			'report_date' => Dates::YESTERDAY,
			'limit'       => 10,
		), $atts );

		if ( ! current_user_can( Capabilities::KEY_VIEW ) ) {
			// not needed as processRequest checks permission anyway but it's faster this way and double ensures to not
			// letting users view it when they have no access.
			return __('Sorry, you are not allowed to view this report.', 'matomo');
		}

		$metadata    = new Metadata();
		$report_meta = $metadata->find_report_by_unique_id( $a['unique_id'] );

		if ( empty( $report_meta ) ) {
			return sprintf( __( 'Report %s not found', 'matomo' ), esc_html( $a['unique_id'] ) );
		}

		$metric_keys               = array_keys( $report_meta['metrics'] );
		$first_metric_name         = reset( $metric_keys );
		$first_metric_display_name = reset( $report_meta['metrics'] );

		$dates = new Dates();
		list( $period, $date ) = $dates->detect_period_and_date( $a['report_date'] );

		$report_data     = new Data();
		$report          = $report_data->fetch_report( $report_meta, $period, $date, $first_metric_name, $a['limit'] );
		$has_report_data = ! empty( $report['reportData'] ) && $report['reportData']->getRowsCount();

		ob_start();

		if ( ! $has_report_data ) {
			include 'views/table_no_data.php';
		} elseif ( empty( $report_meta['dimension'] ) ) {
			include 'views/table_no_dimension.php';
		} else {
			include 'views/table.php';
		}

		return ob_get_clean();
	}

}
