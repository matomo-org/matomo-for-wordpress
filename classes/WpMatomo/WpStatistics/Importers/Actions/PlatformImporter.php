<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\browsers;
use Piwik\Date;
use WP_STATISTICS\MetaBox\top_visitors;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\BrowsersConverter;
use WpMatomo\WpStatistics\DataConverters\PlatformConverter;

class PlatformImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'DevicesDetection';

	public function importRecords( Date $date ) {
		$limit  = 100;
		$plateforms = [];
		$page   = 0;
		do {
			$page ++;
			$visits_found = top_visitors::get( [
				'day'      => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
				'per_page' => $limit,
				'paged'    => $page
			] );
			$no_data      = ( ( array_key_exists( 'no_data', $visits_found ) ) && ( $visits_found['no_data'] === 1 ) );
			if ( ! $no_data ) {
				$plateforms = array_merge( $plateforms, $visits_found );
			}
		} while ( $no_data !== true );
		$plateforms = PlatformConverter::convert($plateforms);
		return $plateforms;
	}
}