<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * https://github.com/braekling/matomo
 *
 */

namespace WpMatomo;

use Piwik\CliMulti\Process;
use Piwik\Tracker\Cache;
use WpMatomo\Admin\CookieConsent;
use WpMatomo\Admin\TrackingSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Settings {
	const OPTION_PREFIX                        = 'matomo-';
	const GLOBAL_OPTION_PREFIX                 = 'matomo_global-';
	const OPTION                               = 'matomo-option';
	const OPTION_GLOBAL                        = 'matomo-global-option';
	const OPTION_KEY_CAPS_ACCESS               = 'caps_access';
	const OPTION_KEY_STEALTH                   = 'caps_tracking';
	const OPTION_KEY_STEALTH_BLOG              = 'caps_tracking_blog';
	const OPTION_LAST_TRACKING_SETTINGS_CHANGE = 'last_tracking_settings_update';
	const OPTION_LAST_TRACKING_CODE_UPDATE     = 'last_tracking_code_update';
	const SHOW_GET_STARTED_PAGE                = 'show_get_started_page';
	const DELETE_ALL_DATA_ON_UNINSTALL         = 'delete_all_data_uninstall';
	const SITE_CURRENCY                        = 'site_currency';
	const NETWORK_CONFIG_OPTIONS               = 'config_options';
	const DISABLE_ASYNC_ARCHIVING_OPTION_NAME  = 'matomo_disable_async_archiving';
	const USE_SESSION_VISITOR_ID_OPTION_NAME   = 'use_session_visitor_id';
	const SERVER_SIDE_TRACKING_DELAY_SECS      = 'server_side_tracking_delay_secs';
	const GLOBAL_USER_AGENT_EXCLUSIONS         = 'global_user_agent_exclusions';
	const TRACK_AI_BOTS                        = 'track_ai_bots';
	const TRACK_AI_BOTS_USING_ESI              = 'track_ai_bots_using_esi';

	// NOTE: this is not a setting value, but is stored with setting values to avoid
	// adding an extra get_option call to every WordPress backoffice request.
	const INSTANCE_COMPONENTS_INSTALLED = 'instance-components-installed';

	public static $is_doing_action_tracking_related = false;
	/**
	 * @internal tests only
	 * @var bool
	 */
	public $force_disable_addhandler = false;

	/**
	 * Tests only
	 *
	 * @ignore
	 * @var bool
	 */
	private $assume_is_network_enabled_in_tests = false;

	/**
	 * Register default configuration set
	 *
	 * (public for tests)
	 *
	 * @var array
	 */
	public $default_global_settings = [
		// Plugin settings
		self::OPTION_LAST_TRACKING_SETTINGS_CHANGE => 0,
		self::OPTION_KEY_STEALTH                   => [],
		self::OPTION_KEY_CAPS_ACCESS               => [],
		self::NETWORK_CONFIG_OPTIONS               => [],
		self::DELETE_ALL_DATA_ON_UNINSTALL         => true,
		self::SITE_CURRENCY                        => 'USD',
		// User settings: Stats configuration
		// User settings: Tracking configuration
		'track_mode'                               => 'disabled',
		'track_js_endpoint'                        => 'default',
		'track_api_endpoint'                       => 'default',
		'track_codeposition'                       => 'footer',
		'track_noscript'                           => false,
		'track_content'                            => 'disabled',
		'track_ecommerce'                          => true,
		'track_search'                             => false,
		'track_404'                                => false,
		self::TRACK_AI_BOTS                        => false,
		self::TRACK_AI_BOTS_USING_ESI              => false,
		'tagmanger_container_ids'                  => [],
		'add_post_annotations'                     => [],
		'add_customvars_box'                       => false,
		'js_manually'                              => '',
		'noscript_manually'                        => '',
		'add_download_extensions'                  => '',
		'set_download_extensions'                  => '',
		'set_link_classes'                         => '',
		'set_download_classes'                     => '',
		'core_version'                             => '',
		'version_history'                          => [],
		'mail_history'                             => [],
		'disable_cookies'                          => false,
		'cookie_consent'                           => CookieConsent::REQUIRE_NONE,
		'force_post'                               => false,
		'limit_cookies'                            => false,
		'limit_cookies_visitor'                    => 34186669, // Matomo default 13 months
		'limit_cookies_session'                    => 1800, // Matomo default 30 minutes
		'limit_cookies_referral'                   => 15778463, // Matomo default 6 months
		'track_admin'                              => false,
		'track_across'                             => false,
		'track_across_alias'                       => false,
		'track_crossdomain_linking'                => false,
		'track_feed'                               => false,
		'track_feed_addcampaign'                   => false,
		'track_feed_campaign'                      => 'feed',
		'track_heartbeat'                          => 0,
		'track_user_id'                            => 'disabled',
		'track_datacfasync'                        => false,
		'track_jserrors'                           => false,
		'force_protocol'                           => 'disabled',
		'maxmind_license_key'                      => '',
		self::SHOW_GET_STARTED_PAGE                => 1,
		self::DISABLE_ASYNC_ARCHIVING_OPTION_NAME  => false,
	];

	/**
	 * Settings stored per blog
	 *
	 * @var array
	 */
	private $default_blog_settings = [
		'noscript_code'                          => '',
		'tracking_code'                          => '',
		self::OPTION_LAST_TRACKING_CODE_UPDATE   => 0,
		self::USE_SESSION_VISITOR_ID_OPTION_NAME => false,
		self::SERVER_SIDE_TRACKING_DELAY_SECS    => 180,
		self::GLOBAL_USER_AGENT_EXCLUSIONS       => null,
		self::OPTION_KEY_STEALTH_BLOG            => [],
	];

	private $global_settings = [];
	private $blog_settings   = [];

	/**
	 * The blog ID the cached settings were loaded for. Long-lived Settings instances can
	 * be used across switch_to_blog() calls, cached values must be reloaded when a blog
	 * changes.
	 *
	 * @var int|null
	 */
	private $loaded_for_blog_id = null;

	/**
	 * Whether the cached global settings were read from the network wide site option rather
	 * than from this blog's own option. Recorded on load instead of re-derived on reload, to
	 * avoid invoking WP option filters while reloading settings.
	 *
	 * TODO: for thoroughness, at some point we need to hook on plugin activation here and
	 * re-compute this value.
	 *
	 * @var bool
	 */
	private $global_settings_are_network_wide = false;

	/**
	 * @var string[]
	 */
	private $global_settings_changed = [];

	/**
	 * @var string[]
	 */
	private $blog_settings_changed = [];

	/**
	 * Settings whose value is a secret. Their values are never written to the debug log.
	 *
	 * Matching whole keys also means a secret nested inside an array valued setting cannot be
	 * hidden this way: the whole array is json encoded into the log.
	 *
	 * @var string[]
	 */
	private static $sensitive_settings = [
		'maxmind_license_key',
	];

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * Constructor class to prepare settings manager
	 *
	 */
	public function __construct() {
		$this->logger = new Logger();

		$this->init_settings();
	}

	public function init_settings() {
		// set and cleared before the option reads below, which all run filters that can
		// reach back in here through WpMatomo::$settings. such a re-entrant call must not find
		// a blog mismatch, or it would start the load over again recursing forever
		$this->loaded_for_blog_id = get_current_blog_id();

		// a re-entrant call must also not reference values that were changed for the previously
		// loaded blog
		$this->global_settings_changed = [];
		$this->blog_settings_changed   = [];
		$this->global_settings         = [];
		$this->blog_settings           = [];

		$this->global_settings_are_network_wide = $this->is_network_enabled();

		if ( $this->global_settings_are_network_wide ) {
			$global_settings = get_site_option( self::OPTION_GLOBAL, [] );
		} else {
			$global_settings = get_option( self::OPTION_GLOBAL, [] );
		}

		if ( ! empty( $global_settings ) && is_array( $global_settings ) ) {
			$this->global_settings = $global_settings;
		}

		$this->load_blog_settings();
	}

	public function get_customised_global_settings() {
		$this->reload_if_blog_switched();

		$custom_settings = [];

		foreach ( $this->global_settings as $key => $val ) {
			if ( isset( $this->default_global_settings[ $key ] )
			     // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
				&& $this->default_global_settings[ $key ] != $val ) {
				$custom_settings[ $key ] = $val;
			}
		}

		return $custom_settings;
	}

	public function uninstall() {
		Uninstaller::uninstall_options( self::OPTION_PREFIX );
		Uninstaller::uninstall_options( self::GLOBAL_OPTION_PREFIX );
		Uninstaller::uninstall_site_meta( self::GLOBAL_OPTION_PREFIX );
		$this->init_settings();
	}

	public function is_multisite() {
		return function_exists( 'is_multisite' ) && is_multisite();
	}

	/**
	 * @api
	 */
	public function is_network_enabled() {
		if ( $this->assume_is_network_enabled_in_tests ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( 'matomo/matomo.php' );
	}

	/**
	 * Save all settings as WordPress options.
	 *
	 * Note: save() should be called immediately after one or more set_option/set_global_option
	 * calls.
	 */
	public function save() {
		$this->reload_if_blog_switched();

		if ( empty( $this->global_settings_changed ) && empty( $this->blog_settings_changed ) ) {
			$this->logger->log( 'No settings changed yet' );
			return;
		}

		$this->logger->log( 'Save settings' );

		if ( ! empty( $this->global_settings_changed ) ) {
			if ( $this->global_settings_are_network_wide ) {
				update_site_option( self::OPTION_GLOBAL, $this->global_settings );
			} else {
				update_option( self::OPTION_GLOBAL, $this->global_settings );
			}
		}

		if ( ! empty( $this->blog_settings_changed ) ) {
			update_option( self::OPTION, $this->blog_settings );
		}

		$keys_changed = array_values(
			array_unique( array_merge( $this->global_settings_changed, $this->blog_settings_changed ) )
		);

		$this->global_settings_changed = [];
		$this->blog_settings_changed   = [];

		foreach ( $keys_changed as $key_changed ) {
			if ( self::GLOBAL_USER_AGENT_EXCLUSIONS === $key_changed ) {
				Bootstrap::do_bootstrap();
				Cache::clearCacheGeneral();
			}

			do_action( 'matomo_setting_change_' . $key_changed );
		}
	}

	/**
	 * Get a global option's value
	 *
	 * @param string $key
	 *            option key
	 *
	 * @return string|array option value
	 * @api
	 */
	public function get_global_option( $key ) {
		$this->reload_if_blog_switched();

		if ( isset( $this->global_settings[ $key ] ) ) {
			return $this->global_settings[ $key ];
		}

		if ( isset( $this->default_global_settings[ $key ] ) ) {
			return $this->default_global_settings[ $key ];
		}
	}

	/**
	 * Get an option's value related to a specific blog
	 *
	 * @param string $key option key
	 *
	 * @return string|array
	 * @api
	 */
	public function get_option( $key ) {
		$this->reload_if_blog_switched();

		if ( isset( $this->blog_settings[ $key ] ) ) {
			return $this->blog_settings[ $key ];
		}

		if ( isset( $this->default_blog_settings[ $key ] ) ) {
			return $this->default_blog_settings[ $key ];
		}
	}

	private function convert_type( $value, $type ) {
		if ( 'array' === $type && empty( $value ) ) {
			$value = []; // prevent eg converting '' to array('')
		} else {
			settype( $value, $type );
		}

		return $value;
	}

	/**
	 * Set a global option's value
	 *
	 * @param string       $key option key
	 * @param string|array $value new option value
	 */
	public function set_global_option( $key, $value ) {
		$this->reload_if_blog_switched();

		if ( isset( $this->default_global_settings[ $key ] ) ) {
			$type  = gettype( $this->default_global_settings[ $key ] );
			$value = $this->convert_type( $value, $type );
		}

		if ( ! isset( $this->global_settings[ $key ] )
			|| $this->global_settings[ $key ] !== $value
		) {
			$this->global_settings_changed[] = $key;
			$this->logger->log( 'Changed global option ' . $key . ': ' . $this->loggable_value( $key, $value ) );

			$this->global_settings[ $key ] = $value;
		}
	}

	/**
	 * Set an option's value related to a specific blog
	 *
	 * @param string $key option key
	 * @param string $value new option value
	 */
	public function set_option( $key, $value ) {
		$this->reload_if_blog_switched();

		if ( isset( $this->default_blog_settings[ $key ] ) ) {
			$type  = gettype( $this->default_blog_settings[ $key ] );
			$value = $this->convert_type( $value, $type );
		}

		if ( ! isset( $this->blog_settings[ $key ] )
			|| $this->blog_settings[ $key ] !== $value ) {
			$this->blog_settings_changed[] = $key;
			$this->logger->log( 'Changed option ' . $key . ': ' . $this->loggable_value( $key, $value ) );
			$this->blog_settings[ $key ] = $value;
		}
	}

	/**
	 * Values of some settings are secrets and must not be written to the debug log.
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @return string
	 */
	private function loggable_value( $key, $value ) {
		if ( in_array( $key, self::$sensitive_settings, true ) ) {
			return '(value hidden)';
		}

		if ( is_array( $value ) || is_object( $value ) ) {
			$encoded = wp_json_encode( $value );

			// wp_json_encode() returns false eg when the value nests deeper than it can encode
			return false === $encoded ? '(value could not be encoded)' : $encoded;
		}

		return (string) $value;
	}

	/**
	 * @param array $values
	 *
	 * @api
	 */
	public function apply_tracking_related_changes( $values ) {
		$this->set_global_option( self::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() );

		$this->apply_changes( $values );

		if ( ! self::$is_doing_action_tracking_related ) {
			// prevent recurison if any plugin was listening to this event and calling this method again
			self::$is_doing_action_tracking_related = true;
			do_action( 'matomo_tracking_settings_changed', $this, $values );
			self::$is_doing_action_tracking_related = false;
		}
	}

	public function should_disable_addhandler() {
		if ( $this->force_disable_addhandler ) {
			return true;
		}

		return defined( 'MATOMO_DISABLE_ADDHANDLER' ) && MATOMO_DISABLE_ADDHANDLER;
	}

	/**
	 * Apply new configuration
	 *
	 * @param array $settings
	 *            new configuration set
	 *
	 * @api
	 */
	public function apply_changes( $settings ) {
		$this->logger->log( 'Apply changed settings:' );
		foreach ( $this->default_global_settings as $key => $val ) {
			if ( isset( $settings[ $key ] ) ) {
				$this->set_global_option( $key, $settings[ $key ] );
			}
		}
		foreach ( $this->default_blog_settings as $key => $val ) {
			if ( isset( $settings[ $key ] ) ) {
				$this->set_option( $key, $settings[ $key ] );
			}
		}

		if ( $this->should_save_tracking_code_across_sites() ) {
			// special case for when the same tracking code needs to be used across all instances.
			if ( isset( $settings['tracking_code'] ) ) {
				$this->set_global_option( 'js_manually', $this->get_option( 'tracking_code' ) );
			}
			if ( isset( $settings['noscript_code'] ) ) {
				$this->set_global_option( 'noscript_manually', $this->get_option( 'noscript_code' ) );
			}
		}

		$this->save();
	}

	private function should_save_tracking_code_across_sites() {
		return $this->global_settings_are_network_wide
				&& $this->get_global_option( 'track_mode' ) === TrackingSettings::TRACK_MODE_MANUALLY;
	}

	public function get_js_tracking_code() {
		if ( $this->should_save_tracking_code_across_sites() ) {
			return $this->get_global_option( 'js_manually' );
		}

		return $this->get_option( 'tracking_code' );
	}

	public function get_noscript_tracking_code() {
		if ( $this->should_save_tracking_code_across_sites() ) {
			return $this->get_global_option( 'noscript_manually' );
		}

		return $this->get_option( 'noscript_code' );
	}

	public function is_cross_domain_linking_enabled() {
		return $this->get_global_option( 'track_crossdomain_linking' );
	}

	public function should_delete_all_data_on_uninstall() {
		if ( defined( 'MATOMO_REMOVE_ALL_DATA' ) ) {
			return (bool) MATOMO_REMOVE_ALL_DATA;
		}

		return (bool) $this->get_global_option( self::DELETE_ALL_DATA_ON_UNINSTALL );
	}

	/**
	 * Check if feed tracking is enabled
	 *
	 * @return boolean Is feed tracking enabled?
	 */
	public function is_track_feed() {
		return $this->get_global_option( 'track_feed' );
	}

	/**
	 * Check if admin tracking is enabled
	 *
	 * @return boolean Is admin tracking enabled?
	 */
	public function is_admin_tracking_enabled() {
		return $this->get_global_option( 'track_admin' ) && is_admin();
	}

	public function is_current_tracking_code() {
		$last_tracking_code_update     = $this->get_option( self::OPTION_LAST_TRACKING_CODE_UPDATE );
		$last_tracking_settings_update = $this->get_global_option( self::OPTION_LAST_TRACKING_SETTINGS_CHANGE );

		return $last_tracking_code_update && $last_tracking_code_update > $last_tracking_settings_update;
	}

	/**
	 * Check if feed permalinks get a campaign parameter
	 *
	 * @return boolean Add campaign parameter to feed permalinks?
	 */
	public function is_add_feed_campaign() {
		return $this->get_global_option( 'track_feed_addcampaign' );
	}

	public function is_tracking_enabled() {
		return $this->get_global_option( 'track_mode' ) !== 'disabled';
	}

	/**
	 * Whether the tracking code of the current blog is generated by the plugin, as opposed to
	 * being entered manually or not used at all.
	 *
	 * @return bool
	 */
	public function is_tracking_code_autogenerated() {
		return $this->is_tracking_enabled()
			&& TrackingSettings::TRACK_MODE_MANUALLY !== $this->get_global_option( 'track_mode' );
	}

	/**
	 * Check if noscript code insertion is enabled
	 *
	 * @return boolean Insert noscript code?
	 */
	public function is_add_no_script_code() {
		return $this->get_global_option( 'track_noscript' );
	}

	public function get_tracking_code_position() {
		return $this->get_global_option( 'track_codeposition' );
	}

	public function track_404_enabled() {
		return $this->get_global_option( 'track_404' );
	}

	public function track_user_id_enabled() {
		return $this->get_global_option( 'track_user_id' ) !== 'disabled';
	}

	public function track_search_enabled() {
		return ( is_search() && $this->get_global_option( 'track_search' ) );
	}

	public function set_assume_is_network_enabled_in_tests( $network_enabled = true ) {
		$this->assume_is_network_enabled_in_tests = $network_enabled;
		$this->global_settings_are_network_wide   = $network_enabled;
	}

	public function is_async_archiving_supported() {
		return Process::isSupported() && ( ! defined( 'MATOMO_SUPPORT_ASYNC_ARCHIVING' ) || MATOMO_SUPPORT_ASYNC_ARCHIVING );
	}

	public function is_async_archiving_disabled_by_option() {
		return (bool) $this->get_global_option( self::DISABLE_ASYNC_ARCHIVING_OPTION_NAME );
	}

	public function is_ai_bot_tracking_enabled() {
		return (bool) $this->get_global_option( self::TRACK_AI_BOTS );
	}

	public function is_tracking_ai_bots_via_esi_includes() {
		return (bool) $this->get_global_option( self::TRACK_AI_BOTS_USING_ESI );
	}

	public function get_matomo_major_version() {
		$core_version = $this->get_global_option( 'core_version' );
		$core_version = isset( $core_version ) ? $core_version : '';

		$parts = explode( '.', $core_version );
		if ( empty( $parts ) ) {
			return 0;
		}

		return (int) $parts[0];
	}

	/**
	 * Note: "Global" here means what it means in Matomo, where the setting this stands in for lives:
	 * every Matomo site of one Matomo install. A WordPress blog only has one Matomo install of its
	 * own, so this is stored per blog even when the plugin is network activated.
	 *
	 * @param string[] $user_agents
	 */
	public function set_global_user_agent_exclusions( $user_agents ) {
		$this->set_option( self::GLOBAL_USER_AGENT_EXCLUSIONS, $user_agents );
	}

	public function get_global_user_agent_exclusions() {
		$user_agents = $this->get_option( self::GLOBAL_USER_AGENT_EXCLUSIONS );

		if ( ! is_array( $user_agents ) ) {
			// previously this setting was incorrectly stored as a network wide option. now it
			// is saved as a per-blog option, but we make sure to fall back to the network wide
			// setting for installs that still have a value there.
			$user_agents = $this->get_global_option( self::GLOBAL_USER_AGENT_EXCLUSIONS );
		}

		if ( ! is_array( $user_agents ) ) {
			// only bootstrap if we can't access the SitesManager API.
			// if we always bootstrap, it is possible to try initializing the FrontController before Matomo
			// installation completes, which will fail.
			if ( ! class_exists( \Piwik\Plugins\SitesManager\API::class ) ) {
				Bootstrap::do_bootstrap();
			}

			$user_agents = \Piwik\Plugins\SitesManager\API::getInstance()->getExcludedUserAgentsGlobal();
			$user_agents = explode( ',', $user_agents );
		}

		return $user_agents;
	}

	/**
	 * The WordPress roles whose users must not be tracked on the current blog.
	 *
	 * Two settings decide this: OPTION_KEY_STEALTH, a network wide option, and OPTION_KEY_STEALTH_BLOG
	 * the blog's own. Merged rather than one overriding the other, so a blog can stop tracking a
	 * role the network still tracks, but cannot start tracking one the network excluded.
	 *
	 * @return array<string, bool> role name => true, listing only the excluded roles
	 */
	public function get_stealth_roles() {
		$stealth_roles = [];

		$keys = [ self::OPTION_KEY_STEALTH ];
		if ( $this->is_network_enabled() ) {
			// use the per-blog overrides only if network mode is enabled
			$keys[] = self::OPTION_KEY_STEALTH_BLOG;
		}

		foreach ( $keys as $key ) {
			$roles = self::OPTION_KEY_STEALTH === $key
				? $this->get_global_option( $key )
				: $this->get_option( $key );

			if ( ! is_array( $roles ) ) {
				continue;
			}

			foreach ( $roles as $role_name => $is_excluded ) {
				if ( $is_excluded ) {
					$stealth_roles[ $role_name ] = true;
				}
			}
		}

		return $stealth_roles;
	}

	public function is_track_via_esi_enabled() {
		return ( (bool) $this->get_global_option( 'track_ai_bots_using_esi' ) ) === true;
	}

	private function load_blog_settings() {
		// set and cleared before the option read below for the same reasons as in
		// init_settings(), see there
		$this->loaded_for_blog_id    = get_current_blog_id();
		$this->blog_settings         = [];
		$this->blog_settings_changed = [];

		$settings = get_option( self::OPTION, [] );
		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		$this->blog_settings = $settings;
	}

	/**
	 * Reload cached settings if the current blog changed since they were loaded (eg, via
	 * switch_to_blog()). Cached per-blog settings must not be served for, or saved to, a
	 * different blog. Pending unsaved per-blog changes are discarded.
	 */
	private function reload_if_blog_switched() {
		if ( ! $this->is_multisite()
			|| get_current_blog_id() === $this->loaded_for_blog_id
		) {
			return;
		}

		$discarded = $this->blog_settings_changed;

		if ( $this->global_settings_are_network_wide ) {
			// they live in a network wide site option, so neither they nor unsaved changes to
			// them are affected by the blog switch, and both are left alone
			$this->load_blog_settings();
		} else {
			// without network activation the global settings are stored per blog too
			$discarded = array_merge( $this->global_settings_changed, $discarded );

			$this->init_settings();
		}

		if ( ! empty( $discarded ) ) {
			// the blog was switched in code before the settings were save()'d
			$this->logger->log(
				'Unsaved Matomo setting changes were discarded after a WP blog switch: '
				. implode( ', ', array_values( array_unique( $discarded ) ) )
			);
		}
	}
}
