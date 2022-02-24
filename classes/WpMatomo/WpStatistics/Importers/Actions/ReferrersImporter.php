<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Plugins\Referrers\Archiver;
use WP_STATISTICS\DB;
use Piwik\Date;
use WpMatomo\WpStatistics\DataConverters\ReferrersConverter;
use WpMatomo\WpStatistics\DataConverters\SearchEngineConverter;
use WpMatomo\WpStatistics\DataConverters\SearchKeywordConverter;

/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 *
 * phpcs:disable WordPress.DB
 */
class ReferrersImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'Referrers';

	public function import_records( Date $date ) {
		$this->import_referrers( $date );
		$this->import_search_engines( $date );
	}

	/**
	 * @param Date $day
	 *
	 * @return DataTable[]
	 */
	private function get_keywords_and_search_engine_records( Date $day ) {
		global $wpdb;
		$sql                = 'select engine, words, count(visitor) AS nb from ' . DB::table( 'search' ) . " where last_counter = '" . $day->toString() . "' group by engine, words order by engine, words;";
		$wp_statistics_data = $wpdb->get_results( $sql, ARRAY_A );
		$this->convert_search_engines( $wp_statistics_data );
		$search_engine_by_keyword = SearchEngineConverter::convert( $wp_statistics_data );

		$sql                = 'select engine, words, count(visitor) AS nb from ' . DB::table( 'search' ) . " where last_counter = '" . $day->toString() . "' group by words, engine order by words, engine;";
		$wp_statistics_data = $wpdb->get_results( $sql, ARRAY_A );
		$this->convert_search_engines( $wp_statistics_data );
		$keyword_by_search_engine = SearchKeywordConverter::convert( $wp_statistics_data );
		return [ $keyword_by_search_engine, $search_engine_by_keyword ];
	}

	/**
	 * Matomo expects some fixed values for the search engine.
	 * Convert them here
	 *
	 * @param $wp_statistics_data
	 *
	 * @return void
	 */
	private function convert_search_engines( &$wp_statistics_data ) {
		foreach ( $wp_statistics_data as $row => $line ) {
			/*
			 * the list of search engine available from wpstatistics is fixed
			 */
			$wp_statistics_data[ $row ]['engine'] = str_replace(
				[ 'google', 'duckduckgo', 'bing', 'baidu', 'yahoo', 'yandex', 'startpage', 'qwant', 'ecosia', 'ask' ],
				[ 'Google', 'DuckDuckGo', 'Bing', 'Baidu', 'Yahoo!', 'Yandex', 'StartPage', 'Qwant', 'Ecosia', 'Ask' ],
				$line['engine']
			);
		}
	}
	private function import_search_engines( Date $date ) {
		list($keyword_by_search_engine, $search_engine_by_keyword) = $this->get_keywords_and_search_engine_records( $date );
		$this->logger->debug( 'Import {nb_sk} search keywords...', [ 'nb_sk' => $keyword_by_search_engine->getRowsCount() ] );
		$this->insert_record( Archiver::KEYWORDS_RECORD_NAME, $keyword_by_search_engine, $this->maximum_rows_in_data_table_level_zero, $this->maximum_rows_in_sub_data_table );
		Common::destroy( $keyword_by_search_engine );

		$this->logger->debug( 'Import {nb_se} search engines...', [ 'nb_se' => $search_engine_by_keyword->getRowsCount() ] );
		$this->insert_record( Archiver::SEARCH_ENGINES_RECORD_NAME, $search_engine_by_keyword, $this->maximum_rows_in_data_table_level_zero, $this->maximum_rows_in_sub_data_table );
		Common::destroy( $search_engine_by_keyword );
	}
	/**
	 * @param Date $date
	 */
	private function import_referrers( Date $date ) {
		$referrers = $this->get_referrers( $date );
		$referrers = ReferrersConverter::convert( $referrers );
		$this->logger->debug( 'Import {nb_referrers} referrers...', [ 'nb_referrers' => $referrers->getRowsCount() ] );
		$this->insert_record( Archiver::WEBSITES_RECORD_NAME, $referrers, $this->maximum_rows_in_data_table_level_zero, $this->maximum_rows_in_sub_data_table );
		Common::destroy( $referrers );
	}
	/**
	 * @param Date $date
	 *
	 * @see \WP_Statistics\Referred::GenerateReferSQL
	 * @return array
	 */
	public function get_referrers( Date $date ) {
		$limit = 10000;
		global $wpdb;
		// Check Protocol Of domain
		$domain_name = rtrim( preg_replace( '/^https?:\/\//', '', get_site_url() ), ' / ' );
		// Return SQL
		$sql = $wpdb->prepare(
			"SELECT SUBSTRING_INDEX(REPLACE( REPLACE( referred, 'http://', '') , 'https://' , '') , '/', 1 ) as `domain`, count(referred) as `number` FROM " . DB::table( 'visitor' ) . ' WHERE `referred` REGEXP "^(https?://|www\\.)[\.A-Za-z0-9\-]+\\.[a-zA-Z]{2,4}" AND referred <> \'\' AND LENGTH(referred) >=12  AND `referred` NOT LIKE \'%s\' AND last_counter = \'%s\' GROUP BY domain LIMIT %d',
			array( '%' . $wpdb->_real_escape( $domain_name ) . '%', $date->toString(), $limit )
		);
		return $wpdb->get_results( $sql, ARRAY_A );
	}
}
