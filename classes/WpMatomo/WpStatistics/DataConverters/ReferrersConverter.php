<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class ReferrersConverter implements DataConverterInterface {

	public static function convert($wpStatisticData) {
		$referrers = new DataTable();
		if (count($wpStatisticData)) {
			foreach ($wpStatisticData as $referrer) {
				if (!array_key_exists($referrer['domain'], $referrers)) {
					$referrers->addRowFromArray(['label' => $referrer['domain'], 'nb_visits' =>0 ]);
				}
				$referrers->getColumn($referrer['domain']);
				$referrers[$referrer['domain']]['nb_visits']++;
			}
		}
		return array_values($referrers);
	}
}