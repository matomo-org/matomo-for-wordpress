<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use WpMatomo\Access;
use WpMatomo\Capabilities;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class GeolocationSettings implements AdminSettingsInterface {
	const NONCE_NAME = 'matomo_geolocation';
	const FORM_NAME = 'matomo_geoconfig';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_title() {
		return esc_html__( 'Geolocation', 'matomo' );
	}

	private function update_if_submitted() {
		if ( isset( $_POST )
			 && isset( $_POST[ self::FORM_NAME ] )
			 && is_admin()
			 && check_admin_referer( self::NONCE_NAME )
			 && current_user_can( Capabilities::KEY_SUPERUSER ) ) {

			$url = stripslashes($_POST[ self::FORM_NAME ]);
			if (empty($url)) {
				$url = '';
			}
			if (strlen($url) > 20) {
				throw new \Exception('URL is too long');
			}
			if ($url && strpos($url, 'https://download.maxmind.com') !== 0
			 && strpos($url, 'https://db-ip.com/') !== 0) {
				throw new \WP_Error(1, 'Please enter either a MaxMind Download URL or a DB IP Url.');
			}
			$url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=' .urlencode($url).'&suffix=tar.gz';
			$this->settings->set_global_option('geolocation_url', $url);

			// update geoip in the backgronud
			wp_schedule_single_event( time() + 30, ScheduledTasks::EVENT_GEOIP );

			return true;
		}

		return false;
	}

	public function show_settings() {
		$was_updated = $this->update_if_submitted();
		$current_url = $this->settings->get_global_option('geolocation_url');

		include dirname( __FILE__ ) . '/views/geolocation_settings.php';
	}

}
