<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\Hits;
use WpMatomo\WpStatistics\DateTime;

class HitsImporter implements ActionsInterface {

	public function import( DateTime $date_time ) {
		$hits = Hits::get( [ 'from' => $date_time->beginDay()->toWpsMySQL(), 'to' => $date_time->endDay()->toWpsMySQL() ] );
		if (array_key_exists('no_data', $hits) && ($hits['no_data'] === 1)) {
			$hits = array();
		}
		return $hits;
	}
}