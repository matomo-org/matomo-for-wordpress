<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Config;
use Piwik\DataTable;
use WP_STATISTICS\MetaBox\top_visitors;
use WpMatomo\WpStatistics\RecordInserter;
use Psr\Log\LoggerInterface;
use Piwik\Date;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class RecordImporter {

	const IS_IMPORTED_FROM_WPSTATISTICS_METADATA_NAME = 'is_imported_from_wpstatistics';
	protected $logger                                 = null;

	protected $maximum_rows_in_data_table_level_zero;

	protected $maximum_rows_in_sub_data_table;

	protected $record_inserter;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		// Reading pre 2.0 config file settings
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$this->maximum_rows_in_data_table_level_zero = @Config::getInstance()->General['datatable_archiving_maximum_rows_actions'];
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$this->maximum_rows_in_sub_data_table = @Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_actions'];
		if ( empty( $this->maximum_rows_in_data_table_level_zero ) ) {
			$this->maximum_rows_in_data_table_level_zero = Config::getInstance()->General['datatable_archiving_maximum_rows_referrers'];
			$this->maximum_rows_in_sub_data_table        = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referrers'];
		}
	}

	public function supports_site() {
		return true;
	}

	public function set_record_inserter( RecordInserter $record_inserter ) {
		$this->record_inserter = $record_inserter;
	}

	protected function insert_record(
		$record_name, DataTable $record, $maximum_rows_in_data_table = null,
		$maximum_rows_in_sub_data_table = null, $column_to_sort_by_before_truncation = null
	) {
		$this->record_inserter->insert_record( $record_name, $record, $maximum_rows_in_data_table, $maximum_rows_in_sub_data_table, $column_to_sort_by_before_truncation );
	}

	protected function insert_blob_record( $name, $values ) {
		$this->record_inserter->insert_blob_record( $name, $values );
	}

	protected function insert_numeric_records( array $values ) {
		$this->record_inserter->insert_numeric_records( $values );
	}

	protected function get_visitors( Date $date ) {
		$page           = 1;
		$limit          = 1000;
		$visitors_found = [];
		do {
			$visitors = top_visitors::get(
				[
					'day'      => $date->toString(),
					'per_page' => $limit,
					'paged'    => $page,
				]
			);
			$page ++;
			$no_data = ( ( array_key_exists( 'no_data', $visitors ) ) && ( 1 === $visitors['no_data'] ) );
			if ( $no_data ) {
				$visitors = [];
			}
			$visitors_found = array_merge( $visitors_found, $visitors );
		} while ( true !== $no_data );
		return $visitors_found;
	}
}
