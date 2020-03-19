<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\Config;
use Piwik\IP;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\TrackingCode\TrackingCodeGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class AdvancedSettings implements AdminSettingsInterface {
	const FORM_NAME             = 'matomo';
	const NONCE_NAME            = 'matomo_advanced';

	public static $valid_host_headers = array(
		'HTTP_CLIENT_IP',
		'HTTP_X_REAL_IP',
		'HTTP_X_FORWARDED_FOR',
		'HTTP_X_FORWARDED',
		'HTTP_FORWARDED_FOR',
		'HTTP_FORWARDED',
		'HTTP_CF_CONNECTING_IP',
		'HTTP_TRUE_CLIENT_IP',
		'HTTP_X_CLUSTER_CLIENT_IP',
	);

	public function get_title() {
		return esc_html__( 'Advanced', 'matomo' );
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
		Bootstrap::do_bootstrap();
		$config = Config::getInstance();
		$general = $config->General;
		$general['proxy_client_headers'] = array();

		if (!empty($_POST[ self::FORM_NAME ]['proxy_client_header'])) {
			$client_header = $_POST[ self::FORM_NAME ]['proxy_client_header'];
			if (in_array($client_header, self::$valid_host_headers, true)) {
				$general['proxy_client_headers'][] = $client_header;
			}
		}
		$config->General = $general;
		$config->forceSave();

		return true;
	}

	public function show_settings() {
		$was_updated = $this->update_if_submitted();

		$matomo_client_headers = array();
		Bootstrap::do_bootstrap();
		$config = Config::getInstance();
		$general = $config->General;
		if (!empty($general['proxy_client_headers']) && is_array($general['proxy_client_headers'])) {
			$matomo_client_headers = $general['proxy_client_headers'];
		}

		$matomo_detected_ip = IP::getIpFromHeader();

		include dirname( __FILE__ ) . '/views/advanced_settings.php';
	}



}
