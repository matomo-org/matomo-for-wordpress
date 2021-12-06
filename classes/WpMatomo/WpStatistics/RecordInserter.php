<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics;

use Piwik\DataAccess\ArchiveWriter;
use Piwik\DataTable;
use Piwik\Metrics;
use WpMatomo\WpStatistics\Importers\Actions\RecordImporter;

class RecordInserter {

	/**
	 * @var ArchiveWriter
	 */
	private $archiveWriter;

	public function __construct( ArchiveWriter $writer ) {
		$this->archiveWriter = $writer;
	}

	public function insertRecord( $recordName, DataTable $record, $maximumRowsInDataTable = null,
									$maximumRowsInSubDataTable = null, $columnToSortByBeforeTruncation = null ) {
		$record->setMetadata( RecordImporter::IS_IMPORTED_FROM_WPSTATISTICS_METADATA_NAME, 1 );

		$blob = $record->getSerialized( $maximumRowsInDataTable, $maximumRowsInSubDataTable, $columnToSortByBeforeTruncation );
		$this->insertBlobRecord( $recordName, $blob );
	}

	public function insertBlobRecord( $name, $values ) {
		$this->archiveWriter->insertBlobRecord( $name, $values );
	}

	public function insertNumericRecords( array $values ) {
		foreach ( $values as $name => $value ) {
			if ( is_numeric( $name ) ) {
				$name = Metrics::getReadableColumnName( $name );
			}
			$this->archiveWriter->insertRecord( $name, $value );
		}
	}
}
