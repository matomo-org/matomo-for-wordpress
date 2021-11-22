<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class UserCountryConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$countries = new DataTable();
		if (count($wpStatisticData)) {
			$nbUnknown = 0;
			foreach ($wpStatisticData as $country) {
				if ($country['location'] === '000') {
					$nbUnknown = $country['number'];
					break;
				}
			}
			$foundUs = false;
			if ($nbUnknown > 0) {
				foreach ($wpStatisticData as $id => $country) {
					if ( $country['location'] === 'US' ) {
						$foundUs = true;
						$wpStatisticData[$id]['number'] += $nbUnknown;
						break;
					}
				}
			}
			foreach ($wpStatisticData as $country) {
				$name = strtolower($country['location']);
				$add = true;
				if ($name === '000') {
					$name = "us";
					$add = !$foundUs;
				}
				if ($add) {
					$countries->addRowFromSimpleArray(['label' => $name, 'nb_visits' => $country['number']]);
				}
			}
		}
		return $countries;
	}
}