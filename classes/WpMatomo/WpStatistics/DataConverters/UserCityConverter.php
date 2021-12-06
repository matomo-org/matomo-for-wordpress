<?php

namespace WpMatomo\WpStatistics\DataConverters;

class UserCityConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'matomo_city' );
	}
}
