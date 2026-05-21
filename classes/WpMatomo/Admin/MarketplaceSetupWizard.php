<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use WpMatomo\Feature;
use WpMatomo\Settings;

class MarketplaceSetupWizard extends Feature {
	const MARKETPLACE_PLUGIN_FILE   = 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php';
	const AJAX_IS_ACTIVE_NONCE_NAME = 'matomo-marketplace-setup-wizard-is-active';
	const AJAX_ACTIVATE_NONCE_NAME  = 'matomo-marketplace-setup-wizard-activate';

	public function is_active() {
		if ( ! is_admin() ) {
			return false;
		}

		if ( $this->is_plugin_install_page() ) {
			return true;
		}

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( Menu::SLUG_GET_STARTED === $_REQUEST['page'] ) {
			return true;
		}

		if ( Menu::SLUG_MARKETPLACE !== $_REQUEST['page'] ) {
			return false;
		}

		return true; // displayed in some manner on all tabs
	}

	public function get_body( $show_titles = true ) {
		return new MarketplaceSetupWizardBody( $show_titles );
	}

	public function show() {
		$matomo_logo_big               = plugins_url( 'assets/img/logo-big.png?v=' . rawurlencode( matomo_get_asset_version() ), MATOMO_ANALYTICS_FILE );
		$marketplace_setup_wizard_body = $this->get_body();

		include dirname( __FILE__ ) . '/views/marketplace_setup_wizard.php';
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_notices', [ $this, 'admin_notices' ] );
	}

	public function admin_notices() {
		if ( ! $this->is_plugin_install_page() ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'You are almost there! Upload the .zip file below to install the Marketplace and start exploring advanced analytics features.', 'matomo' ); ?>
			</p>
		</div>
		<?php
	}

	public function enqueue_scripts() {
		wp_enqueue_script(
			'matomo-marketplace-setup-wizard',
			plugins_url( '/assets/js/marketplace_setup_wizard.js', MATOMO_ANALYTICS_FILE ),
			[ 'jquery' ],
			matomo_get_asset_version(),
			true
		);

		wp_localize_script(
			'matomo-marketplace-setup-wizard',
			'mtmMarketplaceWizardAjax',
			[
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'is_active_nonce' => wp_create_nonce( self::AJAX_IS_ACTIVE_NONCE_NAME ),
				'activate_nonce'  => wp_create_nonce( self::AJAX_ACTIVATE_NONCE_NAME ),
				'is_welcome_page' => wp_unslash( $_REQUEST['page'] ) === Menu::SLUG_MARKETPLACE && wp_unslash( $_REQUEST['tab'] ) === 'marketplace',
			]
		);
	}

	public function register_ajax() {
		add_action( 'wp_ajax_matomo_is_marketplace_active', [ self::class, 'is_marketplace_active' ] );
		add_action( 'wp_ajax_matomo_activate_marketplace', [ self::class, 'activate_marketplace_plugin' ] );
	}

	public static function is_marketplace_active() {
		check_ajax_referer( self::AJAX_IS_ACTIVE_NONCE_NAME );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		wp_send_json(
			[
				'installed' => self::is_marketplace_installed(),
				'active'    => is_plugin_active( self::MARKETPLACE_PLUGIN_FILE ),
			]
		);
	}

	public static function activate_marketplace_plugin() {
		check_ajax_referer( self::AJAX_ACTIVATE_NONCE_NAME );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( [ 'message' => 'forbidden' ], 403 );
		}

		activate_plugin( self::MARKETPLACE_PLUGIN_FILE );
		wp_send_json( [] );
	}

	public static function is_marketplace_installed() {
		return is_file( WP_PLUGIN_DIR . '/' . self::MARKETPLACE_PLUGIN_FILE )
			|| is_file( WP_CONTENT_DIR . '/mu-plugins/' . self::MARKETPLACE_PLUGIN_FILE );
	}

	private function is_plugin_install_page() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$request_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		if ( ! preg_match( '%/wp-admin/plugin-install\\.php$%', $request_path ) ) {
			return false;
		}

		if (
			empty( $_REQUEST['tab'] )
			|| wp_unslash( $_REQUEST['tab'] ) !== 'upload'
			|| empty( $_REQUEST['mtm_marketplace_install'] )
		) {
			return false;
		}

		return true;
	}
}
