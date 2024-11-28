<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

abstract class Feature {

	public function is_enabled() {
		return true;
	}

	public function register_ajax() {
		// empty
	}

	public function register_hooks() {
		// empty
	}

	/**
	 * Optional. For tests only.
	 *
	 * @return void
	 */
	public function remove_hooks() {
		// empty
	}
}
