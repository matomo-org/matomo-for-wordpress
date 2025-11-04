<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class AdBlockDetector {

	public function register_hooks() {
		if ( ! Admin::is_matomo_admin() ) {
			return;
		}

		add_action( 'admin_head', [ $this, 'display_adblocker_notice' ] );
	}

	public function display_adblocker_notice() {
		include __DIR__ . '/views/adblocker-notice.php';
	}
}
