<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions\Suggestions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

use Piwik\Common;
use Piwik\DataTable;
use WpMatomo\Admin\PluginSuggestions\Suggestion;
use WpMatomo\Bootstrap;

class SearchEngineKeywordsPerformance extends Suggestion {

	private $visits_threshold;

	public function __construct( $visits_threshold = 100 ) {
		$this->visits_threshold = $visits_threshold;
	}

	public function should_trigger() {
		Bootstrap::do_bootstrap();

		/** @var DataTable $data */
		$data           = $this->get_last_month_data(
			'Referrers.getReferrerType',
			500,
			'label',
			[ '_setReferrerTypeLabel' => 0 ]
		);
		$search_engines = $data->getRowFromLabel( Common::REFERRER_TYPE_SEARCH_ENGINE );

		if ( empty( $search_engines ) ) {
			return false;
		}

		$visits_from_search_engines = $search_engines->getColumn( 'nb_visits' );
		if ( empty( $visits_from_search_engines ) ) {
			return false;
		}

		$total = array_sum( $data->getColumn( 'nb_visits' ) );
		if ( empty( $total ) ) {
			return false;
		}

		$percent_from_search_engines = (float) $visits_from_search_engines / (float) $total;

		return $total >= $this->visits_threshold && $percent_from_search_engines > 0.40;
	}

	public function init() {
		$this->plugin_slug        = 'SearchEngineKeywordsPerformance';
		$this->plugin_name        = __( 'Search Engine Keywords Performance', 'matomo' );
		$this->plugin_desc_long   = __( 'A large share of your traffic comes from search engines. Discover which keywords bring visitors to your site.', 'matomo' );
		$this->plugin_desc_short  = __( 'Uncover the keywords people use to find your site', 'matomo' );
		$this->trigger_desc_short = __( 'Search engine traffic', 'matomo' );
		$this->trigger_desc_long  = __( 'High traffic from search engines', 'matomo' );
		$this->image_file         = 'search-engine-keywords-performance.webp';
	}
}
