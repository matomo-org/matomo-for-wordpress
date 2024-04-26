<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\Context;
use Piwik\FrontController;
use WpMatomo\Bootstrap;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class PluginMeasurableSettings implements AdminSettingsInterface {

	/**
	 * @var string
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $plugin_display_name;

	public function __construct( $plugin_name, $plugin_display_name ) {
		$this->plugin_name         = $plugin_name;
		$this->plugin_display_name = $plugin_display_name;
	}

	public function get_title() {
		return esc_html( $this->plugin_display_name );
	}

	public function show_settings() {
		$plugin_name         = $this->plugin_name;
		$plugin_display_name = $this->plugin_display_name;
		$home_url            = home_url();
		$site                = new Site();
		$idsite              = $site->get_current_matomo_site_id();

		Bootstrap::do_bootstrap();

		$matomo_html = Context::executeWithQueryParameters(
			[
				'idSite' => $idsite,
				'plugin' => $plugin_name,
				'module' => 'WordPress',
				'action' => 'showMeasurableSettings',
			],
			function () {
				return FrontController::getInstance()->dispatch();
			}
		);

		include dirname( __FILE__ ) . '/views/measurable_settings.php';
	}
}
