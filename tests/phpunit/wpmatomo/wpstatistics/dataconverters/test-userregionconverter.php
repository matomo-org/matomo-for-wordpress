<?php

use WpMatomo\WpStatistics\DataConverters\UserRegionConverter;

class UserRegionConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$regions = UserRegionConverter::convert( [] );
		$this->assertEquals( $regions->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data    = [
			[
				'matomo_region' => 'NY|us',
			],
			[
				'matomo_region' => 'CAN|nz',
			],
			[
				'matomo_region' => 'NY|us',
			],
			[
				'matomo_region' => 'CA|us',
			],
		];
		$regions = UserRegionConverter::convert( $data );
		$this->assertEquals( $regions->getRowsCount(), 3 );
		$this->assertEquals( $regions->getFirstRow()->getColumn( 'nb_visits' ), 2 );
	}
}
