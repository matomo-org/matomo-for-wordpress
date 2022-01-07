<?php
namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class VisitsTimeConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( array $wp_statistics_data ) {
		$datatable = new DataTable();
		$datatable->addRowFromSimpleArray(
			[
				'label'     => $wp_statistics_data[0]['date'],
				'nb_visits' => count( $wp_statistics_data ),
			]
		);
		return $datatable;
	}
}
