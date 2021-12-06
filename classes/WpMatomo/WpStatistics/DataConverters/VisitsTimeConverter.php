<?php
namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class VisitsTimeConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		$datatable = new DataTable();
		$datatable->addRowFromSimpleArray(
			[
				'label'     => $wpStatisticData['date'],
				'nb_visits' => count( $wpStatisticData ),
			]
		);
		return $datatable;
	}
}
