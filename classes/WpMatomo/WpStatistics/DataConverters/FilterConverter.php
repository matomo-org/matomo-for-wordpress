<?php

namespace WpMatomo\WpStatistics\DataConverters;

/**
 * aggregate data on the number fields
 *
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class FilterConverter extends NumberConverter {

	public static function filter( $wp_statistics_data, $pattern, $field ) {
		$data = [];
		foreach ( $wp_statistics_data as $wps_data ) {
			if ( strpos( $wps_data[ $field ], $pattern ) !== false ) {
				$data[] = $wps_data;
			}
		}
		return $data;
	}
}
