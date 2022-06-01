<?php

use WpMatomo\WpStatistics\DataConverters\UserCityConverter;

class UserCityConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$cities = UserCityConverter::convert( [] );
		$this->assertEquals( $cities->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data   = [
			[
				'matomo_city' => 'New York|NY|us|40.7128|-74.0060',
			],
			[
				'matomo_city' => 'Christchurch|CAN|nz|-43.5320|172.6306',
			],
			[
				'matomo_city' => 'New York|NY|us|40.7128|-74.0060',
			],
			[
				'matomo_city' => 'San Francisco|CA|us|37.7749|-122.4194',
			],
		];
		$cities = UserCityConverter::convert( $data );
		$this->assertEquals( $cities->getRowsCount(), 3 );
		$this->assertEquals( $cities->getFirstRow()->getColumn( 'nb_visits' ), 2 );
	}
}
