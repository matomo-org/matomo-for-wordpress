<?php

use WpMatomo\WpStatistics\DataConverters\SearchKeywordConverter;

class SearchKeywordConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$search_keywords = SearchKeywordConverter::convert( [] );
		$this->assertEquals( $search_keywords->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data = [
			[
				'engine' => 'Bing',
				'words'  => 'import',
				'nb'     => 1,
			],
			[
				'engine' => 'Google',
				'words'  => 'matomo',
				'nb'     => 2,
			],

			[
				'engine' => 'Google',
				'words'  => 'import',
				'nb'     => 2,
			],
		];
		$search_keywords = SearchKeywordConverter::convert( $data );
		$this->assertEquals( $search_keywords->getRowsCount(), 2 );
		$this->assertEquals( $search_keywords->getFirstRow()->getColumn( 'nb_visits' ), 3 );
	}
}
