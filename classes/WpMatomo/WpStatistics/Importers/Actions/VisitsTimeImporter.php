<?php
namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Plugins\VisitTime\Archiver;
use WP_STATISTICS\MetaBox\top_visitors;
use Piwik\Date;
use WpMatomo\WpStatistics\Config;
use WpMatomo\WpStatistics\DataConverters\VisitsTimeConverter;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class VisitsTimeImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'VisitTime';

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
		if ( $visits ) {
			$visits = VisitsTimeConverter::convert( $visits );
			$this->insert_record( Archiver::SERVER_TIME_RECORD_NAME, $visits );
		}
		Common::destroy( $visits );

		return $visits;
	}
}
