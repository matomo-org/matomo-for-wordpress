<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class VisitorsConverter {

	public static function aggregateByKey( array $wpStatisticData, $key ) {
		$data = [];
		if ( count( $wpStatisticData ) ) {
			foreach ( $wpStatisticData as $row ) {
				if ( ! array_key_exists( $row[ $key ], $data ) ) {
					$data[ $row[ $key ] ] = [
						'label'            => $row[ $key ],
						'nb_visits'        => 0,
						'nb_uniq_visitors' => 0,
					];
				}
				$data[ $row[ $key ] ]['nb_visits'] ++;
				$data[ $row[ $key ] ]['nb_uniq_visitors'] ++;
			}
		}
		$datatable = new DataTable();
		foreach ( array_values( $data ) as $row ) {
			$datatable->addRowFromSimpleArray( $row );
		}

		return $datatable;
	}
}
