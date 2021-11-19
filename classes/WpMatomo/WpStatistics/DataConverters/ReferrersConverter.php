<?php

namespace WpMatomo\WpStatistics\DataConverters;

class ReferrersConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'domain' );
	}
}