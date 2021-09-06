<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Exception;
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Site\Sync\SyncConfig as SiteConfigSync;
use WpMatomo\TrackingCode\TrackingCodeGenerator;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
/**
 * @todo set up the nonce verification
 * phpcs:disable WordPress.Security.NonceVerification.Missing
 */
class TrackingSettings implements AdminSettingsInterface {
	const FORM_NAME             = 'matomo';
	const NONCE_NAME            = 'matomo_settings';
	const TRACK_MODE_DEFAULT    = 'default';
	const TRACK_MODE_DISABLED   = 'disabled';
	const TRACK_MODE_MANUALLY   = 'manually';
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
		return esc_html__( 'Tracking', 'matomo' );
	}

	private function update_if_submitted() {
		if ( $this->form_submitted() === true
			 && check_admin_referer( self::NONCE_NAME ) ) {
			$this->apply_settings();

			return true;
		}

		return false;
	}

	public function can_user_manage() {
		return current_user_can( Capabilities::KEY_SUPERUSER );
	}

	private function apply_settings() {
		$keys_to_keep = [
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
			'force_post',
			'disable_cookies',
			'cookie_consent',
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
			'track_jserrors',
			'track_api_endpoint',
			Settings::SITE_CURRENCY,
		];

		if ( matomo_has_tag_manager() ) {
			$keys_to_keep[] = 'tagmanger_container_ids';
		}

		$values = [];

		// default value in case no role/ post type is selected to make sure we unset it if no role /post type is selected
		$values['add_post_annotations']    = [];
		$values['tagmanger_container_ids'] = [];

		$valid_currencies = $this->get_supported_currencies();

		if ( ! empty( $_POST[ self::FORM_NAME ]['tracker_debug'] ) ) {
			$site_config_sync = new SiteConfigSync( $this->settings );
			switch ( $_POST[ self::FORM_NAME ]['tracker_debug'] ) {
				case 'always':
					$site_config_sync->set_config_value( 'Tracker', 'debug', 1 );
					$site_config_sync->set_config_value( 'Tracker', 'debug_on_demand', 0 );
					break;
				case 'on_demand':
					$site_config_sync->set_config_value( 'Tracker', 'debug', 0 );
					$site_config_sync->set_config_value( 'Tracker', 'debug_on_demand', 1 );
					break;
				default:
					$site_config_sync->set_config_value( 'Tracker', 'debug', 0 );
					$site_config_sync->set_config_value( 'Tracker', 'debug_on_demand', 0 );
			}
		}

		if ( empty( $_POST[ self::FORM_NAME ][ Settings::SITE_CURRENCY ] )
			 || ! array_key_exists( sanitize_text_field( wp_unslash( $_POST[ self::FORM_NAME ][ Settings::SITE_CURRENCY ] ) ), $valid_currencies ) ) {
			$_POST[ self::FORM_NAME ][ Settings::SITE_CURRENCY ] = 'USD';
		}

		if ( ! empty( $_POST[ self::FORM_NAME ]['track_mode'] ) ) {
			$track_mode = $this->get_track_mode();
			if ( self::TRACK_MODE_TAGMANAGER === $track_mode ) {
				// no noscript mode in this case
				$_POST[ self::FORM_NAME ]['track_noscript'] = '';
				$_POST[ self::FORM_NAME ]['noscript_code']  = '';
			} else {
				unset( $_POST['tagmanger_container_ids'] );
			}
			if ( $this->must_update_tracker() === true ) {
				// We want to keep the tracking code when user switches between disabled and manually or disabled to disabled.
				if ( ! empty( $_POST[ self::FORM_NAME ]['tracking_code'] ) ) {
					// don't process, this is a script
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput
					$_POST[ self::FORM_NAME ]['tracking_code'] = stripslashes( $_POST[ self::FORM_NAME ]['tracking_code'] );
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput
				} else {
					$_POST[ self::FORM_NAME ]['tracking_code'] = '';
				}
				if ( ! empty( $_POST[ self::FORM_NAME ]['noscript_code'] ) ) {
					// don't process, this is a script
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput
					$_POST[ self::FORM_NAME ]['noscript_code'] = stripslashes( $_POST[ self::FORM_NAME ]['noscript_code'] );
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput
				} else {
					$_POST[ self::FORM_NAME ]['noscript_code'] = '';
				}
			} else {
				$_POST[ self::FORM_NAME ]['noscript_code'] = '';
				$_POST[ self::FORM_NAME ]['tracking_code'] = '';
			}
		}
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput
		foreach ( $_POST[ self::FORM_NAME ] as $name => $value ) {
			if ( in_array( $name, $keys_to_keep, true ) ) {
				$values[ $name ] = $value;
			}
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput
		$this->settings->apply_tracking_related_changes( $values );

		return true;
	}

	private function get_track_mode() {
		if ( ! empty( $_POST[ self::FORM_NAME ]['track_mode'] ) ) {
			return sanitize_text_field( wp_unslash( $_POST[ self::FORM_NAME ]['track_mode'] ) );
		}
		return '';
	}
	/**
	 * Reauires form to be posted
	 *
	 * @return bool
	 */
	private function must_update_tracker() {
		$track_mode         = $this->get_track_mode();
		$previus_track_mode = $this->settings->get_global_option( 'track_mode' );
		$must_update        = false;
		if ( self::TRACK_MODE_MANUALLY === $track_mode
			 || ( self::TRACK_MODE_DISABLED === $track_mode &&
				  in_array( $previus_track_mode, [ self::TRACK_MODE_DISABLED, self::TRACK_MODE_MANUALLY ], true ) ) ) {
			// We want to keep the tracking code when user switches between disabled and manually or disabled to disabled.
			$must_update = true;
		}

		return $must_update;
	}

	/**
	 * @return bool
	 */
	private function form_submitted() {
		return isset( $_POST ) && ! empty( $_POST[ self::FORM_NAME ] )
			   && is_admin()
			   && $this->can_user_manage();
	}

	/**
	 * @param string $field
	 *
	 * @return bool
	 */
	private function has_valid_html_comments( $field ) {
		$valid = true;
		if ( $this->form_submitted() === true ) {
			if ( $this->must_update_tracker() === true ) {
				if ( ! empty( $_POST[ self::FORM_NAME ][ $field ] ) ) {
					// phpcs:disable WordPress.Security.ValidatedSanitizedInput
					$valid = $this->validate_html_comments( $_POST[ self::FORM_NAME ][ $field ] );
					// phpcs:enable WordPress.Security.ValidatedSanitizedInput
				}
			}
		}

		return $valid;
	}

	/**
	 * @param string $html html content to validate
	 *
	 * @returns boolean
	 */
	public function validate_html_comments( $html ) {
		$opening = substr_count( $html, '<!--' );
		$closing = substr_count( $html, '-->' );

		return ( $opening === $closing );
	}

	public function show_settings() {
		$was_updated     = false;
		$settings_errors = [];
		if ( $this->has_valid_html_comments( 'tracking_code' ) !== true ) {
			$settings_errors[] = __( 'Settings have not been saved. There is an issue with the HTML comments in the field "Tracking code". Make sure all opened comments (<!--) are closed (-->) correctly.', 'matomo' );
		}
		if ( $this->has_valid_html_comments( 'noscript_code' ) !== true ) {
			$settings_errors[] = __( 'Settings have not been saved. There is an issue with the HTML comments in the field "Noscript code". Make sure all opened comments (<!--) are closed (-->) correctly.', 'matomo' );
		}
		if ( count( $settings_errors ) === 0 ) {
			$was_updated = $this->update_if_submitted();
		}

		$settings = $this->settings;

		$containers = $this->get_active_containers();

		$track_modes = [
			self::TRACK_MODE_DISABLED => esc_html__( 'Disabled', 'matomo' ),
			self::TRACK_MODE_DEFAULT  => esc_html__( 'Default tracking', 'matomo' ),
			self::TRACK_MODE_MANUALLY => esc_html__( 'Enter manually', 'matomo' ),
		];

		if ( ! empty( $containers ) ) {
			$track_modes[ self::TRACK_MODE_TAGMANAGER ] = esc_html__( 'Tag Manager', 'matomo' );
		}

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		$matomo_currencies = $this->get_supported_currencies();

		$cookie_consent_modes = $this->get_cookie_consent_modes();

		$tracking_code_generator      = new TrackingCodeGenerator( $this->settings );
		$matomo_default_tracking_code = $tracking_code_generator->prepare_tracking_code( $idsite );

		include dirname( __FILE__ ) . '/views/tracking.php';
	}

	/**
	 * @return string[]
	 */
	private function get_cookie_consent_modes() {
		$modes = [];
		foreach ( CookieConsent::get_available_options() as $option => $description ) {
			$modes[ $option ] = $description;
		}

		return $modes;
	}

	private function get_supported_currencies() {
		$all        = include dirname( MATOMO_ANALYTICS_FILE ) . '/app/core/Intl/Data/Resources/currencies.php';
		$currencies = [];
		foreach ( $all as $key => $single ) {
			$currencies[ $key ] = $single[0] . ' ' . $single[1];
		}

		return $currencies;
	}

	public function get_active_containers() {
		// we don't use Matomo API here to avoid needing to bootstrap Matomo which is slow and could break things
		$containers = [];
		if ( matomo_has_tag_manager() ) {
			global $wpdb;
			$db_settings     = new \WpMatomo\Db\Settings();
			$container_table = $db_settings->prefix_table_name( 'tagmanager_container' );
			try {
				// phpcs:disable WordPress.DB
				$containers = $wpdb->get_results( sprintf( 'SELECT `idcontainer`, `name` FROM %s where `status` = "active"', $container_table ) );
				// phpcs:enable WordPress.DB
			} catch ( Exception $e ) {
				// table may not exist yet etc
				$containers = [];
			}
		}
		$by_id = [];
		foreach ( $containers as $container ) {
			$by_id[ $container->idcontainer ] = $container->name;
		}

		return $by_id;
	}
}
