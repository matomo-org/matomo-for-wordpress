<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class UserCountryConverter extends VisitorsConverter implements DataConverterInterface {

	public static function convert( $wpStatisticData ) {
		return self::aggregateByKey( $wpStatisticData, 'matomo_country' );
	}
}
