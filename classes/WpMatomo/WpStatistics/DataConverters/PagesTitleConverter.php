<?php

namespace WpMatomo\WpStatistics\DataConverters;

class PagesTitleConverter extends NumberConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticsData ) {
		return self::aggregateByKey( $wpStatisticsData, 'title' );
	}
}
