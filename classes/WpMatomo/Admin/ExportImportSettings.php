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
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class ExportImportSettings implements AdminSettingsInterface {
	const NONCE_NAME = 'matomo_import_nonce';
	const FORM_NAME = 'matomo_import';

	private function get_supported_settings() {
		return array(
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
			'track_noscript',
			'noscript_code',
			'track_codeposition',
			'tracking_code',
			'force_protocol',
			'track_js_endpoint',
			'track_api_endpoint',
		);
	}

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_title() {
		return __( 'Export/Import', 'matomo' );
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
		     && ! empty( $_POST[ self::FORM_NAME ] )
		     && is_admin()
		     && check_admin_referer( self::NONCE_NAME )
		     && current_user_can( Capabilities::KEY_SUPERUSER ) ) {

			$this->settings->apply_tracking_related_changes( json_decode( $_POST[ self::FORM_NAME ] ) );

			return true;
		}

		return false;
	}

	public function show_settings() {
		$this->update_if_submitted();

		$export = array();
		foreach ( $this->get_supported_settings() as $setting_key ) {
			$export[ $setting_key ] = $this->settings->get_global_option( $setting_key );
		}
		$export = json_encode( $export );

		include( dirname( __FILE__ ) . '/views/export_import.php' );
	}

}
