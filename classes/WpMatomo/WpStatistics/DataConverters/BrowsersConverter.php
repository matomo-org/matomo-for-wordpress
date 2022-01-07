<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class BrowsersConverter implements DataConverterInterface {

	public static function convert( array $wp_statistics_data ) {
		$browsers = new DataTable();
		$data     = [];
		foreach ( $wp_statistics_data as $visit ) {
			if ( ! array_key_exists( $visit['browser']['name'], $data ) ) {
				$data[ $visit['browser']['name'] ] = 0;
			}
			$data[ $visit['browser']['name'] ]++;
		}
		foreach ( $data as $browser => $hits ) {
			$browsers->addRowFromSimpleArray(
				[
					'label'     => $browser,
					'nb_visits' => $hits,
				]
			);
		}
		return $browsers;
	}
}
