<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

class SubtableConverter {

	public static function aggregateByKey( array $wpStatisticData, $firstKey, $secondKey ) {
		$data = [];
		if ( count( $wpStatisticData ) ) {
			foreach ( $wpStatisticData as $row ) {
				if ( ! array_key_exists( $row[ $firstKey ], $data ) ) {
					$data[ $row[ $firstKey ] ] = [
						'label'            => $row[ $firstKey ],
						'data'             => [],
						'nb_uniq_visitors' => 0,
						'nb_visits'        => 0,
					];
				}
				$data[ $row[ $firstKey ] ]['data'][ $row[ $secondKey ] ] = [
					'label'            => $row[ $secondKey ],
					'nb_uniq_visitors' => $row['nb'],
					'nb_visits'        => $row['nb'],
				];
				$data[ $row[ $firstKey ] ]['nb_visits']                 += $row['nb'];
				$data[ $row[ $firstKey ] ]['nb_uniq_visitors']          += $row['nb'];
			}
		}

		$datatable = new DataTable();
		foreach ( $data as $key => $row ) {
			$data = $row['data'];
			unset( $row['data'] );
			$topLevelRow = self::addRowToTable( $datatable, new DataTable\Row( array( 0 => $row ) ), $key );
			foreach ( $data as $subkey => $subrow ) {
				self::addRowToSubtable( $topLevelRow, new DataTable\Row( array( 0 => $subrow ) ), $subkey );
			}
		}
		return $datatable;
	}

	protected static function addRowToSubtable( DataTable\Row $topLevelRow, DataTable\Row $rowToAdd, $newLabel ) {
		$subtable = $topLevelRow->getSubtable();
		if ( ! $subtable ) {
			$subtable = new DataTable();
			$topLevelRow->setSubtable( $subtable );
		}

		return self::addRowToTable( $subtable, $rowToAdd, $newLabel );
	}

	protected static function addRowToTable( DataTable $record, DataTable\Row $row, $newLabel ) {
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
}
