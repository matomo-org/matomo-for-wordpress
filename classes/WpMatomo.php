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

use WpMatomo\Admin\AdBlockDetector;
use WpMatomo\Admin\Admin;
use WpMatomo\Admin\Chart;
use WpMatomo\Admin\Dashboard;
use WpMatomo\Admin\MarketplaceSetupWizard;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\PluginSuggestions\PluginSuggestions;
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
use WpMatomo\PluginAdminOverrides;
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

	const VERSION = '5.8.2';

	/**
	 * @var \WpMatomo\Feature[]
	 */
	private static $features = [];


	/**
	 * @var Settings
	 */
	public static $settings;

	public function __construct() {
		$this->declare_woocommerce_hpos_compatible();

		if ( ! $this->check_compatibility() ) {
			return;
		}

		self::$settings = new Settings();

		$this->init_features();

		if ( self::is_safe_mode() ) {
			return;
		}

		$adblock_detector = new AdBlockDetector();
		$adblock_detector->register_hooks();

		add_action(
			'init',
			function () {
				$whats_new_notifications = new \WpMatomo\Admin\WhatsNewNotifications( self::$settings );
				if ( $whats_new_notifications->is_active() ) {
					$whats_new_notifications->register_hooks();
				}
				$whats_new_notifications->register_ajax();
			}
		);

		$ai_bot_tracking = new \WpMatomo\AIBotTracking( self::$settings );
		$ai_bot_tracking->register_hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			new MatomoCommands();
		}

		// TODO: need better way of doing ajax?
		WpMatomo\Admin\TrackingSettings::register_ajax();
		\WpMatomo\Admin\GetStarted::register_hooks();
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

	private function declare_woocommerce_hpos_compatible() {
		add_action(
			'before_woocommerce_init',
			function() {
				if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'matomo/matomo.php', true );
				}
			}
		);
	}

	private function init_features() {
		$features = $this->get_all_features();

		self::$features = [];
		foreach ( $features as $feature ) {
			if ( $feature->is_active() ) {
				self::$features[ get_class( $feature ) ] = $feature;

				$feature->register_hooks();
			}

			// ajax methods must be present even if other hooks should not be added,
			// since ajax requests go through admin-ajax.php
			$feature->register_ajax();
		}
	}

	private function get_all_features() {
		if ( self::is_safe_mode() ) {
			if ( is_admin() ) {
				return [
					new Admin( self::$settings, false ),
					new \WpMatomo\Admin\SafeModeMenu( self::$settings ),
				];
			}

			return [];
		}

		$site_config = new SiteSync\SyncConfig( self::$settings );

		return [
			new \WpMatomo\PluginInit( self::$settings ),
			new Capabilities( self::$settings ),
			new Roles( self::$settings ),
			new \WpMatomo\Compatibility(),
			new ScheduledTasks( self::$settings, $site_config ),
			new OptOut(),
			new Renderer(),
			new API(),
			new Admin( self::$settings ),
			new Dashboard(),
			new SiteSync( self::$settings ),
			new UserSync(),
			new \WpMatomo\Referral(),
			new \WpMatomo\ErrorNotice( self::$settings ),
			new Chart(),

			/*
			 * @see https://github.com/matomo-org/matomo-for-wordpress/issues/434
			 */
			new RedirectOnActivation(),

			new PluginAdminOverrides( self::$settings ),

			new TrackingCode( self::$settings ),
			new Annotations( self::$settings ),

			new \WpMatomo\PluginActionLinks( self::$settings ),

			new MarketplaceSetupWizard(),

			new PluginSuggestions(),
		];
	}

	public static function is_async_archiving_manually_disabled() {
		return ( defined( 'MATOMO_SUPPORT_ASYNC_ARCHIVING' ) && ! MATOMO_SUPPORT_ASYNC_ARCHIVING )
			|| self::is_async_archiving_disabled_by_setting();
	}

	private static function is_async_archiving_disabled_by_setting() {
		return self::$settings->is_async_archiving_disabled_by_option();
	}

	/**
	 * @param string $class_name
	 * @return \WpMatomo\Feature|null
	 */
	public static function get_active_feature( $class_name ) {
		if ( empty( self::$features[ $class_name ] ) ) {
			return null;
		}
		return self::$features[ $class_name ];
	}
}
