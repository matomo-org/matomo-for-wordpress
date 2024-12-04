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
use WpMatomo\Report\Dates;
use WpMatomo\Settings;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Menu {
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
	 * @var Settings
	 */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		// Hook for adding admin menus
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'network_admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_head', [ $this, 'menu_external_icons' ] );

		// as we are redirecting we need to perform the redirect as soon as possible before WP has eg echoed the header
		add_action( 'load-matomo-analytics_page_' . self::SLUG_REPORTING, [ $this, 'reporting' ] );
		add_action( 'load-' . self::$parent_slug . '_page_' . self::SLUG_REPORTING, [ $this, 'reporting' ] );
		add_action( 'load-matomo-analytics_page_' . self::SLUG_TAGMANAGER, [ $this, 'tagmanager' ] );
		add_action( 'load-' . self::$parent_slug . '_page_' . self::SLUG_TAGMANAGER, [ $this, 'tagmanager' ] );
	}

	public function add_menu() {
		$info          = new Info();
		$get_started   = new GetStarted( $this->settings );
		$marketplace   = new Marketplace( $this->settings );
		$system_report = new SystemReport( $this->settings );
		$summary       = new Summary( $this->settings );
		$import_wp_s   = new ImportWpStatistics();

		$admin_settings = new AdminSettings( $this->settings );

		add_menu_page(
			'Matomo Analytics',
			'Matomo Analytics',
			self::CAP_NOT_EXISTS,
			'matomo',
			null,
			'dashicons-analytics'
		);

		$submenu_pages = [];

		if ( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) && $get_started->can_user_manage() ) {
			if ( ! is_multisite() || ! is_network_admin() ) {
				$submenu_pages[] = [
					'page_title' => __( 'Get Started', 'matomo' ),
					'menu_title' => __( 'Get Started', 'matomo' ),
					'capability' => Capabilities::KEY_SUPERUSER,
					'menu_slug'  => self::SLUG_GET_STARTED,
					'callback'   => [
						$get_started,
						'show',
					],
				];
			}
		}

		if ( is_network_admin() ) {
			$submenu_pages[] = [
				'page_title' => __( 'Multi Site', 'matomo' ),
				'menu_title' => __( 'Multi Site', 'matomo' ),
				'capability' => Capabilities::KEY_SUPERUSER,
				'menu_slug'  => 'matomo-multisite',
				'callback'   => [
					$info,
					'show_multisite',
				],
			];
		} else {
			$submenu_pages[] = [
				'page_title' => __( 'Summary', 'matomo' ),
				'menu_title' => __( 'Summary', 'matomo' ),
				'capability' => Capabilities::KEY_VIEW,
				'menu_slug'  => self::SLUG_REPORT_SUMMARY,
				'callback'   => [
					$summary,
					'show',
				],
			];

			// the network itself is not a blog
			$submenu_pages[] = [
				'page_title' => __( 'Reporting', 'matomo' ),
				'menu_title' => __( 'Reporting', 'matomo' ),
				'capability' => Capabilities::KEY_VIEW,
				'menu_slug'  => self::SLUG_REPORTING,
				'callback'   => [
					$this,
					'reporting',
				],
			];
			// the network itself is not a blog
			if ( matomo_has_tag_manager() ) {
				$submenu_pages[] = [
					'page_title' => __( 'Tag Manager', 'matomo' ),
					'menu_title' => __( 'Tag Manager', 'matomo' ),
					'capability' => Capabilities::KEY_WRITE,
					'menu_slug'  => self::SLUG_TAGMANAGER,
					'callback'   => [
						$this,
						'tagmanager',
					],
				];
			}
		}

		// we always show settings except when multi site is used, plugin is not network enabled, and we are in network admin
		$can_matomo_be_managed = ( ! is_multisite() || $this->settings->is_network_enabled() || ! is_network_admin() );

		if ( $can_matomo_be_managed ) {
			$submenu_pages[] = [
				'page_title' => __( 'Settings', 'matomo' ),
				'menu_title' => __( 'Settings', 'matomo' ),
				'capability' => Capabilities::KEY_SUPERUSER,
				'menu_slug'  => self::SLUG_SETTINGS,
				'callback'   => [
					$admin_settings,
					'show',
				],
			];
		}

		if ( ! is_plugin_active( MATOMO_MARKETPLACE_PLUGIN_NAME ) ) {
			$submenu_pages[] = [
				'page_title' => __( 'Marketplace', 'matomo' ),
				'menu_title' => __( 'Marketplace', 'matomo' ),
				'capability' => Capabilities::KEY_VIEW,
				'menu_slug'  => self::SLUG_MARKETPLACE,
				'callback'   => [
					$marketplace,
					'show',
				],
			];
		}

		if ( $this->settings->is_network_enabled() || ! is_network_admin() ) {
			$warning = '';
			if ( Admin::is_matomo_admin() ) {
				$system_report = new \WpMatomo\Admin\SystemReport( $this->settings );
				if ( ! get_user_meta( get_current_user_id(), \WpMatomo\ErrorNotice::OPTION_NAME_SYSTEM_REPORT_ERRORS_DISMISSED ) && $system_report->errors_present() ) {
					$warning = '<span class="awaiting-mod">!</span>';
				}
			}

			$submenu_pages[] = [
				'page_title' => __( 'Diagnostics', 'matomo' ),
				'menu_title' => __( 'Diagnostics', 'matomo' ) . $warning,
				'capability' => Capabilities::KEY_SUPERUSER,
				'menu_slug'  => self::SLUG_SYSTEM_REPORT,
				'callback'   => [
					$system_report,
					'show',
				],
			];
		}

		if ( is_plugin_active( 'wp-statistics/wp-statistics.php' ) ) {
			$submenu_pages[] = [
				'page_title' => __( 'Import WP Statistics', 'matomo' ),
				'menu_title' => __( 'Import WP Statistics', 'matomo' ),
				'capability' => Capabilities::KEY_SUPERUSER,
				'menu_slug'  => self::SLUG_IMPORTWPS,
				'callback'   => [
					$import_wp_s,
					'show',
				],
			];
		}
		$submenu_pages[] = [
			'page_title' => __( 'Help', 'matomo' ),
			'menu_title' => __( 'Help', 'matomo' ),
			'capability' => Capabilities::KEY_VIEW,
			'menu_slug'  => self::SLUG_ABOUT,
			'callback'   => [
				$info,
				'show',
			],
		];

		$submenu_pages = apply_filters( 'matomo_submenu', $submenu_pages );
		foreach ( $submenu_pages as $page ) {
			add_submenu_page(
				self::$parent_slug,
				$page['page_title'],
				$page['menu_title'],
				$page['capability'],
				$page['menu_slug'],
				$page['callback']
			);
		}
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
}
