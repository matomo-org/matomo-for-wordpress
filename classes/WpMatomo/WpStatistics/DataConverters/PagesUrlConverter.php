<?php

namespace WpMatomo\WpStatistics\DataConverters;

class PagesUrlConverter extends NumberConverter implements DataConverterInterface {

	public static function convert($wpStatisticsData) {
		return self::aggregateByKey($wpStatisticsData, 'str_url');
	}
}