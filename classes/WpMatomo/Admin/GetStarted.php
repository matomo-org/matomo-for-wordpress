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

class GetStarted implements MatomoPageContent {
	const NONCE_NAME = 'matomo_enable_tracking';
	const FORM_NAME  = 'matomo';

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public static function register_hooks() {
		add_action( 'matomo_before_add_menu', [ self::class, 'on_marketplace_plugin_activated' ] );
	}

	public static function on_marketplace_plugin_activated() {
		$settings = new Settings();

		$is_hidden = self::auto_hide_page_if_steps_completed( $settings );
		if (
			$is_hidden
			&& isset( $_REQUEST['page'] )
			&& Menu::SLUG_GET_STARTED === $_REQUEST['page']
		) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . Menu::SLUG_REPORT_SUMMARY ) );
			exit;
		}
	}

	public static function auto_hide_page_if_steps_completed( Settings $settings ) {
		if ( self::should_auto_hide_get_started_page( $settings ) ) {
			$settings->apply_changes(
				[
					Settings::SHOW_GET_STARTED_PAGE => 0,
				]
			);

			return true;
		}

		return false;
	}
	public function show() {
		$was_updated                  = $this->update_if_submitted();
		$settings                     = $this->settings;
		$can_user_edit                = $this->can_user_manage();
		$show_this_page               = $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE );
		$matomo_is_marketplace_active = $this->is_marketplace_installed_and_active();

		$matomo_marketplace_setup_wizard      = new MarketplaceSetupWizard();
		$matomo_marketplace_setup_wizard_body = $matomo_marketplace_setup_wizard->get_body( false );

		include dirname( __FILE__ ) . '/views/get_started.php';
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
			 && ! empty( $_POST[ self::FORM_NAME ] )
			 && is_admin()
			 && check_admin_referer( self::NONCE_NAME )
			 && $this->can_user_manage() ) {
			if ( ! empty( $_POST[ self::FORM_NAME ][ Settings::SHOW_GET_STARTED_PAGE ] )
				 && 'no' === $_POST[ self::FORM_NAME ][ Settings::SHOW_GET_STARTED_PAGE ] ) {
				$this->settings->apply_changes(
					[
						Settings::SHOW_GET_STARTED_PAGE => 0,
					]
				);

				return true;
			}
			if ( ! empty( $_POST[ self::FORM_NAME ]['track_mode'] )
				 && TrackingSettings::TRACK_MODE_DEFAULT === $_POST[ self::FORM_NAME ]['track_mode'] ) {
				$this->settings->apply_tracking_related_changes( [ 'track_mode' => TrackingSettings::TRACK_MODE_DEFAULT ] );

				self::auto_hide_page_if_steps_completed( $this->settings );

				return true;
			}
		}

		return false;
	}

	public static function should_auto_hide_get_started_page( Settings $settings ) {
		return $settings->is_tracking_enabled()
			&& self::is_marketplace_installed_and_active();
	}

	public static function is_marketplace_installed_and_active() {
		return MarketplaceSetupWizard::is_marketplace_installed()
			&& is_plugin_active( MarketplaceSetupWizard::MARKETPLACE_PLUGIN_FILE );
	}

	public function can_user_manage() {
		$tracking_settings = new TrackingSettings( $this->settings );

		return $tracking_settings->can_user_manage();
	}

	public function get_title() {
		return __( 'Start getting a full picture of your visitors', 'matomo' );
	}
}
