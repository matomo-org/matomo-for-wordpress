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

use WpMatomo\Admin\PluginSuggestions\Suggestion;

class HeatmapSessionRecording extends Suggestion {

	/**
	 * @var float
	 */
	private $bounce_rate;

	/**
	 * @var int
	 */
	private $nb_visits;

	/**
	 * @var int
	 */
	private $nb_visits_threshold;

	public function __construct( $nb_visits_threshold = 100 ) {
		$this->nb_visits_threshold = $nb_visits_threshold;
	}

	public function should_trigger() {
		$this->get_bounce_rate();
		return $this->nb_visits >= $this->nb_visits_threshold && $this->bounce_rate > 0.65;
	}

	public function get_trigger_desc_long() {
		return sprintf( $this->trigger_desc_long, round( $this->get_bounce_rate() * 100 ) );
	}

	public function init() {
		$this->plugin_slug        = 'HeatmapSessionRecording';
		$this->plugin_name        = __( 'Heatmap & Session Recording', 'matomo' );
		$this->plugin_desc_long   = __( 'Your bounce rate is above average. Understand how visitors actually interact with your pages.', 'matomo' );
		$this->plugin_desc_short  = __( 'Dive deep into your visitors\' behaviour', 'matomo' );
		$this->trigger_desc_short = __( 'High bounce rate', 'matomo' );
		$this->trigger_desc_long  = __( 'Bounce rate is %1$s%% — above average', 'matomo' );
		$this->image_file         = 'heatmap-session-recording.webp';
	}

	private function get_bounce_rate() {
		if ( ! isset( $this->bounce_rate ) ) {
			$data = $this->get_last_month_data( 'VisitsSummary.get' );
			$row  = $data->getFirstRow();

			$this->bounce_rate = 0;
			$this->nb_visits   = 0;

			if ( ! empty( $row ) ) {
				$this->bounce_rate = $row->getColumn( 'bounce_rate' );
				$this->nb_visits   = $row->getColumn( 'nb_visits' );
			}
		}
		return $this->bounce_rate;
	}
}
