<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WpMatomo\Ecommerce\EasyDigitalDownloads;
use WpMatomo\Ecommerce\MemberPress;
use WpMatomo\Ecommerce\Woocommerce;
use WpMatomo\Site\Sync\SyncConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class PluginInit extends Feature {

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_action( 'init', [ $this, 'init_plugin' ] );
	}

	public function init_plugin() {
		if ( ( is_admin() || matomo_is_app_request() ) && ! wp_doing_ajax() ) {
			$installer = new Installer( $this->settings );
			$installer->register_hooks();
			if ( $installer->looks_like_it_is_installed() ) {
				if ( is_admin() && ( ! defined( 'MATOMO_ENABLE_AUTO_UPGRADE' ) || MATOMO_ENABLE_AUTO_UPGRADE ) ) {
					$updater = new Updater( $this->settings );
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

		// TODO: can this be moved out of here? try in a later release
		$tracking_code = new TrackingCode( $this->settings );
		if ( $this->settings->is_tracking_enabled()
			&& $this->settings->get_global_option( 'track_ecommerce' )
			&& ! $tracking_code->is_hidden_user()
		) {
			$tracker = new AjaxTracker( $this->settings );

			$sync_config = new SyncConfig( $this->settings );

			if ( function_exists( 'WC' ) ) {
				$woocommerce = new Woocommerce( $tracker, $this->settings, $sync_config );
				$woocommerce->register_hooks();
			}

			$easy_digital_downloads = new EasyDigitalDownloads( $tracker, $this->settings, $sync_config );
			$easy_digital_downloads->register_hooks();

			$member_press = new MemberPress( $tracker, $this->settings, $sync_config );
			$member_press->register_hooks();

			do_action( 'matomo_ecommerce_init', $tracker );
		}
	}
}
