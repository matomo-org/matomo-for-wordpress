<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use WpMatomo\Capabilities;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class TrackingSettings implements AdminSettingsInterface {
	const FORM_NAME = 'matomo';
	const NONCE_NAME = 'matomo_settings';
	const TRACK_MODE_DEFAULT = 'default';
	const TRACK_MODE_DISABLED = 'disabled';
	const TRACK_MODE_MANUALLY = 'manually';
	const TRACK_MODE_TAGMANAGER = 'tagmanager';

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function get_title() {
		return __( 'Tracking', 'matomo' );
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
		     && ! empty( $_POST[ self::FORM_NAME ] )
		     && is_admin()
		     && check_admin_referer( self::NONCE_NAME )
		     && $this->can_user_manage() ) {
			$this->apply_settings();

			return true;
		}

		return false;
	}

	public function can_user_manage() {
		return current_user_can( Capabilities::KEY_SUPERUSER );
	}

	private function apply_settings() {
		$keys_to_keep = array(
			'track_mode',
			'track_across',
			'track_across_alias',
			'track_crossdomain_linking',
			'track_feed',
			'track_feed_addcampaign',
			'track_feed_campaign',
			'track_heartbeat',
			'track_user_id',
			'track_datacfasync',
			'tagmanger_container_ids',
			'set_download_extensions',
			'set_download_classes',
			'set_link_classes',
			'track_admin',
			'limit_cookies_referral',
			'limit_cookies_session',
			'limit_cookies_visitor',
			'limit_cookies',
			'disable_cookies',
			'add_download_extensions',
			'track_404',
			'track_search',
			'add_post_annotations',
			'track_content',
			'track_ecommerce',
			'track_noscript',
			'noscript_code',
			'track_codeposition',
			'tracking_code',
			'force_protocol',
			'track_js_endpoint',
			'track_api_endpoint',
		);

		if ( has_matomo_tag_manager() ) {
			$keys_to_keep[] = 'tagmanger_container_ids';
		}

		$values = array();

		// default value in case no role/ post type is selected to make sure we unset it if no role /post type is selected
		$values['add_post_annotations'] = array();
		$values['tagmanger_container_ids'] = array();

		if ( $_POST[ self::FORM_NAME ]['track_mode'] === self::TRACK_MODE_TAGMANAGER ) {
			// no noscript mode in this case
			$_POST[ 'track_noscript' ] = '';
			$_POST[ 'noscript_code' ]  = '';
		} else {
			unset($_POST[ 'tagmanger_container_ids' ]);
		}

		if ( $_POST[ self::FORM_NAME ]['track_mode'] === self::TRACK_MODE_MANUALLY
		     || ( $_POST[ self::FORM_NAME ]['track_mode'] === self::TRACK_MODE_DISABLED &&
		          $this->settings->get_global_option( 'track_mode' ) === self::TRACK_MODE_MANUALLY ) ) {
			if ( ! empty( $_POST[ self::FORM_NAME ]['tracking_code'] ) ) {
				$_POST[ self::FORM_NAME ]['tracking_code'] = stripslashes( $_POST[ self::FORM_NAME ]['tracking_code'] );
			} else {
				$_POST[ self::FORM_NAME ]['tracking_code'] = '';
			}
			if ( ! empty( $_POST[ self::FORM_NAME ]['noscript_code'] ) ) {
				$_POST[ self::FORM_NAME ]['noscript_code'] = stripslashes( $_POST[ self::FORM_NAME ]['noscript_code'] );
			} else {
				$_POST[ self::FORM_NAME ]['noscript_code'] = '';
			}
		}

		foreach ( $_POST[ self::FORM_NAME ] as $name => $value ) {
			if ( in_array( $name, $keys_to_keep, true ) ) {
				$values[ $name ] = $value;
			}
		}

		$this->settings->apply_tracking_related_changes( $values );

		return true;
	}

	public function show_settings() {
		$was_updated = $this->update_if_submitted();
		$settings    = $this->settings;

		$containers = $this->get_active_containers();

		$track_modes = array(
			TrackingSettings::TRACK_MODE_DISABLED => __( 'Disabled', 'matomo' ),
			TrackingSettings::TRACK_MODE_DEFAULT  => __( 'Default tracking', 'matomo' ),
			TrackingSettings::TRACK_MODE_MANUALLY => __( 'Enter manually', 'matomo' )
		);

		if (!empty($containers)) {
			$track_modes[TrackingSettings::TRACK_MODE_TAGMANAGER] = __('Tag Manager', 'matomo');
		}

		include( dirname( __FILE__ ) . '/views/tracking.php' );
	}

	public function get_active_containers()
	{
		// we don't use Matomo API here to avoid needing to bootstrap Matomo which is slow and could break things
		$containers = array();
		if (has_matomo_tag_manager()) {
			global $wpdb;
			$dbsettings = new \WpMatomo\Db\Settings();
			$containerTable = $dbsettings->prefix_table_name('tagmanager_container');
			try {
				$containers = $wpdb->get_results(sprintf('SELECT `idcontainer`, `name` FROM %s where `status` = "active"', $containerTable));
			} catch (\Exception $e) {
				// table may not exist yet etc
				$containers = array();
			}
		}
		$by_id = array();
		foreach ($containers as $container) {
			$by_id[$container->idcontainer] = $container->name;
		}
		return $by_id;
	}


}
