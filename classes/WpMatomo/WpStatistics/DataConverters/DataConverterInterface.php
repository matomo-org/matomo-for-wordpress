<?php
namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

interface DataConverterInterface {
	/**
	 * @param [] $wpStatisticData
	 *
	 * @return DataTable
	 */
	public static function convert( array $wpStatisticData);
}
