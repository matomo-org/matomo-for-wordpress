<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class NumberConverter {

	public static function aggregateByKey( $wpStatisticData, $key ) {
		$data = [];
		if ( count( $wpStatisticData ) ) {
			foreach ( $wpStatisticData as $row ) {
				if (!array_key_exists($row[$key], $data)) {
					$data[$row[$key]] = [ 'label' => $row[$key], 'nb_visits' => 0, 'nb_uniq_visitors' => 0 ];
				}
				$data[$row[$key]]['nb_visits'] += $row['number'];
				$data[$row[$key]]['nb_uniq_visitors'] += $row['number'];
			}
		}

		$datatable = new DataTable();
		foreach ( array_values( $data ) as $row ) {
			$datatable->addRowFromSimpleArray( $row );
		}
		return $datatable;
	}
}