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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Admin extends Feature {

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

	public function is_active() {
		return is_admin();
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_filter( 'admin_body_class', [ $this, 'on_admin_body_class' ], 9999 );
	}

	public static function is_matomo_admin() {
		return substr( self::get_current_page(), 0, 7 ) === 'matomo-';
	}

	public static function get_current_page() {
		return isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	}

	public function load_scripts() {
		wp_enqueue_style( 'matomo_admin_css', plugins_url( 'assets/css/admin-style.css', MATOMO_ANALYTICS_FILE ), false, matomo_get_asset_version() );
		wp_enqueue_script( 'matomo_iframe_resizer', plugins_url( 'assets/js/iframeResizer.min.js', MATOMO_ANALYTICS_FILE ), [], matomo_get_asset_version(), [ 'defer', false ] );

		wp_enqueue_script(
			'matomo-admin-js',
			plugins_url( '/assets/js/admin.js', MATOMO_ANALYTICS_FILE ),
			[ 'jquery' ],
			matomo_get_asset_version(),
			true
		);
		wp_localize_script(
			'matomo-admin-js',
			'mtmSystemReportErrorNoticeAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'matomo-systemreport-notice-dismiss' ),
			]
		);
		wp_localize_script(
			'matomo-admin-js',
			'mtmReferralDismissNoticeAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'matomo-referral-notice-dismiss' ),
			]
		);
	}

	public function on_admin_body_class( $classes ) {
		$raw_version = get_bloginfo( 'version' );
		if ( ! $raw_version ) {
			return $classes;
		}

		$version_parts = explode( '-', $raw_version );
		$version       = count( $version_parts ) > 1 ? $version_parts[0] : $raw_version;

		if ( version_compare( $version, '7.0', '>=' ) ) {
			$classes .= 'mtm-wp-gte-7';
		}

		return $classes;
	}
}
