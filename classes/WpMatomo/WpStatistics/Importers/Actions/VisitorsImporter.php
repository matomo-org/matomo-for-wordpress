<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\top_visitors;
use WpMatomo\WpStatistics\DateTime;

class VisitorsImporter implements ActionsInterface {

	public function import( DateTime $date_time ) {
		$limit  = 100;
		$visits  = [];
		$page   = 0;
		do {
			$page ++;
			$visits_found = top_visitors::get( [
				'day'      => $date_time->toWpsMySQL(),
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