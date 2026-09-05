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

		// executes if activate_plugin() was invoked with the plugin slug rather than with the
		// path to its entry point
		add_action( 'activate_matomo', [ $this, 'on_plugin_activated' ] );
	}

	/**
	 * @param bool $network_wide passed by WordPress, true when the plugin is being activated for
	 *                           the whole network rather than for a single blog.
	 */
	public function on_plugin_activated( $network_wide = false ) {
		if ( ! $network_wide ) {
			return;
		}

		$this->migrate_all_blogs();
	}

	public function migrate_all_blogs() {
		if ( ! is_multisite() || ! function_exists( 'get_sites' ) ) {
			return;
		}

		$migrated = 0;

		// number => 0 means no limit
		foreach ( get_sites( [ 'number' => 0 ] ) as $site ) {
			switch_to_blog( $site->blog_id );

			try {
				if ( $this->migrate_current_blog() ) {
					++$migrated;
				}
			} catch ( Exception $e ) {
				// do not abort entire migration if a single blog migration fails
				$this->logger->log_exception( 'tracking_filter_migration', $e );
			} finally {
				restore_current_blog();
			}
		}

		$this->logger->log( sprintf( 'Carried the tracking filter of %d blog(s) over to the network activated setting', $migrated ) );
	}

	/**
	 * @return bool whether anything was carried over
	 */
	public function migrate_current_blog() {
		$blog_global_settings = get_option( Settings::OPTION_GLOBAL, [] );
		if (
			! is_array( $blog_global_settings )
			|| empty( $blog_global_settings[ Settings::OPTION_KEY_STEALTH ] )
			|| ! is_array( $blog_global_settings[ Settings::OPTION_KEY_STEALTH ] )
		) {
			return false;
		}

		$network_wide_filter = $blog_global_settings[ Settings::OPTION_KEY_STEALTH ];

		$blog_settings = get_option( Settings::OPTION, [] );
		if ( ! is_array( $blog_settings ) ) {
			$blog_settings = [];
		}

		$blog_filter = isset( $blog_settings[ Settings::OPTION_KEY_STEALTH_BLOG ] )
			&& is_array( $blog_settings[ Settings::OPTION_KEY_STEALTH_BLOG ] )
				? $blog_settings[ Settings::OPTION_KEY_STEALTH_BLOG ]
				: [];

		// merged rather than replaced, the same way the two lists are merged when they are read:
		// a role excluded by either of them stays excluded, so running this twice cannot start
		// tracking somebody it excluded the first time
		$merged = $blog_filter;
		foreach ( $network_wide_filter as $role_name => $is_excluded ) {
			if ( $is_excluded ) {
				$merged[ $role_name ] = true;
			}
		}

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
}
