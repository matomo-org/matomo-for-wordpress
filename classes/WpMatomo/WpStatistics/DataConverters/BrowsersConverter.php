<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class BrowsersConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		$browsers = new DataTable();
		$data     = [];
		foreach ( $wpStatisticData as $visit ) {
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
