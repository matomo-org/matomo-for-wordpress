<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

use WpMatomo\Admin\Admin;
use WpMatomo\Admin\Chart;
use WpMatomo\Admin\Dashboard;
use WpMatomo\Admin\Menu;
use WpMatomo\AjaxTracker;
use WpMatomo\Annotations;
use WpMatomo\API;
use WpMatomo\Capabilities;
use WpMatomo\Commands\MatomoCommands;
use WpMatomo\Ecommerce\EasyDigitalDownloads;
use WpMatomo\Ecommerce\MemberPress;
use WpMatomo\Ecommerce\Woocommerce;
use WpMatomo\Installer;
use WpMatomo\OptOut;
use WpMatomo\Paths;
use WpMatomo\RedirectOnActivation;
use WpMatomo\Report\Renderer;
use WpMatomo\Roles;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\TrackingCode;
use WpMatomo\Updater;
use WpMatomo\User\Sync as UserSync;

class WpMatomo {

	/**
	 * @var Settings
	 */
	public static $settings;

	public function __construct() {
		if ( ! $this->check_compatibility() ) {
			return;
		}

		self::$settings = new Settings();

		if ( self::is_safe_mode() ) {
			if ( is_admin() ) {
				new Admin( self::$settings, false );
				new \WpMatomo\Admin\SafeModeMenu( self::$settings );
			}

			return;
		}

		add_action( 'init', [ $this, 'init_plugin' ] );

		$capabilities = new Capabilities( self::$settings );
		$capabilities->register_hooks();

		$roles = new Roles( self::$settings );
		$roles->register_hooks();

		$compatibility = new \WpMatomo\Compatibility();
		$compatibility->register_hooks();

		$scheduled_tasks = new ScheduledTasks( self::$settings );
		$scheduled_tasks->schedule();

		$privacy_badge = new OptOut();
		$privacy_badge->register_hooks();

		$renderer = new Renderer();
		$renderer->register_hooks();

		$api = new API();
		$api->register_hooks();

		if ( is_admin() ) {
			new Admin( self::$settings );

			$dashboard = new Dashboard();
			$dashboard->register_hooks();

			$site_sync = new SiteSync( self::$settings );
			$site_sync->register_hooks();
			$user_sync = new UserSync();
			$user_sync->register_hooks();

			$referral = new \WpMatomo\Referral();
			if ( $referral->should_show() ) {
				$referral->register_hooks();
			}

			$error_notice = new \WpMatomo\ErrorNotice( self::$settings );
			$error_notice->register_hooks();

			$chart = new Chart();
			$chart->register_hooks();

			/*
			 * @see https://github.com/matomo-org/matomo-for-wordpress/issues/434
			 */
			$redirect = new RedirectOnActivation( $this );
			$redirect->register_hooks();
		}

		$tracking_code = new TrackingCode( self::$settings );
		$tracking_code->register_hooks();
		$annotations = new Annotations( self::$settings );
		$annotations->register_hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			new MatomoCommands();
		}

		add_filter(
			'plugin_action_links_' . plugin_basename( MATOMO_ANALYTICS_FILE ),
			[
				$this,
				'add_settings_link',
			]
		);
	}

	private function check_compatibility() {
		if ( ! is_admin() ) {
			return true;
		}
		if ( matomo_is_app_request() ) {
			return true;
		}

		$paths       = new Paths();
		$upload_path = $paths->get_upload_base_dir();

		if ( $upload_path
			&& ! is_writable( dirname( $upload_path ) ) ) {
			add_action(
				'init',
				function () use ( $upload_path ) {
					if ( self::is_admin_user() ) {
						add_action(
							'admin_notices',
							function () use ( $upload_path ) {
								echo '<div class="error"><p>' . sprintf( esc_html__( 'Matomo Analytics requires the uploads directory %s to be writable. Please make the directory writable for it to work.', 'matomo' ), '(' . esc_html( dirname( $upload_path ) ) . ')' ) . '</p></div>';
							}
						);
					}
				}
			);

			return false;
		}

		return true;
	}

	public static function is_admin_user() {
		if ( ! function_exists( 'is_multisite' )
			|| ! is_multisite() ) {
			return current_user_can( 'administrator' );
		}

		return is_super_admin();
	}

	public static function is_safe_mode() {
		if ( defined( 'MATOMO_SAFE_MODE' ) ) {
			return MATOMO_SAFE_MODE;
		}

		return false;
	}

	private static function get_active_plugins() {
		$plugins = [];
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$muplugins = get_site_option( 'active_sitewide_plugins' );
			$plugins   = array_keys( $muplugins );
		}
		$plugins = array_merge( (array) get_option( 'active_plugins', [] ), $plugins );

		return $plugins;
	}

	public static function should_disable_addhandler() {
		return defined( 'MATOMO_DISABLE_ADDHANDLER' ) && MATOMO_DISABLE_ADDHANDLER;
	}

	public function add_settings_link( $links ) {
		$get_started = new \WpMatomo\Admin\GetStarted( self::$settings );

		if ( self::$settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) && $get_started->can_user_manage() ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_GET_STARTED, false ) . '">' . __( 'Get Started', 'matomo' ) . '</a>';
		} elseif ( current_user_can( Capabilities::KEY_SUPERUSER ) ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_SETTINGS, false ) . '">' . __( 'Settings', 'matomo' ) . '</a>';
		}

		return $links;
	}

	public function init_plugin() {
		if ( ( is_admin() || matomo_is_app_request() ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$installer = new Installer( self::$settings );
			$installer->register_hooks();
			if ( $installer->looks_like_it_is_installed() ) {
				if ( is_admin() && ( ! defined( 'MATOMO_ENABLE_AUTO_UPGRADE' ) || MATOMO_ENABLE_AUTO_UPGRADE ) ) {
					$updater = new Updater( self::$settings );
					$updater->update_if_needed();
				}
			} else {
				if ( matomo_is_app_request() ) {
					// we can't install if matomo is requested... there's some circular reference
					wp_safe_redirect( admin_url() );
					exit;
				} else {
					if ( $installer->can_be_installed() ) {
						$installer->install();
					}
				}
			}
		}
		$tracking_code = new TrackingCode( self::$settings );
		if ( self::$settings->is_tracking_enabled()
			&& self::$settings->get_global_option( 'track_ecommerce' )
			&& ! $tracking_code->is_hidden_user() ) {
			$tracker = new AjaxTracker( self::$settings );

			$woocommerce = new Woocommerce( $tracker );
			$woocommerce->register_hooks();

			$easy_digital_downloads = new EasyDigitalDownloads( $tracker );
			$easy_digital_downloads->register_hooks();

			$member_press = new MemberPress( $tracker );
			$member_press->register_hooks();

			do_action( 'matomo_ecommerce_init', $tracker );
		}
	}
}
