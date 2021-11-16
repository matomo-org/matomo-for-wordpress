<?php

namespace WpMatomo\WpStatistics\DataConverters;

class BrowsersConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$data = [];
		if (array_key_exists('browsers_name', $wpStatisticData) && array_key_exists('browsers_value', $wpStatisticData)) {
			foreach($wpStatisticData['browsers_name'] as $id => $name) {
				$data[] = ['label' => $name, 'nb_visits' => $wpStatisticData['browsers_value'][$id]];
			}
		}
		return $data;
	}
}