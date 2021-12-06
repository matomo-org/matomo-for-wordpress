<?php

namespace WpMatomo\WpStatistics\DataConverters;

class SearchKeywordConverter extends SubtableConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'words', 'engine' );
	}
}
