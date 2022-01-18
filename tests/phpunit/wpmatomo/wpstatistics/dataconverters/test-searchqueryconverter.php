<?php

use WpMatomo\WpStatistics\DataConverters\SearchQueryConverter;

class SearchQueryConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$search_queries = SearchQueryConverter::convert( [] );
		$this->assertEquals( $search_queries->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data = [
			[
				'str_url' => 'https://test.com',
				'number'  => 1,
			],
			[
				'str_url' => 'https://test.com?s=import',
				'number'  => 2,
			],

			[
				'str_url' => 'https://test.com?s=demo',
				'number'  => 2,
			],
		];
		$search_queries = SearchQueryConverter::convert( $data );
		$this->assertEquals( $search_queries->getRowsCount(), 2 );
		$this->assertEquals( $search_queries->getFirstRow()->getColumn( 'nb_visits' ), 2 );
	}
}
