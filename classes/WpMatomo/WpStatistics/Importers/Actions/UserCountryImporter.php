<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\countries;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\UserCountryConverter;

class UserCountryImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'UserCountry';

	public function importRecords( Date $date ) {
		$limit     = 10000;
		$countries = countries::get( [
			'from'  => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'to'    => $date->addDay( 1 )->toString( Config::WP_STATISTICS_DATE_FORMAT ),
			'limit' => $limit
		] );
		$no_data   = ( ( array_key_exists( 'no_data', $countries ) ) && ( $countries['no_data'] === 1 ) );
		if ( $no_data ) {
			$countries = [];
		}
		$countries = UserCountryConverter::convert($countries);
		return $countries;
	}
}