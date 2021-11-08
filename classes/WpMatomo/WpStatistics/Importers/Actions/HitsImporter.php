<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\Hits;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;

class HitsImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'HitsImporter';

	public function import( Date $date ) {
		$hits = Hits::get( [ 'from' => $date->toString(Config::WP_STATISTICS_DATE_FORMAT), 'to' => $date->addDay(1)->toString(Config::WP_STATISTICS_DATE_FORMAT) ] );
		if (array_key_exists('no_data', $hits) && ($hits['no_data'] === 1)) {
			$hits = array();
		}
		return $hits;
	}
}