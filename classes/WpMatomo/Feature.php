<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

/**
 * Encapsulates a plugin feature. To create a new Feature:
 *
 * - subclass Feature and fill it out
 * - create new instance of it in WpMatomo::get_all_features()
 */
abstract class Feature {

	/**
	 * Returns true if this feature is active for the current request.
	 * For example, if the feature is admin only, it would return true
	 * for admin pages, false if otherwise.
	 *
	 * Note: AJAX hooks are always added, regardless of what this function
	 * returns, since AJAX methods go through admin-ajax.php, and not
	 * any other script.
	 *
	 * @return bool
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Register hooks for this feature. AJAX actions should not be
	 * added here.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// empty
	}

	/**
	 * Register handlers for custom AJAX methods for the feature here.
	 *
	 * @return void
	 */
	public function register_ajax() {
		// empty
	}

	/**
	 * Optional. For tests only. Should remove hooks added in register_hooks()
	 * and in register_ajax().
	 *
	 * @return void
	 */
	public function remove_hooks() {
		// empty
	}
}
