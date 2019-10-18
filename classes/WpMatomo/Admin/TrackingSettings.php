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
		return 'Tracking';
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
			'set_download_extensions',
			'set_download_classes',
			'set_link_classes',
			'track_admin',
			Settings::OPTION_KEY_STEALTH,
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

		$values = array();

		// default value in case no role/ post type is selected to make sure we unset it if no role /post type is selected
		$values['add_post_annotations']         = array();
		$values[ Settings::OPTION_KEY_STEALTH ] = array();

		if ( $_POST[ self::FORM_NAME ][ 'track_mode' ] === self::TRACK_MODE_MANUALLY) {
			if ( !empty($_POST[ self::FORM_NAME ][ 'tracking_code' ])) {
				$_POST[ self::FORM_NAME ][ 'tracking_code' ] = stripslashes($_POST[ self::FORM_NAME ][ 'tracking_code' ]);
			} else {
				$_POST[ self::FORM_NAME ][ 'tracking_code' ] = '';
			}
			if ( !empty($_POST[ self::FORM_NAME ][ 'noscript_code' ])) {
				$_POST[ self::FORM_NAME ][ 'noscript_code' ] = stripslashes($_POST[ self::FORM_NAME ][ 'noscript_code' ]);
			} else {
				$_POST[ self::FORM_NAME ][ 'noscript_code' ] = '';
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
		global $wp_roles;

		$was_updated = $this->update_if_submitted();
		$settings    = $this->settings;

		include_once( dirname( __FILE__ ) . '/views/tracking.php' );
	}


}
