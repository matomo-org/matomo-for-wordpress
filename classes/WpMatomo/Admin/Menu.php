<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\Plugins\UsersManager\UserPreferences;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Feature;
use WpMatomo\Report\Dates;
use WpMatomo\Settings;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Menu extends Feature {
	/**
	 * @var Settings
	 */
	private $settings;

	public static $parent_slug = 'matomo';

	const REPORTING_GOTO_ADMIN          = 'matomo-admin';
	const REPORTING_GOTO_GDPR_TOOLS     = 'matomo-gdpr-tools';
	const REPORTING_GOTO_GDPR_OVERVIEW  = 'matomo-gdpr-overview';
	const REPORTING_GOTO_ASK_CONSENT    = 'matomo-gdpr-consent';
	const REPORTING_GOTO_OPTOUT         = 'matomo-privacy-optout';
	const REPORTING_GOTO_ANONYMIZE_DATA = 'matomo-anonymize-date';
	const REPORTING_GOTO_DATA_RETENTION = 'matomo-data-retention';
	const SLUG_SYSTEM_REPORT            = 'matomo-systemreport';
	const SLUG_REPORT_SUMMARY           = 'matomo-summary';
	const SLUG_TAGMANAGER               = 'matomo-tagmanager';
	const SLUG_REPORTING                = 'matomo-reporting';
	const SLUG_SETTINGS                 = 'matomo-settings';
	const SLUG_GET_STARTED              = 'matomo-get-started';
	const SLUG_ABOUT                    = 'matomo-about';
	const SLUG_MARKETPLACE              = 'matomo-marketplace';
	const SLUG_IMPORTWPS                = 'matomo-importwps';

	const CAP_NOT_EXISTS = 'unknownfoobar';

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		parent::register_hooks();

		// Hook for adding admin menus
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'network_admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_head', [ $this, 'menu_external_icons' ] );
		add_action( 'admin_head', [ $this, 'hide_non_matomo_notifications' ], 99999 );

		// as we are redirecting we need to perform the redirect as soon as possible before WP has eg echoed the header
		add_action( 'load-matomo-analytics_page_' . self::SLUG_REPORTING, [ $this, 'reporting' ] );
		add_action( 'load-' . self::$parent_slug . '_page_' . self::SLUG_REPORTING, [ $this, 'reporting' ] );
		add_action( 'load-matomo-analytics_page_' . self::SLUG_TAGMANAGER, [ $this, 'tagmanager' ] );
		add_action( 'load-' . self::$parent_slug . '_page_' . self::SLUG_TAGMANAGER, [ $this, 'tagmanager' ] );

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		if (
			! empty( $_REQUEST['page'] )
			&& self::SLUG_MARKETPLACE === $_REQUEST['page']
		) {
			wp_enqueue_style( 'matomo_marketplace_css', plugins_url( 'assets/css/marketplace-style.css', MATOMO_ANALYTICS_FILE ), false, matomo_get_asset_version() );
		}
	}

	public function hide_non_matomo_notifications() {
		// only hide for matomo- pages
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$page = isset( $_REQUEST['page'] ) ? wp_unslash( $_REQUEST['page'] ) : '';
		if ( strpos( $page, 'matomo-' ) !== 0 ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo <<<EOF
<style>
.matomo-notice {
    display: block;
}

.notice:not(.matomo-notice) {
    display: none;
}
</style>
EOF;
	}

	public function add_menu() {
		do_action( 'matomo_before_add_menu' );

		$info           = new MatomoPage( new Info() );
		$get_started    = new MatomoPage( new GetStarted( $this->settings ) );
		$marketplace    = new MatomoPage( new Marketplace( $this->settings ) );
		$summary        = new MatomoPage( new Summary( $this->settings ) );
		$import_wp_s    = new MatomoPage( new ImportWpStatistics() );
		$admin_settings = new MatomoPage( new AdminSettings( $this->settings ) );

		$matomo_logo_url = $this->get_light_grey_brand_icon();

		add_menu_page( 'Matomo Analytics', 'Matomo Analytics', self::CAP_NOT_EXISTS, 'matomo', null, $matomo_logo_url, 2 );

		if ( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) && $get_started->get_content()->can_user_manage() ) {
			if ( ! is_multisite() || ! is_network_admin() ) {
				add_submenu_page(
					self::$parent_slug,
					__( 'Get Started', 'matomo' ),
					__( 'Get Started', 'matomo' ),
					Capabilities::KEY_SUPERUSER,
					self::SLUG_GET_STARTED,
					[
						$get_started,
						'show',
					]
				);
			}
		}

		if ( is_network_admin() ) {
			$info_multisite = new MatomoPage( new Info( true ), 'show_multisite' );

			add_submenu_page(
				self::$parent_slug,
				__( 'Multi Site', 'matomo' ),
				__( 'Multi Site', 'matomo' ),
				Capabilities::KEY_SUPERUSER,
				'matomo-multisite',
				[
					$info_multisite,
					'show',
				]
			);
		} else {
			add_submenu_page(
				self::$parent_slug,
				__( 'Summary', 'matomo' ),
				__( 'Summary', 'matomo' ),
				Capabilities::KEY_VIEW,
				self::SLUG_REPORT_SUMMARY,
				[
					$summary,
					'show',
				]
			);

			// the network itself is not a blog
			add_submenu_page(
				self::$parent_slug,
				__( 'Reporting', 'matomo' ),
				__( 'Reporting', 'matomo' ),
				Capabilities::KEY_VIEW,
				self::SLUG_REPORTING,
				[
					$this,
					'reporting',
				]
			);
			// the network itself is not a blog
			if ( matomo_has_tag_manager() ) {
				add_submenu_page(
					self::$parent_slug,
					__( 'Tag Manager', 'matomo' ),
					__( 'Tag Manager', 'matomo' ),
					Capabilities::KEY_WRITE,
					self::SLUG_TAGMANAGER,
					[
						$this,
						'tagmanager',
					]
				);
			}
		}

		// we always show settings except when multi site is used, plugin is not network enabled, and we are in network admin
		$can_matomo_be_managed = ( ! is_multisite() || $this->settings->is_network_enabled() || ! is_network_admin() );

		if ( $can_matomo_be_managed ) {
			add_submenu_page(
				self::$parent_slug,
				__( 'Settings', 'matomo' ),
				__( 'Settings', 'matomo' ),
				Capabilities::KEY_SUPERUSER,
				self::SLUG_SETTINGS,
				[
					$admin_settings,
					'show',
				]
			);
		}

		if ( ! is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) {
			add_submenu_page(
				self::$parent_slug,
				__( 'Marketplace', 'matomo' ),
				__( 'Marketplace', 'matomo' ),
				Capabilities::KEY_VIEW,
				self::SLUG_MARKETPLACE,
				[
					$marketplace,
					'show',
				]
			);
		}

		if ( $this->settings->is_network_enabled() || ! is_network_admin() ) {
			$system_report = new MatomoPage( new SystemReport( $this->settings ) );

			$warning = '';
			if ( Admin::is_matomo_admin() ) {
				if ( ! get_user_meta( get_current_user_id(), \WpMatomo\ErrorNotice::OPTION_NAME_SYSTEM_REPORT_ERRORS_DISMISSED ) && $system_report->get_content()->errors_present() ) {
					$warning = '<span class="awaiting-mod">!</span>';
				}
			}

			add_submenu_page(
				self::$parent_slug,
				__( 'Diagnostics', 'matomo' ),
				__( 'Diagnostics', 'matomo' ) . $warning,
				Capabilities::KEY_SUPERUSER,
				self::SLUG_SYSTEM_REPORT,
				[
					$system_report,
					'show',
				]
			);
		}

		if ( is_plugin_active( 'wp-statistics/wp-statistics.php' ) ) {
			add_submenu_page(
				self::$parent_slug,
				__( 'Import WP Statistics', 'matomo' ),
				__( 'Import WP Statistics', 'matomo' ),
				Capabilities::KEY_SUPERUSER,
				self::SLUG_IMPORTWPS,
				[
					$import_wp_s,
					'show',
				]
			);
		}
		add_submenu_page(
			self::$parent_slug,
			__( 'Help', 'matomo' ),
			__( 'Help', 'matomo' ),
			Capabilities::KEY_VIEW,
			self::SLUG_ABOUT,
			[
				$info,
				'show',
			]
		);
	}

	public function menu_external_icons() {
		global $submenu;

		if ( isset( $submenu[ self::$parent_slug ] ) ) {
			$reporting  = __( 'Reporting', 'matomo' );
			$tagmanager = __( 'Tag Manager', 'matomo' );
			foreach ( $submenu[ self::$parent_slug ] as $key => $menu_item ) {
				if ( 0 === strpos( $menu_item[0], $reporting ) || 0 === strpos( $menu_item[0], $tagmanager ) ) {
					// No other choice
					// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					$submenu[ self::$parent_slug ][ $key ][0] .= ' <span class="dashicons-before dashicons-external"></span>';
				}
			}
		}
	}

	public static function get_matomo_goto_url( $goto ) {
		return add_query_arg( [ 'goto' => $goto ], menu_page_url( self::SLUG_REPORTING, false ) );
	}

	public static function get_reporting_url() {
		return plugins_url( 'app', MATOMO_ANALYTICS_FILE ) . '/index.php';
	}

	public function tagmanager() {
		if ( matomo_has_tag_manager() ) {
			$this->go_to_matomo_page( 'TagManager', 'manageContainers', Capabilities::KEY_WRITE );
		}
		exit;
	}

	public function reporting() {
		if ( ! empty( $_GET['goto'] ) ) {
			switch ( sanitize_text_field( wp_unslash( $_GET['goto'] ) ) ) {
				case self::REPORTING_GOTO_ADMIN:
					$this->go_to_matomo_page( 'CoreAdminHome', 'home', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_GDPR_TOOLS:
					$this->go_to_matomo_page( 'PrivacyManager', 'gdprTools', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_GDPR_OVERVIEW:
					$this->go_to_matomo_page( 'PrivacyManager', 'gdprOverview', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_ASK_CONSENT:
					$this->go_to_matomo_page( 'PrivacyManager', 'consent', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_OPTOUT:
					$this->go_to_matomo_page( 'PrivacyManager', 'usersOptOut', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_ANONYMIZE_DATA:
					$this->go_to_matomo_page( 'PrivacyManager', 'privacySettings', Capabilities::KEY_SUPERUSER );
					break;
				case self::REPORTING_GOTO_DATA_RETENTION:
					$this->go_to_matomo_page( 'CoreAdminHome', 'generalSettings', Capabilities::KEY_SUPERUSER );
					break;
			}
		}

		$url = self::get_reporting_url();

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( $idsite ) {
			$url = add_query_arg( [ 'idSite' => (int) $idsite ], $url );
		}

		if ( ! empty( $_GET['report_date'] ) ) {
			$report_date = sanitize_text_field( wp_unslash( $_GET['report_date'] ) );
			$url         = add_query_arg(
				[
					'module' => 'CoreHome',
					'action' => 'index',
				],
				$url
			);

			$date                  = new Dates();
			list( $period, $date ) = $date->detect_period_and_date( $report_date );
			$url                   = add_query_arg(
				[
					'period' => $period,
					'date'   => $date,
				],
				$url
			);
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @api
	 */
	public static function get_matomo_reporting_url( $category, $subcategory, $params = [] ) {
		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( ! $idsite ) {
			return;
		}

		$idsite                = (int) $idsite;
		$params['category']    = $category;
		$params['subcategory'] = $subcategory;
		$params['idSite']      = $idsite;

		if ( empty( $params['period'] ) ) {
			$params['period'] = 'day';
		}
		if ( empty( $params['date'] ) ) {
			$params['date'] = 'today';
		}

		$url  = self::make_matomo_app_base_url();
		$url .= '?module=CoreHome&action=index&idSite=' . (int) $idsite . '&period=' . rawurlencode( $params['period'] ) . '&date=' . rawurlencode( $params['date'] ) . '#?&' . http_build_query( $params );

		return $url;
	}

	private static function make_matomo_app_base_url() {
		$url = plugins_url( 'app', MATOMO_ANALYTICS_FILE );

		return $url . '/index.php';
	}

	/**
	 * @api
	 */
	public static function get_matomo_action_url( $module, $action, $params = [] ) {
		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		if ( ! $idsite ) {
			return;
		}

		$idsite           = (int) $idsite;
		$params['module'] = $module;
		$params['action'] = $action;
		$params['idSite'] = $idsite;

		if ( empty( $params['period'] ) ) {
			$params['period'] = 'day';
		}
		if ( empty( $params['date'] ) ) {
			$params['date'] = 'today';
		}

		$url = self::make_matomo_app_base_url() . '?' . http_build_query( $params );

		return $url;
	}

	public function go_to_matomo_page( $module, $action, $cap ) {
		if ( ! current_user_can( $cap ) ) {
			return;
		}
		Bootstrap::do_bootstrap();

		$user_preferences = new UserPreferences();
		$website_id       = $user_preferences->getDefaultWebsiteId();
		$default_date     = $user_preferences->getDefaultDate();
		$default_period   = $user_preferences->getDefaultPeriod( false );

		$url  = self::make_matomo_app_base_url();
		$url .= '?idSite=' . (int) $website_id . '&period=' . rawurlencode( $default_period ) . '&date=' . rawurlencode( $default_date );
		$url .= '&module=' . rawurlencode( $module ) . '&action=' . rawurlencode( $action );
		wp_safe_redirect( $url );
		exit;
	}

	private function get_light_grey_brand_icon() {
		$svg = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 590 400" height="20" width="20">
  <defs>
    <style>
      .cls-1 {
        fill: #f0f0f1;
      }
    </style>
  </defs>
  <path class="cls-1" d="M528.8,224.35l-76.26-132.09c-33.59-62.63-132.66-51.05-151.87,16.66-52.88-119.94-218.21-34.64-148.86,75.97-51.11-12.04-102.27,28.82-101.6,81.44-.95,94.29,136.48,114.11,163.06,25.01,32.01,76.13,133.35,78.62,161.24-.43,50.04,114.29,208.02,43.94,154.29-66.56ZM133.66,322.78c-74.19-1.33-74.18-111.59,0-112.9,74.19,1.33,74.18,111.59,0,112.9ZM338.64,229.24c2.93,5.11,5.51,10.43,7.55,15.96,9.37,25.39,7.6,50.58-23.02,69.6-25.97,15.71-62.49,5.93-77.12-20.66,0,0-76.26-132.09-76.26-132.09-7.54-13.06-9.54-28.27-5.64-42.84,12.04-47.99,79.4-56.84,103.42-13.61,0,0,39.99,69.32,40.42,70.12l30.64,53.53ZM323.84,134.03c1.27-74.19,111.63-74.18,112.88,0-1.27,74.19-111.63,74.18-112.88,0ZM511.06,280.69c-7.25,29.47-39.99,48.38-69.14,39.92-14.56-3.9-26.74-13.24-34.28-26.3-3.85-6.59-42.93-74.4-45.46-78.84,41.93,9.96,85.91-16.68,97.71-56.47l45.53,78.86c7.54,13.06,9.54,28.27,5.64,42.84Z"/>
</svg>
EOF;

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$svg = 'data:image/svg+xml;base64,' . base64_encode( $svg );
		return $svg;
	}
}
