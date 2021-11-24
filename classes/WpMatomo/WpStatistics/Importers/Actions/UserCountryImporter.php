<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use WP_STATISTICS\Country;
use WP_STATISTICS\MetaBox\countries;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\UserCityConverter;
use WpMatomo\WpStatistics\DataConverters\UserCountryConverter;
use WpMatomo\WpStatistics\DataConverters\UserRegionConverter;
use WpMatomo\WpStatistics\Importers\Actions\RecordImporter;
use Piwik\Plugins\UserCountry\Archiver;

class UserCountryImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'UserCountry';

	const CITY_PATTERN = '/[a-z ]+,([a-z ]+)/i';

	protected $visitors = null;

	public function importRecords( Date $date ) {
		$this->visitors = $this->getVisitors($date);
		$this->importCountries($date);
		$this->importRegions();
		$this->importCities();
	}

	private function appendCountry($field, & $visitors) {
		foreach ($visitors as $id => $visitor) {
			$visitors[$id][$field] .= '|'.strtolower($visitor['country']['location']);
		}
	}

	private function importRegions() {

		$regionsIsoCodes = include WP_CONTENT_DIR.'/plugins/matomo/app/plugins/GeoIp2/data/isoRegionNames.php';
		// parse the region from the city field
		foreach ($this->visitors as $id => $visitor) {
			$matches = [];
			$regionCode = '';
			if (preg_match(self::CITY_PATTERN, $visitor['city'], $matches)) {
				$region = trim($matches[1]);
				$regionCodes = array_flip($regionsIsoCodes[$visitor['country']['location']]);
				if (array_key_exists($region, $regionCodes)) {
					$regionCode = $regionCodes[$region];
				}
			}
			$this->visitors[$id]['region'] = $regionCode;
			$this->logger->debug($regionCode. ' '.$visitor['country']['location']);
		}
		foreach($this->visitors as $visitor) {
			$this->logger->debug($visitor['region']);
		}
		// apply the country name to normalize with the matomo data
		$this->appendCountry('region', $this->visitors);
		foreach($this->visitors as $visitor) {
			$this->logger->debug($visitor['region']);
		}
		$regions = UserRegionConverter::convert($this->visitors);
		$this->logger->debug('Import {nb_regions} regions...', ['nb_regions' => $regions->getRowsCount()]);
		$this->insertRecord(Archiver::REGION_RECORD_NAME, $regions, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
	}

	private function importCities() {
		// apply the country name to normalize with the matomo data
		foreach($this->visitors as $id => $visitor) {
			$citiesFields = explode(',', $visitor['city']);
			if ($citiesFields[0] === '(Unknown)') {
				$citiesFields[0] = '';
			}
			$this->visitors[$id]['city'] = $citiesFields[0].'|'.$visitor['region'];
		}
		$cities = UserCityConverter::convert($this->visitors);
		$this->logger->debug('Import {nb_cities} cities...', ['nb_cities' => $cities->getRowsCount()]);
		$this->insertRecord(Archiver::CITY_RECORD_NAME, $cities, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
	}

	/**
	 * @param Date $date
	 */
	private function importCountries(Date $date) {
		$limit     = 10000;
		$countries = countries::get( [
			'from'  => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'to'    => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'limit' => $limit
		] );
		$noData   = ( ( array_key_exists( 'no_data', $countries ) ) && ( $countries['no_data'] === 1 ) );
		if ( $noData ) {
			$countries = [];
		}
		$countries = UserCountryConverter::convert($countries);
		$this->logger->debug('Import {nb_countries} countries...', ['nb_countries' => $countries->getRowsCount()]);
		$this->insertRecord(Archiver::COUNTRY_RECORD_NAME, $countries, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
		$this->insertNumericRecords([Archiver::DISTINCT_COUNTRIES_METRIC => $countries->getRowsCount()]);
		Common::destroy($countries);
	}
}