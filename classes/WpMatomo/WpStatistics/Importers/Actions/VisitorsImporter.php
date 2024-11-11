<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Common;
use Piwik\Metrics;
use WP_STATISTICS\MetaBox\top_visitors;
use Piwik\Date;
use WP_Statistics\Models\VisitorsModel;
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

			// copied from wp-statistics-meta-box-top-visitors.php.
			// used to use top_visitors::get(), but that no longer supports
			// pagination.
			try {
				$visitors_model = new VisitorsModel();
				$response       = $visitors_model->getVisitorsData(
					[
						'date'      => [
							'from' => $date->toString(),
							'to'   => $date->toString(),
						],
						'page'      => $page,
						'per_page'  => $limit,
						'order_by'  => 'hits',
						'order'     => 'DESC',
						'user_info' => true,
						'page_info' => true,
					]
				);
			} catch ( \Exception $e ) {
				$response = array();
			}

			$no_data = count( $response ) < 1;
			if ( ! $no_data ) {
				$visits = array_merge( $visits, $response );
			}
		} while ( true !== $no_data );

		$this->logger->debug( 'Import {nb_visits} visits...', [ 'nb_visits' => count( $visits ) ] );
		$this->insert_numeric_records( [ Metrics::INDEX_NB_UNIQ_VISITORS => count( $visits ) ] );
		$this->insert_numeric_records( [ Metrics::INDEX_NB_VISITS => count( $visits ) ] );
		Common::destroy( $visits );
		return $visits;
	}
}
