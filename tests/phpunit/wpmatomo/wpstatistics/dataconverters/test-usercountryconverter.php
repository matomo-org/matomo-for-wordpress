<?php

use WpMatomo\WpStatistics\DataConverters\UserCountryConverter;

class UserCountryConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$countries = UserCountryConverter::convert( [] );
		$this->assertEquals( $countries->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data      = [
			[
				'matomo_country' => 'us',
			],
			[
				'matomo_country' => 'nz',
			],
			[
				'matomo_country' => 'us',
			],
			[
				'matomo_country' => 'us',
			],
		];
		$countries = UserCountryConverter::convert( $data );
		$this->assertEquals( $countries->getRowsCount(), 2 );
		$this->assertEquals( $countries->getFirstRow()->getColumn( 'nb_visits' ), 3 );
	}
}
