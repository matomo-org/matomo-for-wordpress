<?php

use WpMatomo\WpStatistics\DataConverters\SearchEngineConverter;

class SearchEngineConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$search_engines = SearchEngineConverter::convert( [] );
		$this->assertEquals( $search_engines->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data           = [
			[
				'engine' => 'Google',
				'nb'     => 4,
			],
			[
				'engine' => 'Bing',
				'nb'     => 1,
			],
		];
		$search_engines = SearchEngineConverter::convert( $data );
		$this->assertEquals( 2, $search_engines->getRowsCount() );
		$this->assertEquals( 4, $search_engines->getFirstRow()->getColumn( 'nb_visits' ) );
	}
}
