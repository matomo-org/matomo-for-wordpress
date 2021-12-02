<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Date;
use WpMatomo\WpStatistics\DataConverters\UserCityConverter;
use WpMatomo\WpStatistics\DataConverters\UserCountryConverter;
use WpMatomo\WpStatistics\DataConverters\UserRegionConverter;
use WpMatomo\WpStatistics\Geoip2;
use Piwik\Plugins\UserCountry\Archiver;

class UserCountryImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'UserCountry';

	const CITY_PATTERN = '/[a-z ]+,([a-z ]+)/i';

	protected $visitors = null;

	private $geoip;

	public function importRecords( Date $date ) {
		$this->geoip = Geoip2::getInstance();
		$this->visitors = $this->getVisitors($date);
		$this->importCountries();
		$this->importRegions();
		$this->importCities();
	}

	private function getRegion($visitor) {
		$matches = [];
		$region = '';
		if (preg_match(self::CITY_PATTERN, $visitor['city'], $matches)) {
			$region = trim($matches[1]);
		}
		return $region;
	}

	private function importRegions() {

		foreach ($this->visitors as $id => $visitor) {
			$this->visitors[$id]['matomo_region'] = $this->geoip->getMatomoRegionCode($visitor['ip']['value'], $this->getRegion($visitor));
		}
		$regions = UserRegionConverter::convert($this->visitors);
		$this->logger->debug('Import {nb_regions} regions...', ['nb_regions' => $regions->getRowsCount()]);
		$this->insertRecord(Archiver::REGION_RECORD_NAME, $regions, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
		Common::destroy($regions);
	}

	private function importCities() {
		// apply the country name to normalize with the matomo data
		foreach ($this->visitors as $id => $visitor) {
			$this->visitors[$id]['matomo_city'] = $this->geoip->getMatomoCityCode($visitor['ip']['value'], $this->getRegion($visitor));
		}
		$cities = UserCityConverter::convert($this->visitors);
		$this->logger->debug('Import {nb_cities} cities...', ['nb_cities' => $cities->getRowsCount()]);
		$this->insertRecord(Archiver::CITY_RECORD_NAME, $cities, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
		Common::destroy($cities);
	}

	/**
	 * @param Date $date
	 */
	private function importCountries() {
		foreach ($this->visitors as $id => $visitor) {
			$this->visitors[$id]['matomo_country'] = $this->geoip->getMatomoCountryCode($visitor['ip']['value']);
		}
		$countries = UserCountryConverter::convert($this->visitors);
		$this->logger->debug('Import {nb_countries} countries...', ['nb_countries' => $countries->getRowsCount()]);
		$this->insertRecord(Archiver::COUNTRY_RECORD_NAME, $countries, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
		$this->insertNumericRecords([Archiver::DISTINCT_COUNTRIES_METRIC => $countries->getRowsCount()]);
		Common::destroy($countries);
	}
}