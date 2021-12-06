<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Plugins\Actions\Archiver;
use WP_STATISTICS\MetaBox\pages;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\PagesUrlConverter;
use WpMatomo\WpStatistics\DataConverters\PagesTitleConverter;

class PagesImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'Actions';

	public function importRecords( Date $date ) {
		$limit = 100;
		$pages = [];
		$page  = 0;
		do {
			$page ++;
			$pages_found = pages::get(
				[
					'from'     => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
					'to'       => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
					'per_page' => $limit,
					'paged'    => $page,
				]
			);
			$no_data     = ( ( array_key_exists( 'no_data', $pages_found ) ) && ( $pages_found['no_data'] === 1 ) );
			if ( ! $no_data ) {
				$pages = array_merge( $pages, $pages_found );
			}
		} while ( $no_data !== true );

		foreach ( $pages as $id => $page ) {
			$pos = strpos( $page['str_url'], '?' );
			if ( $pos !== false ) {
				$pages[ $id ]['str_url'] = substr( $page['str_url'], 0, $pos );
			}
		}
		$pagesUrl = PagesUrlConverter::convert( $pages );
		$this->logger->debug( 'Import {nb_pages} pages...', [ 'nb_pages' => $pagesUrl->getRowsCount() ] );
		$this->insertRecord( Archiver::PAGE_URLS_RECORD_NAME, $pagesUrl, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable );

		$pagesTitle = PagesTitleConverter::convert( $pages );
		$this->logger->debug( 'Import {nb_pages} page titles...', [ 'nb_pages' => $pagesTitle->getRowsCount() ] );
		$this->insertRecord( Archiver::PAGE_TITLES_RECORD_NAME, $pagesTitle, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable );
	}
}
