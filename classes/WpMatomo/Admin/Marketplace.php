<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use WpMatomo\Capabilities;
use WpMatomo\Marketplace\Api;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Marketplace {
	const NONCE_LICENSE = 'matomo_license';
	const FORM_NAME = 'matomo_license_key';
	private $validTabs = array( 'subscriptions' );

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Api
	 */
	private $api;

	public function __construct( Settings $settings, Api $api ) {
		$this->settings = $settings;
		$this->api      = $api;
	}

	private function can_user_manage() {
		// only someone who can activate plugins is allowed to manage subscriptions
		if ( $this->settings->is_multisite() ) {
			return is_super_admin();
		}

		return current_user_can( Capabilities::KEY_SUPERUSER );
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
		     && isset( $_POST[ self::FORM_NAME ] )
		     && is_admin()
		     && check_admin_referer( self::NONCE_LICENSE )
		     && $this->can_user_manage() ) {

			if ( $this->api->is_valid_api_key( $_POST[ self::FORM_NAME ] ) ) {
				$this->settings->set_license_key( $_POST[ self::FORM_NAME ] );
			} else {
				$this->settings->set_license_key( '' );
				echo '<div class="error"><p>' . __( 'License key is not valid', 'matomo' ) . '</p></div>';
			}
		}
	}

	public function show() {
		$this->update_if_submitted();

		$active_tab = '';
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $this->validTabs, true ) ) {
			$active_tab = $_GET['tab'];
		}
		$settings                  = $this->settings;
		$can_view_subscription_tab = $this->can_user_manage();
		include( dirname( __FILE__ ) . '/views/marketplace.php' );
	}

}
