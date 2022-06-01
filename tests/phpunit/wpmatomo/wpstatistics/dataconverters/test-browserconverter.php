<?php

use WpMatomo\WpStatistics\DataConverters\BrowsersConverter;

class BrowserConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$browsers = BrowsersConverter::convert( [] );
		$this->assertEquals( $browsers->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data     = [
			[
				'browser' => [ 'name' => 'FF' ],
			],
			[
				'browser' => [ 'name' => 'FM' ],
			],
			[
				'browser' => [ 'name' => 'FF' ],
			],
		];
		$browsers = BrowsersConverter::convert( $data );
		$this->assertEquals( $browsers->getRowsCount(), 2 );
		$this->assertEquals( $browsers->getFirstRow()->getColumn( 'nb_visits' ), 2 );
	}
}
