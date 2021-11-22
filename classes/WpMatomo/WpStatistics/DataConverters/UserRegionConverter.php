<?php

namespace WpMatomo\WpStatistics\DataConverters;

class UserRegionConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'region' );
	}
}