<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Config;
use Piwik\Plugins\Referrers\Archiver;
use Psr\Log\LoggerInterface;
use WP_STATISTICS\DB;
use WP_STATISTICS\MetaBox\referring;
use Piwik\Date;
use WP_STATISTICS\Referred;
use WpMatomo\WpStatistics\DataConverters\ReferrersConverter;

class ReferrersImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'Referrers';

	private $maximumRowsInDataTableLevelZero;

	private $maximumRowsInSubDataTable;

	public function __construct( LoggerInterface $logger ) {
		parent::__construct( $logger );
		// Reading pre 2.0 config file settings
		$this->maximumRowsInDataTableLevelZero = @Config::getInstance()->General['datatable_archiving_maximum_rows_referers'];
		$this->maximumRowsInSubDataTable = @Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referers'];
		if (empty($this->maximumRowsInDataTableLevelZero)) {
			$this->maximumRowsInDataTableLevelZero = Config::getInstance()->General['datatable_archiving_maximum_rows_referrers'];
			$this->maximumRowsInSubDataTable = Config::getInstance()->General['datatable_archiving_maximum_rows_subtable_referrers'];
		}
	}

	public function importRecords( Date $date ) {
		$referrers = $this->getReferrers($date);

		$this->insertRecord(Archiver::CAMPAIGNS_RECORD_NAME, $referrers, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable,
			$this->columnToSortByBeforeTruncation);
		return $referrers;
	}

	/**
	 * @param Date $date
	 *
	 * @see \WP_Statistics\Referred::GenerateReferSQL
	 * @return array
	 */
	public function getReferrers(Date $date) {
		$limit = 10000;
		global $wpdb;
		// Check Protocol Of domain
		$domain_name = rtrim(preg_replace('/^https?:\/\//', '', get_site_url()), " / ");
		foreach (array("http", "https", "ftp") as $protocol) {
			foreach (array('', 'www.') as $w3) {
				$where = " AND `referred` NOT LIKE '{$protocol}://{$w3}{$domain_name}%' ";
			}
		}

		// Return SQL
		$sql = "SELECT SUBSTRING_INDEX(REPLACE( REPLACE( referred, 'http://', '') , 'https://' , '') , '/', 1 ) as `domain`, count(referred) as `number` FROM " . DB::table('visitor') . " WHERE `referred` REGEXP \"^(https?://|www\\.)[\.A-Za-z0-9\-]+\\.[a-zA-Z]{2,4}\" AND referred <> '' AND LENGTH(referred) >=12 " . $where . " AND last_counter = '".$date->toString()."' GROUP BY domain LIMIT " . $limit;

		$referrers = $wpdb->get_results($sql, ARRAY_A);
		$referrers = ReferrersConverter::convert($referrers);
		$this->insertRecord(Archiver::WEBSITES_RECORD_NAME, $referrers, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable, $this->columnToSortByBeforeTruncation);


	}
}