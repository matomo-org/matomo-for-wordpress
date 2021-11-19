<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Plugins\DevicesDetection\Archiver;
use WP_STATISTICS\MetaBox\browsers;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\BrowsersConverter;
use WpMatomo\WpStatistics\DataConverters\PlatformConverter;

class DeviceDetectionImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'DevicesDetection';

	public function importRecords( Date $date ) {
		$this->importBrowsers($date);
		$this->importPlateform($date);
	}

	/**
	 * @param Date $date
	 *
	 * @throws \Exception
	 */
	private function importBrowsers(Date $date) {
		$limit   = 1000;
		$devices = browsers::get( [
			'from'   => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'to'     => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'number' => $limit
		] );
		if (array_key_exists('no_data', $devices) && $devices['no_data']) {
			$devices = array();
		}
		$devices = BrowsersConverter::convert($devices);
		$this->logger->debug('Import {nb_browsers} browsers...', ['nb_browsers' => $devices->getRowsCount()]);
		$this->insertRecord(Archiver::BROWSER_RECORD_NAME, $devices);
		Common::destroy($devices);
	}

	/**
	 * @param Date $date
	 */
	private function importPlateform(Date $date) {
		$plateforms = $this->getVisitors($date);
		$plateforms = PlatformConverter::convert( $plateforms );
		$this->logger->debug('Import {nb_platform} platforms...', ['nb_platform' => $plateforms->getRowsCount()]);
		$this->insertRecord(Archiver::DEVICE_TYPE_RECORD_NAME, $plateforms);
		Common::destroy($plateforms);
	}
}