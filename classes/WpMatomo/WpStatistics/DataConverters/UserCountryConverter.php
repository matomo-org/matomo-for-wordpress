<?php

namespace WpMatomo\WpStatistics\DataConverters;

class UserCountryConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$countries = array();
		if (count($wpStatisticData)) {
			foreach ($wpStatisticData as $country) {
				$name = $country['name'];
				if ($name === 'Unknown') {
					$name = "United states";
				}
				$countries[] = ['label' => $name, 'nb_visits' => $country['number']];
			}
		}
		return $countries;
	}
}