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
	public function importRecords( Date $date ) {
		$this->importCountries($date);
		$this->importRegions($date);
		$this->importCities($date);
	}

	private function appendCountry($field, & $visitors) {
		$isoCountries = Country::getList();
		foreach ($visitors as $id => $visitor) {
			if (preg_match(self::CITY_PATTERN, $visitor['city'])) {
				$visitors[$id][$field] .= ', '.$isoCountries[$visitor['country']['location']];
			}
		}
	}
	/**
	 * @param Date $date
	 */
	private function importRegions(Date $date) {
		$visitors = $this->getVisitors($date);
		// parse the region from the city field
		foreach ($visitors as $id => $visitor) {
			$matches = [];
			$region = 'Unknown';
			if (preg_match(self::CITY_PATTERN, $visitor['city'], $matches)) {
				$region = trim($matches[1]);
			}
			$visitors[$id]['region'] = $region;
		}
		// apply the country name to normalize with the matomo data
		$this->appendCountry('region', $visitors);
		$regions = UserRegionConverter::convert($visitors);
		$this->logger->debug('Import {nb_regions} regions...', ['nb_regions' => $regions->getRowsCount()]);
		$this->insertRecord(Archiver::REGION_RECORD_NAME, $regions, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
	}
	/**
	 * @param Date $date
	 */
	private function importCities(Date $date) {
		$visitors = $this->getVisitors($date);
		// apply the country name to normalize with the matomo data
		$this->appendCountry('city', $visitors);
		$cities = UserCityConverter::convert($visitors);
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