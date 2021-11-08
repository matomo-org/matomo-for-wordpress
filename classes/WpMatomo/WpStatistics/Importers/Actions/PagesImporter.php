<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\pages;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;

class PagesImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'PagesImporter';

	public function import( Date $date ) {
		$limit = 100;
		$pages = [];
		$page  = 0;
		do {
			$page ++;
			$pages_found = pages::get( [
				'from'     => $date->toString(Config::WP_STATISTICS_DATE_FORMAT),
				'to'       => $date->addDay(1)->toString(Config::WP_STATISTICS_DATE_FORMAT),
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