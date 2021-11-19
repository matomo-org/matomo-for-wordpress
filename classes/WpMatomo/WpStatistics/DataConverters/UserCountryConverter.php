<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class UserCountryConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$countries = new DataTable();
		if (count($wpStatisticData)) {
			foreach ($wpStatisticData as $country) {
				$name = $country['name'];
				if ($name === 'Unknown') {
					$name = "United states";
				}
				$countries->addRowFromSimpleArray(['label' => $name, 'nb_visits' => $country['number']]);
			}
		}
		return $countries;
	}
}