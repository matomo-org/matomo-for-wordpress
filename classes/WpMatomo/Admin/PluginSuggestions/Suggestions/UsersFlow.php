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

class UsersFlow extends Suggestion {

	const UNIQUE_PAGE_THRESHOLD = 20;

	public function should_trigger() {
		return wp_count_posts() > self::UNIQUE_PAGE_THRESHOLD;
	}

	public function init() {
		$this->plugin_slug        = 'UsersFlow';
		$this->plugin_name        = __( 'Users Flow', 'matomo' );
		$this->plugin_desc_long   = __( 'Your website contains many unique pages. Understand how visitors navigate from one page to another.', 'matomo' );
		$this->plugin_desc_short  = __( 'Map the paths your users take', 'matomo' );
		$this->trigger_desc_short = __( 'Unique pages', 'matomo' );
		$this->trigger_desc_long  = __( 'High count of unique page URLs', 'matomo' );
	}
}
