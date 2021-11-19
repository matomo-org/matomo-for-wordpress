<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use WP_STATISTICS\MetaBox\countries;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\UserCityConverter;
use WpMatomo\WpStatistics\DataConverters\UserCountryConverter;
use WpMatomo\WpStatistics\Importers\Actions\RecordImporter;
use Piwik\Plugins\UserCountry\Archiver;

class UserCountryImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'UserCountry';

	public function importRecords( Date $date ) {
		$this->importCountries($date);
		$this->importCities($date);
	}

	/**
	 * @param Date $date
	 */
	private function importCities(Date $date) {
		$visitors = $this->getVisitors($date);
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

		Common::destroy($countries);
	}
}