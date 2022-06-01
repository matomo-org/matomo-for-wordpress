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
				'words'  => 'matomo',
				'nb'     => 2,
			],
			[
				'engine' => 'Bing',
				'words'  => 'import',
				'nb'     => 1,
			],
			[
				'engine' => 'Google',
				'words'  => 'import',
				'nb'     => 2,
			],
		];
		$search_engines = SearchEngineConverter::convert( $data );
		$this->assertEquals( $search_engines->getRowsCount(), 2 );
		$this->assertEquals( $search_engines->getFirstRow()->getColumn( 'nb_visits' ), 4 );
	}
}
