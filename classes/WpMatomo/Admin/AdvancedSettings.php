<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\IP;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site\Sync\SyncConfig as SiteConfigSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
/**
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */
class AdvancedSettings implements AdminSettingsInterface {
	const FORM_NAME  = 'matomo';
	const NONCE_NAME = 'matomo_advanced';

	public static $valid_host_headers = [
		'HTTP_CLIENT_IP',
		'HTTP_X_REAL_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'HTTP_CF_CONNECTING_IP',
		'HTTP_TRUE_CLIENT_IP',
		'HTTP_X_CLUSTER_CLIENT_IP',
	];

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var SiteConfigSync
	 */
	private $site_config_sync;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings         = $settings;
		$this->site_config_sync = new SiteConfigSync( $settings );
	}

	public function get_title() {
		return esc_html__( 'Advanced', 'matomo' );
	}

	public function show_settings() {
		$was_updated = $this->update_if_submitted();

		$matomo_client_headers = $this->site_config_sync->get_config_value( 'General', 'proxy_client_headers' );
		if ( empty( $matomo_client_headers ) ) {
			$matomo_client_headers = [];
		}

		Bootstrap::do_bootstrap();
		$matomo_detected_ip     = IP::getIpFromHeader();
		$matomo_delete_all_data = $this->settings->should_delete_all_data_on_uninstall();

		include dirname( __FILE__ ) . '/views/advanced_settings.php';
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
			 && ! empty( $_POST[ self::FORM_NAME ] )
			 && is_admin()
			 && check_admin_referer( self::NONCE_NAME )
			 && $this->can_user_manage() ) {
			$this->apply_settings();

			return true;
		}

		return false;
	}

	public function can_user_manage() {
		return current_user_can( Capabilities::KEY_SUPERUSER );
	}

	private function apply_settings() {
		if ( ! defined( 'MATOMO_REMOVE_ALL_DATA' ) ) {
			$this->settings->apply_changes(
				[
					Settings::DELETE_ALL_DATA_ON_UNINSTALL => ! empty( $_POST['matomo']['delete_all_data'] ),
				]
			);
		}

		$client_headers = [];
		if ( ! empty( $_POST[ self::FORM_NAME ]['proxy_client_header'] ) ) {
			$client_header = sanitize_text_field( wp_unslash( $_POST[ self::FORM_NAME ]['proxy_client_header'] ) );
			if ( in_array( $client_header, self::$valid_host_headers, true ) ) {
				$client_headers[] = $client_header;
			}
		}

		$this->site_config_sync->set_config_value( 'General', 'proxy_client_headers', $client_headers );

		return true;
	}
}
