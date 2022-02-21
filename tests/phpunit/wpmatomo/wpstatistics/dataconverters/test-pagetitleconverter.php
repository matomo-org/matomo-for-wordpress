<?php

use WpMatomo\WpStatistics\DataConverters\PagesTitleConverter;

class PageTitleConverterTest extends MatomoAnalytics_TestCase {

	public function test_empty_list() {
		$pages = PagesTitleConverter::convert( [] );
		$this->assertEquals( $pages->getRowsCount(), 0 );
	}

	public function test_aggregation() {
		$data  = [
			[
				'title'  => '/search',
				'number' => 1,
			],
			[
				'title'  => '/contact',
				'number' => 1,
			],
			[
				'title'  => '/search',
				'number' => 2,
			],
		];
		$pages = PagesTitleConverter::convert( $data );
		$this->assertEquals( $pages->getRowsCount(), 2 );
		$this->assertEquals( $pages->getFirstRow()->getColumn( \Piwik\Metrics::INDEX_NB_VISITS ), 3 );
	}
}
