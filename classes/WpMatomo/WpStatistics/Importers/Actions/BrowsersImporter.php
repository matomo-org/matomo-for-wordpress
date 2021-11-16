<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\browsers;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\BrowsersConverter;

class BrowsersImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'DevicesDetection';

	public function importRecords( Date $date ) {
		$limit   = 1000;
		$devices = browsers::get( [
			'from'   => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'to'     => $date->addDay( 1 )->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'number' => $limit
		] );
		if (array_key_exists('no_data', $devices) && $devices['no_data']) {
			$devices = array();
		}
		$devices = BrowsersConverter::convert($devices);
		return $devices;
	}
}