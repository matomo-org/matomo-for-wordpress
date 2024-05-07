<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

class MarketplaceSetupWizard {
	public function __construct() {
		$this->add_hooks();
	}

	public function show() {
		$matomo_logo_big         = plugins_url( 'assets/img/logo-big.png', MATOMO_ANALYTICS_FILE );
		$user_can_upload_plugins = current_user_can( 'upload_plugins' );

		include dirname( __FILE__ ) . '/views/marketplace_setup_wizard.php';
	}

	private function add_hooks() {
		if ( ! current_user_can( 'upload_plugins' ) ) {
			return;
		}

		$this->enqueue_scripts();
	}

	private function enqueue_scripts() {
		wp_enqueue_script(
			'matomo-marketplace-setup-wizard',
			plugins_url( '/assets/js/marketplace_setup_wizard.js', MATOMO_ANALYTICS_FILE ),
			[ 'jquery' ],
			'1.0.0',
			true
		);

		wp_localize_script(
			'matomo-marketplace-setup-wizard',
			'mtmMarketplaceWizardAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'matomo-marketplace-setup-wizard' ),
			]
		);
	}

	public static function register_ajax() {
		add_action( 'wp_ajax_mtm_is_marketplace_active', [ self::class, 'is_marketplace_active' ] );
	}

	public static function is_marketplace_active() {
		wp_send_json( [ 'active' => is_plugin_active( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' ) ] );
	}
}
