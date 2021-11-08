<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\pages;
use WpMatomo\WpStatistics\DateTime;

class PagesImporter implements ActionsInterface {

	public function import( DateTime $date_time ) {
		$limit = 100;
		$pages = [];
		$page  = 0;
		do {
			$page ++;
			$pages_found = pages::get( [
				'from'     => $date_time->beginDay()->toWpsMySQL(),
				'to'       => $date_time->endDay()->toWpsMySQL(),
				'per_page' => $limit,
				'paged'    => $page
			] );
			$no_data     = ( ( array_key_exists( 'no_data', $pages_found ) ) && ( $pages_found['no_data'] === 1 ) );
			if ( ! $no_data ) {
				$pages = array_merge( $pages, $pages_found );
			}
		} while ( $no_data !== true );

		return $pages;
	}
}