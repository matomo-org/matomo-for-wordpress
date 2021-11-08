<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\top_visitors;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;

class VisitorsImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'VisitorsImporter';

	public function import( Date $date ) {
		$limit  = 100;
		$visits  = [];
		$page   = 0;
		do {
			$page ++;
			$visits_found = top_visitors::get( [
				'day'      => $date->toString(Config::WP_STATISTICS_DATE_FORMAT),
				'per_page' => $limit,
				'paged'    => $page
			] );
			$no_data      = ( ( array_key_exists( 'no_data', $visits_found ) ) && ( $visits_found['no_data'] === 1 ) );
			if ( ! $no_data ) {
				$visits = array_merge( $visits, $visits_found );
			}
		} while ( $no_data !== true );

		return $visits;
	}
}