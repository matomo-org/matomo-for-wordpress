<?php

namespace WpMatomo\WpStatistics\DataConverters;

class ReferrersConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$referrers = array();
		if (count($wpStatisticData)) {
			foreach ($wpStatisticData as $referrer) {
				if (!array_key_exists($referrer['domain'], $referrers)) {
					$referrers[$referrer['domain']] = ['label' => $referrer['domain'], 'nb_visits' =>0 ];
				}
				$referrers[$referrer['domain']]['nb_visits']++;
			}
		}
		return array_values($referrers);
	}
}