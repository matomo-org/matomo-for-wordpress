<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use WpMatomo\Access;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class AdminSettings {
	const TAB_TRACKING = 'tracking';
	const TAB_ACCESS = 'access';
	const TAB_EXCLUSIONS = 'exlusions';
	const TAB_PRIVACY = 'privacy';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public static function make_url( $tab ) {
		return add_query_arg( array( 'tab' => $tab ), menu_page_url( Menu::SLUG_SETTINGS, false ) );
	}

	public function show() {

		$access          = new Access( $this->settings );
		$access_settings = new AccessSettings( $access, $this->settings );
		$tracking        = new TrackingSettings( $this->settings );
		$exclusions      = new ExclusionSettings( $this->settings );
		$privacy         = new PrivacySettings();
		$setting_tabs    = array(
			self::TAB_TRACKING   => $tracking,
			self::TAB_ACCESS     => $access_settings,
			self::TAB_PRIVACY    => $privacy,
			self::TAB_EXCLUSIONS => $exclusions,
		);

		$setting_tabs = apply_filters( 'matomo_setting_tabs', $setting_tabs, $this->settings );

		if ( ! empty( $_GET['tab'] ) && isset( $setting_tabs[ $_GET['tab'] ] ) ) {
			$active_tab = $_GET['tab'];
		} else {
			$active_tab = self::TAB_TRACKING;
		}

		$content_tab = $setting_tabs[ $active_tab ];

		include( dirname( __FILE__ ) . '/views/settings.php' );
	}

}
