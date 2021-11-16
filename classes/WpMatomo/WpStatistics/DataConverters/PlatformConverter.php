<?php

namespace WpMatomo\WpStatistics\DataConverters;

class PlatformConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$plateforms = array();
		if (count($wpStatisticData)) {
			foreach ($wpStatisticData as $visit) {
				if (!array_key_exists($visit['platform'], $plateforms)) {
					$plateforms[$visit['platform']] = ['label' => $visit['platform'], 'nb_visits' =>0 ];
				}
				$plateforms[$visit['platform']]['nb_visits']++;
			}
		}
		return array_values($plateforms);
	}
}