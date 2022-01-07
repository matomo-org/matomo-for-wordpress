<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

/**
 * aggregate data on the number fields
 *
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class NumberConverter {
	/**
	 * @param []     $wp_statistics_data
	 * @param string $key the key to aggregate data
	 *
	 * @return DataTable
	 */
	public static function aggregate_by_key( $wp_statistics_data, $key ) {
		$data = [];
		if ( count( $wp_statistics_data ) ) {
			foreach ( $wp_statistics_data as $row ) {
				if ( ! array_key_exists( $row[ $key ], $data ) ) {
					$data[ $row[ $key ] ] = [
						'label'            => $row[ $key ],
						'nb_visits'        => 0,
						'nb_uniq_visitors' => 0,
					];
				}
				$data[ $row[ $key ] ]['nb_visits']        += intval( $row['number'] );
				$data[ $row[ $key ] ]['nb_uniq_visitors'] += intval( $row['number'] );
			}
		}

		$datatable = new DataTable();
		foreach ( array_values( $data ) as $row ) {
			$datatable->addRowFromSimpleArray( $row );
		}
		return $datatable;
	}
}
