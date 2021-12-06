<?php

namespace WpMatomo\WpStatistics\DataConverters;

class ReferrersConverter extends NumberConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'domain' );
	}
}
