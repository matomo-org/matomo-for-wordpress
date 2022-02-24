<?php

use WpMatomo\WpStatistics\DataConverters\PlatformConverter;

class PlatformConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$platforms = PlatformConverter::convert( [] );
		$this->assertEquals( $platforms->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data      = [
			[
				'platform' => 'MAC',
			],
			[
				'platform' => 'BSD',
			],
			[
				'platform' => 'MAC',
			],
		];
		$platforms = PlatformConverter::convert( $data );
		$this->assertEquals( $platforms->getRowsCount(), 2 );
		$this->assertEquals( $platforms->getFirstRow()->getColumn( 'nb_visits' ), 2 );
	}
}
