<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

use WpMatomo\Admin\Menu;
use WpMatomo\Commands\UninstallCommand;
use WpMatomo\Ecommerce\EasyDigitalDownloads;
use WpMatomo\Ecommerce\MemberPress;
use WpMatomo\OptOut;
use WpMatomo\Paths;
use WpMatomo\PrivacyBadge;
use WpMatomo\ScheduledTasks;
use \WpMatomo\Site\Sync as SiteSync;
use WpMatomo\AjaxTracker;
use \WpMatomo\User\Sync as UserSync;
use \WpMatomo\Installer;
use \WpMatomo\Updater;
use \WpMatomo\Roles;
use \WpMatomo\Annotations;
use \WpMatomo\TrackingCode;
use \WpMatomo\Settings;
use \WpMatomo\Capabilities;
use \WpMatomo\Ecommerce\Woocommerce;
use \WpMatomo\Report\Renderer;
use WpMatomo\API;
use \WpMatomo\Admin\Admin;

class WpMatomo {

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct() {
		if ( ! $this->check_compatibility() ) {
			return;
		}

		$this->settings = new Settings();

		if ( self::is_safe_mode() ) {
			if ( is_admin() ) {
				new \WpMatomo\Admin\SafeModeMenu($this->settings);
			}

			return;
		}

		add_action( 'init', array( $this, 'init_plugin' ) );

		$capabilities = new Capabilities( $this->settings );
		$capabilities->register_hooks();

		$roles = new Roles( $this->settings );
		$roles->register_hooks();

		$scheduled_tasks = new ScheduledTasks( $this->settings );
		$scheduled_tasks->schedule();

		$privacy_badge = new PrivacyBadge();
		$privacy_badge->register_hooks();

		$privacy_badge = new OptOut();
		$privacy_badge->register_hooks();

		$renderer = new Renderer();
		$renderer->register_hooks();

		$api = new API();
		$api->register_hooks();

		if ( is_admin() ) {
			new Admin( $this->settings );

			$site_sync = new SiteSync( $this->settings );
			$site_sync->register_hooks();
			$user_sync = new UserSync();
			$user_sync->register_hooks();
		}

		$tracking_code = new TrackingCode( $this->settings );
		$tracking_code->register_hooks();
		$annotations = new Annotations( $this->settings );
		$annotations->register_hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			new UninstallCommand();
		}

		add_filter( 'plugin_action_links_' . plugin_basename( MATOMO_ANALYTICS_FILE ), array(
			$this,
			'add_settings_link'
		) );
	}

	private function check_compatibility() {
		if ( ! is_admin() ) {
			return true;
		}
		if ( is_matomo_app_request() ) {
			return true;
		}

		$paths       = new Paths();
		$upload_path = $paths->get_upload_base_dir();

		if ( $upload_path
		     && ! is_writable( dirname( $upload_path ) ) ) {
			add_action( 'init', function () {
				if ( self::is_admin_user() ) {
					add_action( 'admin_notices', function () {
						echo '<div class="error"><p>' . __( 'Matomo Analytics requires the uploads directory to be writable. Please make the directory writable for it to work.', 'matomo' ) . '</p></div>';
					} );
				}
			} );

			return false;
		}

		if ( ! has_matomo_compatible_content_dir() ) {
			add_action( 'init', function () {
				if ( self::is_admin_user() ) {
					add_action( 'admin_notices', function () {
						echo '<div class="error"><p>' . __( 'It looks like you are maybe using a custom WordPress content directory. The Matomo Analytics plugin likely won\'t fully work.', 'matomo' ) . '</p></div>';
					} );
				}
			} );
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

	public static function is_safe_mode()
	{
		return defined('MATOMO_SAFE_MODE') && MATOMO_SAFE_MODE;
	}

	public function add_settings_link( $links ) {
		$get_started = new \WpMatomo\Admin\GetStarted( $this->settings );

		if ( $this->settings->get_global_option( Settings::SHOW_GET_STARTED_PAGE ) && $get_started->can_user_manage() ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_GET_STARTED, false ) . '">' . __( 'Get Started' ) . '</a>';
		} elseif ( current_user_can( Capabilities::KEY_SUPERUSER ) ) {
			$links[] = '<a href="' . menu_page_url( Menu::SLUG_SETTINGS, false ) . '">' . __( 'Settings' ) . '</a>';
		}


		return $links;
	}

	public function init_plugin() {
		if ( (is_admin() || is_matomo_app_request())
		     && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$installer = new Installer( $this->settings );
			$installer->register_hooks();
			if ( $installer->looks_like_it_is_installed() ) {
				if ( is_admin() ) {
					$updater = new Updater( $this->settings );
					$updater->update_if_needed();
				}
			} else {
				if (is_matomo_app_request()) {
					// we can't install if matomo is requested... there's some circular reference
					wp_redirect(admin_url());
					exit;
				} else {
					$installer->install();
				}
			}
		}
		$tracking_code = new TrackingCode( $this->settings );
		if ( $this->settings->is_tracking_enabled()
		     && $this->settings->get_global_option('track_ecommerce')
		     && ! $tracking_code->is_hidden_user() ) {
			$tracker = new AjaxTracker( $this->settings );

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
