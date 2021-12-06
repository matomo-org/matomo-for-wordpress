<?php

namespace WpMatomo\WpStatistics\DataConverters;

class PlatformConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'platform' );
	}
}
