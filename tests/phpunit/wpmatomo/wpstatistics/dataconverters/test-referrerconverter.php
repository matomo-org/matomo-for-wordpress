<?php

use WpMatomo\WpStatistics\DataConverters\ReferrersConverter;

class ReferrersConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$referrers = ReferrersConverter::convert( [] );
		$this->assertEquals( $referrers->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data      = [
			[
				'domain' => 'google.com',
				'number' => 1,
			],
			[
				'domain' => 'dummyhost.com',
				'number' => 3,
			],
			[
				'domain' => 'google.com',
				'number' => 2,
			],
		];
		$referrers = ReferrersConverter::convert( $data );
		$this->assertEquals( $referrers->getRowsCount(), 2 );
		$this->assertEquals( $referrers->getFirstRow()->getColumn( 'nb_visits' ), 3 );
	}
}
