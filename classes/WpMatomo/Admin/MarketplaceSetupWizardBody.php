<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

class MarketplaceSetupWizardBody {

	/**
	 * @var bool
	 */
	private $matomo_show_title;

	public function __construct( $matomo_show_title = true ) {
		$this->matomo_show_title = $matomo_show_title;
	}

	public function show() {
		$user_can_upload_plugins   = current_user_can( 'upload_plugins' );
		$user_can_activate_plugins = current_user_can( 'activate_plugins' );
		$is_plugin_installed       = MarketplaceSetupWizard::is_marketplace_installed();
		$matomo_show_title         = $this->matomo_show_title;
		$matomo_is_plugin_active   = is_plugin_active( MarketplaceSetupWizard::MARKETPLACE_PLUGIN_FILE );

		include dirname( __FILE__ ) . '/views/marketplace_setup_wizard_body.php';
	}
}
