<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Plugins\Referrers\Archiver;
use WP_STATISTICS\DB;
use Piwik\Date;
use WpMatomo\WpStatistics\DataConverters\ReferrersConverter;

class ReferrersImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'Referrers';

	public function importRecords( Date $date ) {
		$this->importReferrers($date);
	}

	/**
	 * @param Date $date
	 */
	private function importReferrers(Date $date) {
		$referrers = $this->getReferrers($date);
		$referrers = ReferrersConverter::convert($referrers);
		$this->logger->debug('Import {nb_referrers} referrers...', ['nb_referrers' => $referrers->getRowsCount()]);
		$this->insertRecord(Archiver::WEBSITES_RECORD_NAME, $referrers, $this->maximumRowsInDataTableLevelZero, $this->maximumRowsInSubDataTable);
		Common::destroy($referrers);
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

		return $wpdb->get_results($sql, ARRAY_A);


	}
}