<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Config;
use Piwik\DataTable;
use WP_STATISTICS\MetaBox\top_visitors;
use WpMatomo\WpStatistics\RecordInserter;
use Psr\Log\LoggerInterface;
use Piwik\Date;

class RecordImporter {

	const IS_IMPORTED_FROM_WPSTATISTICS_METADATA_NAME = 'is_imported_from_wpstatistics';
	protected $logger = null;

	protected $maximumRowsInDataTableLevelZero;

	protected $maximumRowsInSubDataTable;

	public function __construct( LoggerInterface $logger ) {
		$this->logger = $logger;
		// Reading pre 2.0 config file settings
		$this->maximumRowsInDataTableLevelZero = @Config::getInstance()->General['datatable_archiving_maximum_rows_referers'];
		$this->maximumRowsInSubDataTable = @Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referers'];
		if (empty($this->maximumRowsInDataTableLevelZero)) {
			$this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_referrers'];
			$this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referrers'];
		}
	}

	public function supportsSite() {
		return true;
	}

	public function setRecordInserter( RecordInserter $recordInserter ) {
		$this->recordInserter = $recordInserter;
	}

	protected function insertRecord(
		$recordName, DataTable $record, $maximumRowsInDataTable = null,
		$maximumRowsInSubDataTable = null, $columnToSortByBeforeTruncation = null
	) {
		$this->recordInserter->insertRecord( $recordName, $record, $maximumRowsInDataTable, $maximumRowsInSubDataTable, $columnToSortByBeforeTruncation );
	}

	protected function insertBlobRecord( $name, $values ) {
		$this->recordInserter->insertBlobRecord( $name, $values );
	}

	protected function insertNumericRecords( array $values ) {
		$this->recordInserter->insertNumericRecords( $values );
	}

	protected function addRowToSubtable( DataTable\Row $topLevelRow, DataTable\Row $rowToAdd, $newLabel ) {
		$subtable = $topLevelRow->getSubtable();
		if ( ! $subtable ) {
			$subtable = new DataTable();
			$topLevelRow->setSubtable( $subtable );
		}

		return $this->addRowToTable( $subtable, $rowToAdd, $newLabel );
	}

	protected function addRowToTable( DataTable $record, DataTable\Row $row, $newLabel ) {
		if ( $newLabel === false || $newLabel === null ) {
			$recordImporterClass = get_class( $this );
			throw new \Exception( "Unexpected error: adding row to table with empty label in $recordImporterClass: " . var_export( $newLabel, true ) );
		}

		$foundRow = $record->getRowFromLabel( $newLabel );
		if ( empty( $foundRow ) ) {
			$foundRow = clone $row;
			$foundRow->deleteMetadata();
			$foundRow->setColumn( 'label', $newLabel );
			$record->addRow( $foundRow );
		} else {
			$foundRow->sumRow( $row, $copyMetadata = false );
		}

		return $foundRow;
	}

	protected function getVisitors( Date $date ) {
		$page  = 1;
		$limit = 1000;
		$visitorsFound = [];
		do {
			$visitors = top_visitors::get(
				[
					'day'      => $date->toString(),
					'per_page' => $limit,
					'paged'    => $page
				]
			);
			$page ++;
			$noData = ( ( array_key_exists( 'no_data', $visitors ) ) && ( $visitors['no_data'] === 1 ) );
			if ( $noData ) {
				$visitors = [];
			}
			$visitorsFound = array_merge( $visitorsFound, $visitors );
		} while ( $noData !== true );
		return $visitorsFound;
	}
}