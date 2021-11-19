<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class BrowsersConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$browsers = new DataTable();
		if (array_key_exists('browsers_name', $wpStatisticData) && array_key_exists('browsers_value', $wpStatisticData)) {
			foreach($wpStatisticData['browsers_name'] as $id => $name) {
				$browsers->addRowFromSimpleArray(['label' => $name, 'nb_visits' => $wpStatisticData['browsers_value'][$id]]);
			}
		}
		return $browsers;
	}
}