<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

/**
 * aggregate data on the number fields
 */
class NumberConverter {
	/**
	 * @param []     $wpStatisticData
	 * @param string $key the key to aggregate data
	 *
	 * @return DataTable
	 */
	public static function aggregateByKey( $wpStatisticData, $key ) {
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
				$data[ $row[ $key ] ]['nb_visits']        += intval($row['number']);
				$data[ $row[ $key ] ]['nb_uniq_visitors'] += intval($row['number']);
			}
		}

		$datatable = new DataTable();
		foreach ( array_values( $data ) as $row ) {
			$datatable->addRowFromSimpleArray( $row );
		}
		return $datatable;
	}
}