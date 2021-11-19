<?php

namespace WpMatomo\WpStatistics;

class Config {

	const WP_STATISTICS_DATE_FORMAT = 'Y-m-d';

	public static function getImporters() {
		return [
			'WpMatomo\WpStatistics\Importers\Actions\HitsImporter',
			'WpMatomo\WpStatistics\Importers\Actions\PagesImporter',
			'WpMatomo\WpStatistics\Importers\Actions\ReferrersImporter',
			'WpMatomo\WpStatistics\Importers\Actions\UserCountryImporter',
			'WpMatomo\WpStatistics\Importers\Actions\DeviceDetectionImporter',
		];
	}
}