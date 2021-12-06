<?php

namespace WpMatomo\WpStatistics\DataConverters;

class SearchEngineConverter extends SubtableConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'engine', 'words' );
	}
}
