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

class Funnels extends Suggestion {

	public function should_trigger() {
		$goals = $this->get_last_month_data( 'Goals.getGoals' );
		return count( $goals ) > 0;
	}

	public function init() {
		$this->plugin_name        = 'Funnels';
		$this->plugin_slug        = 'Funnels';
		$this->plugin_desc_long   = __( 'You\'re already tracking conversions. Identify where visitors drop off before completing your goals.', 'matomo' );
		$this->plugin_desc_short  = __( 'See how your audience flows through your marketing funnels', 'matomo' );
		$this->trigger_desc_short = __( 'Goal Detected', 'matomo' );
		$this->trigger_desc_long  = '';
		$this->image_file         = 'funnel.png';
	}
}
