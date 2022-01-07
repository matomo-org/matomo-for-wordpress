<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Metrics;
use WP_STATISTICS\MetaBox\top_visitors;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class VisitorsImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'VisitsSummary';

	public function import_records( Date $date ) {
		$limit  = 100;
		$visits = [];
		$page   = 0;
		do {
			$page ++;
			$visits_found = top_visitors::get(
				[
					'day'      => $date->toString( Config::WP_STATISTICS_DATE_FORMAT ),
					'per_page' => $limit,
					'paged'    => $page,
				]
			);
			$no_data      = ( ( array_key_exists( 'no_data', $visits_found ) ) && ( 1 === $visits_found['no_data'] ) );
			if ( ! $no_data ) {
				$visits = array_merge( $visits, $visits_found );
			}
		} while ( true !== $no_data );

		$this->logger->debug( 'Import {nb_visits} visits...', [ 'nb_visits' => count( $visits ) ] );
		$this->insert_numeric_records( [ Metrics::INDEX_NB_UNIQ_VISITORS => count( $visits ) ] );
		$this->insert_numeric_records( [ Metrics::INDEX_NB_VISITS => count( $visits ) ] );
		Common::destroy( $visits );
		return $visits;
	}
}
