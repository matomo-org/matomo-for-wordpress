<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions;

use WpMatomo\Feature;

/*
 * TODO:
 * - create trigger class and fill out for every suggestion
 * - add daily task to check plugins
 * - display ui if there is a suggestion
 */

class PluginSuggestions extends Feature {

	const SUGGESTIONS_TRIGGERED_OPTION_NAME = 'matomo_plugin_suggestions_triggered';

	public function register_hooks() {
		// TODO
	}

	public function show() {
		// TODO
	}

	public function check() {
		// TODO
	}

	/**
	 * @return Suggestion[]
	 */
	private function get_suggestions() {
		// ordered by priority to show
		return []; // TODO
	}
}

