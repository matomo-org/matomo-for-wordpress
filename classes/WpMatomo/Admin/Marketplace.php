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
use WpMatomo\Capabilities;

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

	public function get_active_tab() {
		$active_tab = 'marketplace';

		$valid_tabs = $this->get_valid_tabs();

		if ( isset( $_GET['tab'] )
			&& in_array( $_GET['tab'], $valid_tabs, true )
		) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$active_tab = wp_unslash( $_GET['tab'] );
		}

		return $active_tab;
	}

	public function show() {
		$settings   = $this->settings;
		$valid_tabs = [];
		$active_tab = '';

		if ( ! is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) {
			$active_tab = $this->get_active_tab();
			$valid_tabs = $this->get_valid_tabs();

			$marketplace_setup_wizard = \WpMatomo::get_active_feature( MarketplaceSetupWizard::class );
		}

		include dirname( __FILE__ ) . '/views/marketplace.php';
	}

	private function get_valid_tabs() {
		$valid_tabs = [ 'marketplace' ];
		if ( $this->can_user_manage() ) {
			if ( current_user_can( 'install_plugins' ) ) {
				$valid_tabs[] = 'install';
			}
			$valid_tabs[] = 'subscriptions';
		}
		return $valid_tabs;
	}

	private function can_user_manage() {
		// only someone who can activate plugins is allowed to manage subscriptions
		if ( $this->is_multisite() ) {
			return is_super_admin();
		}

		return current_user_can( Capabilities::KEY_SUPERUSER );
	}

	private function is_multisite() {
		return function_exists( 'is_multisite' ) && is_multisite();
	}
}
