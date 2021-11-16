<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\DataTable;
use WpMatomo\WpStatistics\RecordInserter;
use Psr\Log\LoggerInterface;

class RecordImporter {

	protected $logger = null;

	const IS_IMPORTED_FROM_WPSTATISTICS_METADATA_NAME = 'is_imported_from_wpstatistics';

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	public function supportsSite()
	{
		return true;
	}

	public function setRecordInserter(RecordInserter $recordInserter)
	{
		$this->recordInserter = $recordInserter;
	}

	protected function insertRecord($recordName, DataTable $record, $maximumRowsInDataTable = null,
		$maximumRowsInSubDataTable = null, $columnToSortByBeforeTruncation = null)
	{
		$this->recordInserter->insertRecord($recordName, $record, $maximumRowsInDataTable, $maximumRowsInSubDataTable, $columnToSortByBeforeTruncation);
	}

	protected function insertBlobRecord($name, $values)
	{
		$this->recordInserter->insertBlobRecord($name, $values);
	}

	protected function insertNumericRecords(array $values)
	{
		$this->recordInserter->insertNumericRecords($values);
	}

	protected function addRowToTable(DataTable $record, DataTable\Row $row, $newLabel)
	{
		if ($newLabel === false || $newLabel === null) {
			$recordImporterClass = get_class($this);
			throw new \Exception("Unexpected error: adding row to table with empty label in $recordImporterClass: " . var_export($newLabel, true));
		}

		$foundRow = $record->getRowFromLabel($newLabel);
		if (empty($foundRow)) {
			$foundRow = clone $row;
			$foundRow->deleteMetadata();
			$foundRow->setColumn('label', $newLabel);
			$record->addRow($foundRow);
		} else {
			$foundRow->sumRow($row, $copyMetadata = false);
		}
		return $foundRow;
	}

	protected function addRowToSubtable(DataTable\Row $topLevelRow, DataTable\Row $rowToAdd, $newLabel)
	{
		$subtable = $topLevelRow->getSubtable();
		if (!$subtable) {
			$subtable = new DataTable();
			$topLevelRow->setSubtable($subtable);
		}
		return $this->addRowToTable($subtable, $rowToAdd, $newLabel);
	}


}