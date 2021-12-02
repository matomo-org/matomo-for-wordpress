<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class ReferrersConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		$data = [];
		if ( count( $wpStatisticData ) ) {
			foreach ( $wpStatisticData as $row ) {
				$data[] = [ 'label' => $row['domain'], 'nb_visits' => $row['number'], 'nb_uniq_visitors' => $row['number'] ];
			}
		}

		$datatable = new DataTable();
		foreach ( array_values( $data ) as $row ) {
			$datatable->addRowFromSimpleArray( $row );
		}
		return $datatable;
	}
}