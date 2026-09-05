<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Carries the tracking filter across a change in the plugin's activation scope.
 *
 * Which of the two settings holding it is read depends on whether the plugin is network activated
 * (see Settings::get_stealth_roles()), so a setting that was in use before such a change stops
 * being read after it, and roles a blog had excluded from tracking silently start being tracked.
 * Both directions merge rather than replace, so nobody who was excluded before is tracked after.
 */
class NetworkActivationMigration extends Feature {

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		$this->logger   = new Logger();
	}

	public function is_active() {
		// must also be able to run when wp-cli network activates a plugin, so can't be is_admin() only
		return true;
	}

	public function register_hooks() {
		register_activation_hook( MATOMO_ANALYTICS_FILE, [ $this, 'on_plugin_activated' ] );
		register_deactivation_hook( MATOMO_ANALYTICS_FILE, [ $this, 'on_plugin_deactivated' ] );

		// executes if activate_plugin()/deactivate_plugins() was invoked with the plugin slug rather
		// than with the path to its entry point
		add_action( 'activate_matomo', [ $this, 'on_plugin_activated' ] );
		add_action( 'deactivate_matomo', [ $this, 'on_plugin_deactivated' ] );
	}

	public function on_plugin_activated( $network_wide = false ) {
		if ( ! $network_wide ) {
			return;
		}

		$this->migrate_all_blogs();
	}

	public function on_plugin_deactivated( $network_wide = false ) {
		if ( ! $network_wide ) {
			return;
		}

		$this->migrate_all_blogs_back();
	}

	public function migrate_all_blogs() {
		$this->for_every_blog(
			function () {
				return $this->migrate_current_blog();
			},
			'Carried the tracking filter of %d blog(s) over to the network activated setting'
		);
	}

	public function migrate_all_blogs_back() {
		// a site option, so the same list for every blog: read once rather than once for each a blog
		$network_wide_filter = $this->get_network_wide_tracking_filter();

		$this->for_every_blog(
			function () use ( $network_wide_filter ) {
				return $this->migrate_current_blog_back( $network_wide_filter );
			},
			'Carried the network wide tracking filter back into %d blog(s)'
		);
	}

	/**
	 * @return bool whether anything was carried over
	 */
	public function migrate_current_blog() {
		$blog_global_settings = get_option( Settings::OPTION_GLOBAL, [] );
		if ( ! is_array( $blog_global_settings ) ) {
			return false;
		}

		$network_wide_filter = $this->read_tracking_filter( $blog_global_settings, Settings::OPTION_KEY_STEALTH );
		if ( empty( $network_wide_filter ) ) {
			return false;
		}

		$blog_settings = get_option( Settings::OPTION, [] );
		if ( ! is_array( $blog_settings ) ) {
			$blog_settings = [];
		}

		$blog_filter = $this->read_tracking_filter( $blog_settings, Settings::OPTION_KEY_STEALTH_BLOG );
		$merged      = $this->merge_tracking_filters( $blog_filter, [ $network_wide_filter ] );

		if ( $merged === $blog_filter ) {
			return false;
		}

		$blog_settings[ Settings::OPTION_KEY_STEALTH_BLOG ] = $merged;

		update_option( Settings::OPTION, $blog_settings );

		// settings options were written manually, so the Settings instance will be out of date here,
		// so re-load the saved setting data.
		$this->settings->init_settings();

		return true;
	}

	/**
	 * The reverse of migrate_current_blog(): once the plugin is no longer network activated, this
	 * blog's own global settings are read again and the two settings the network previously used
	 * are not, so both of them have to end up in the blog's own one.
	 *
	 * @param array<string, bool> $network_wide_filter
	 *
	 * @return bool whether anything was carried back
	 */
	public function migrate_current_blog_back( array $network_wide_filter ) {
		$blog_settings = get_option( Settings::OPTION, [] );
		if ( ! is_array( $blog_settings ) ) {
			$blog_settings = [];
		}

		$blog_filter = $this->read_tracking_filter( $blog_settings, Settings::OPTION_KEY_STEALTH_BLOG );

		if ( empty( $network_wide_filter ) && empty( $blog_filter ) ) {
			return false;
		}

		$blog_global_settings = get_option( Settings::OPTION_GLOBAL, [] );
		if ( ! is_array( $blog_global_settings ) ) {
			$blog_global_settings = [];
		}

		$own_filter = $this->read_tracking_filter( $blog_global_settings, Settings::OPTION_KEY_STEALTH );
		$merged     = $this->merge_tracking_filters( $own_filter, [ $network_wide_filter, $blog_filter ] );

		if ( $merged === $own_filter ) {
			return false;
		}

		$blog_global_settings[ Settings::OPTION_KEY_STEALTH ] = $merged;

		update_option( Settings::OPTION_GLOBAL, $blog_global_settings );

		// see migrate_current_blog()
		$this->settings->init_settings();

		return true;
	}

	private function for_every_blog( $func, $log_message ) {
		if ( ! is_multisite() || ! function_exists( 'get_sites' ) ) {
			return;
		}

		$migrated = 0;

		// number => 0 means no limit
		foreach ( get_sites( [ 'number' => 0 ] ) as $site ) {
			switch_to_blog( $site->blog_id );

			try {
				if ( $func() ) {
					++$migrated;
				}
			} catch ( Exception $e ) {
				// do not abort entire migration if a single blog migration fails
				$this->logger->log_exception( 'tracking_filter_migration', $e );
			} finally {
				restore_current_blog();
			}
		}

		$this->logger->log( sprintf( $log_message, $migrated ) );
	}

	private function get_network_wide_tracking_filter() {
		if ( ! is_multisite() ) {
			return [];
		}

		$network_settings = get_site_option( Settings::OPTION_GLOBAL, [] );
		if ( ! is_array( $network_settings ) ) {
			return [];
		}

		return $this->read_tracking_filter( $network_settings, Settings::OPTION_KEY_STEALTH );
	}

	private function read_tracking_filter( array $settings, $key ) {
		if ( empty( $settings[ $key ] ) || ! is_array( $settings[ $key ] ) ) {
			return [];
		}

		return $settings[ $key ];
	}

	/**
	 * Merged rather than replaced, the same way the two lists are merged when they are read: a role
	 * excluded by either of them stays excluded. So running a migration twice cannot start tracking
	 * somebody it excluded the first time.
	 *
	 * @param array<string, bool>   $target
	 * @param array<string, bool>[] $filters_to_merge_in
	 * @return array<string, bool>
	 */
	private function merge_tracking_filters( array $target, array $filters_to_merge_in ) {
		foreach ( $filters_to_merge_in as $filter ) {
			foreach ( $filter as $role_name => $is_excluded ) {
				if ( $is_excluded ) {
					$target[ $role_name ] = true;
				}
			}
		}

		return $target;
	}
}
