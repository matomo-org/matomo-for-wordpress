<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\Plugin\Manager;
use WpMatomo\Access;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class AdminSettings implements MatomoPageContent {
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
		return add_query_arg( [ 'tab' => $tab ], Menu::make_page_url( Menu::SLUG_SETTINGS ) );
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

		$matomo_is_super_user = current_user_can( Capabilities::KEY_SUPERUSER );

		$is_blog_specific_screen_and_network_activated = $this->settings->is_network_enabled() && ! is_network_admin();
		$built_in_tabs_for_blog_specific               = [ self::TAB_EXCLUSIONS, self::TAB_PRIVACY ];

		$active_tab = self::TAB_TRACKING;

		$plugin_settings_tabs = $this->get_plugin_settings_tabs();
		$plugin_settings_tabs = array_map(
			function ( $info ) {
				return new PluginMeasurableSettings( $info['plugin_name'], $info['plugin_display_name'] );
			},
			$plugin_settings_tabs
		);
		$setting_tabs         = array_merge( $setting_tabs, $plugin_settings_tabs );

		$setting_tabs = apply_filters( 'matomo_setting_tabs', $setting_tabs, $this->settings );

		// set which tabs the current user is entitled to on this screen
		if ( ! $matomo_is_super_user || $is_blog_specific_screen_and_network_activated ) {
			// tabs for matomo admin or superuser on a blog specific screen
			$setting_tabs = $this->keep_only_tabs(
				$setting_tabs,
				array_merge( $built_in_tabs_for_blog_specific, $this->find_plugin_measurable_settings_tabs( $setting_tabs ) )
			);
			$active_tab   = self::TAB_EXCLUSIONS;
		}
		// a WP super user/network admin or a Matomo super user on a single site blog. they
		// are entitled to see every tab.

		$setting_tabs = $this->remove_tabs_the_user_cannot_manage( $setting_tabs );

		if ( ! empty( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
			if ( isset( $setting_tabs[ $tab ] ) ) {
				$active_tab = $tab;
			}
		}

		if ( ! isset( $setting_tabs[ $active_tab ] ) ) {
			// the tab we would show by default is not on the page, eg because a plugin removed it
			$active_tab = empty( $setting_tabs ) ? '' : key( $setting_tabs );
		}

		// null when a plugin filtered every tab away, in which case the page shows its tab bar and
		// nothing below it rather than fataling
		$content_tab     = isset( $setting_tabs[ $active_tab ] ) ? $setting_tabs[ $active_tab ] : null;
		$matomo_settings = $this->settings;

		include __DIR__ . '/views/settings.php';
	}

	/**
	 * @param AdminSettingsInterface[] $setting_tabs
	 * @param string[]                 $tab_ids
	 * @return AdminSettingsInterface[]
	 */
	private function keep_only_tabs( $setting_tabs, $tab_ids ) {
		return array_intersect_key( $setting_tabs, array_flip( $tab_ids ) );
	}

	/**
	 * @param AdminSettingsInterface[] $setting_tabs
	 * @return string[]
	 */
	private function find_plugin_measurable_settings_tabs( $setting_tabs ) {
		$tab_ids = [];
		foreach ( $setting_tabs as $tab_id => $tab ) {
			if ( $tab instanceof PluginMeasurableSettings ) {
				$tab_ids[] = $tab_id;
			}
		}
		return $tab_ids;
	}

	/**
	 * @param AdminSettingsInterface[] $setting_tabs
	 * @return AdminSettingsInterface[]
	 */
	private function remove_tabs_the_user_cannot_manage( $setting_tabs ) {
		foreach ( $setting_tabs as $tab_id => $tab ) {
			// is_callable() rather than method_exists(), since method_exists() also finds
			// methods that cannot be called (eg, private methods)
			if ( is_object( $tab )
				&& is_callable( [ $tab, 'can_user_manage' ] )
				&& ! $tab->can_user_manage()
			) {
				unset( $setting_tabs[ $tab_id ] );
			}
		}

		return $setting_tabs;
	}

	private function get_plugin_settings_tabs() {
		$active_wordpress_plugins = (array) get_option( 'active_plugins', [] );

		$cache_key = 'plugin-settings-tabs-' . md5( implode( ',', $active_wordpress_plugins ) );

		if ( $this->settings->is_network_enabled() ) {
			$network_plugins = (array) get_site_option( 'active_sitewide_plugins', [] );
			$cache_key       = $cache_key . '-' . md5( implode( ',', $network_plugins ) );
		}

		$tabs = get_transient( $cache_key );
		if ( false === $tabs || ! is_array( $tabs ) || empty( $active_wordpress_plugins ) ) {
			$all_wordpress_plugins = $this->get_wordpress_plugins();

			Bootstrap::do_bootstrap();
			$all_matomo_plugins = Manager::getInstance()->getActivatedPlugins();

			$marketplace_plugins = array_intersect( array_keys( $all_wordpress_plugins ), $all_matomo_plugins );

			$tabs = [];
			foreach ( $marketplace_plugins as $plugin_name ) {
				$settings_class = 'Piwik\\Plugins\\' . $plugin_name . '\\MeasurableSettings';
				if ( ! class_exists( $settings_class ) ) {
					continue;
				}

				$plugin_display_name = $all_wordpress_plugins[ $plugin_name ]['Name'];
				$plugin_display_name = preg_replace( '/\s+\(Matomo Plugin\)\s*/', '', $plugin_display_name );

				$tabs[ "plugin-{$plugin_name}" ] = [
					'plugin_name'         => $plugin_name,
					'plugin_display_name' => $plugin_display_name,
				];
			}

			set_transient( $cache_key, $tabs, 60 * 60 * 24 * 7 );
		}

		return $tabs;
	}

	private function get_wordpress_plugins() {
		$all_wordpress_plugins = array_merge( get_plugins(), get_mu_plugins() );
		$all_wordpress_plugins = array_combine(
			array_map(
				function ( $path ) {
					return basename( dirname( $path ) );
				},
				array_keys( $all_wordpress_plugins )
			),
			$all_wordpress_plugins
		);
		return $all_wordpress_plugins;
	}

	public function get_title() {
		return __( 'Settings', 'matomo' );
	}
}
