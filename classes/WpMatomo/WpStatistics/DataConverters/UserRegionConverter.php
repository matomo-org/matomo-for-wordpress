<?php

namespace WpMatomo\WpStatistics\DataConverters;

class UserRegionConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'matomo_region' );
	}
}
