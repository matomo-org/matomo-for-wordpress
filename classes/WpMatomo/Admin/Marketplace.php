<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Marketplace {

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	private function show_premium_bundle_offer() {
		if (get_option('matomo_marketplace_license_key')) {
			return false; // already has features
		}
		if (is_plugin_active('HeatmapSessionRecording/HeatmapSessionRecording.php')) {
			return false; // already has features
		}
		return true;
	}

	public function show() {
		$settings = $this->settings;

		$matomo_show_offer = $this->show_premium_bundle_offer();

		include dirname( __FILE__ ) . '/views/marketplace.php';
	}

}
