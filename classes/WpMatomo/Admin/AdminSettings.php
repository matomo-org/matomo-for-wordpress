<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use WpMatomo\Access;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class AdminSettings {
	const TAB_TRACKING    = 'tracking';
	const TAB_ACCESS      = 'access';
	const TAB_EXCLUSIONS  = 'exlusions';
	const TAB_PRIVACY     = 'privacy';
	const TAB_GEOLOCATION = 'geolocation';
	const TAB_ADVANCED    = 'advanced';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public static function make_url( $tab ) {
		global $_parent_pages;
		$menu_slug = Menu::SLUG_SETTINGS;

		if ( is_multisite() && is_network_admin() ) {
			if ( isset( $_parent_pages[ $menu_slug ] ) ) {
				$parent_slug = $_parent_pages[ $menu_slug ];
				if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
					$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
				} else {
					$url = network_admin_url( 'admin.php?page=' . $menu_slug );
				}
			} else {
				$url = '';
			}
		} else {
			$url = menu_page_url( $menu_slug, false );
		}

		return add_query_arg( [ 'tab' => $tab ], $url );
	}

	public function show() {
		$access          = new Access( $this->settings );
		$access_settings = new AccessSettings( $access, $this->settings );
		$tracking        = new TrackingSettings( $this->settings );
		$exclusions      = new ExclusionSettings( $this->settings );
		$geolocation     = new GeolocationSettings( $this->settings );
		$privacy         = new PrivacySettings( $this->settings );
		$advanced        = new AdvancedSettings( $this->settings );
		$setting_tabs    = [
			self::TAB_TRACKING    => $tracking,
			self::TAB_ACCESS      => $access_settings,
			self::TAB_PRIVACY     => $privacy,
			self::TAB_EXCLUSIONS  => $exclusions,
			self::TAB_GEOLOCATION => $geolocation,
			self::TAB_ADVANCED    => $advanced,
		];

		$active_tab = self::TAB_TRACKING;

		if ( $this->settings->is_network_enabled() && ! is_network_admin() ) {
			$active_tab   = self::TAB_EXCLUSIONS;
			$setting_tabs = [
				self::TAB_EXCLUSIONS => $exclusions,
				self::TAB_PRIVACY    => $privacy,
			];
		}

		$setting_tabs = apply_filters( 'matomo_setting_tabs', $setting_tabs, $this->settings );

		if ( ! empty( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			if ( isset( $setting_tabs[ $tab ] ) ) {
				$active_tab = $tab;
			}
		}

		$content_tab     = $setting_tabs[ $active_tab ];
		$matomo_settings = $this->settings;

		include dirname( __FILE__ ) . '/views/settings.php';
	}
}
