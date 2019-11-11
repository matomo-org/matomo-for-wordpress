<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use Piwik\Plugins\UsersManager\UserPreferences;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Marketplace\Api as MarketplaceApi;
use WpMatomo\Report\Dates;
use WpMatomo\Settings;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class SafeModeMenu {
	/**
	 * @var Settings
	 */
	private $settings;

	private $parentSlug = 'matomo';

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'network_admin_menu', array( $this, 'add_menu' ) );
	}

	public function add_menu() {
		if (!\WpMatomo::is_admin_user()) {
			return;
		}

		$system_report = new SystemReport( $this->settings );

		add_menu_page( 'Matomo Analytics', 'Matomo Analytics', Menu::CAP_NOT_EXISTS, 'matomo', null, 'dashicons-analytics' );

		add_submenu_page( $this->parentSlug, __( 'System Report', 'matomo' ), __( 'System Report', 'matomo' ), 'administrator', Menu::SLUG_SYSTEM_REPORT, array(
			$system_report,
			'show'
		) );

	}

}
