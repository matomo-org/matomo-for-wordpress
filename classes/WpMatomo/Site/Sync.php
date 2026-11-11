<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Site;

use Exception;
use Piwik\Access;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Intl\Data\Provider\CurrencyDataProvider;
use Piwik\Plugins\SitesManager;
use Piwik\Plugins\SitesManager\Model;
use WP_Site;
use WpMatomo\Bootstrap;
use WpMatomo\Db\Settings as DbSettings;
use WpMatomo\Feature;
use WpMatomo\Installer;
use WpMatomo\Logger;
use WpMatomo\Request;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Site\Sync\SyncConfig;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Properties coming from matomo
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 */
class Sync extends Feature {
	const MAX_LENGTH_SITE_NAME = 90;

	const RETURN_TO_SERVICE_ACTIONS  = [ 'make_undelete_blog', 'unarchive_blog', 'make_ham_blog' ];
	const RETURN_TO_SERVICE_PRIORITY = 10;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var SyncConfig
	 */
	private $config_sync;

	/**
	 * Whether to print detailed progress to WP-CLI.
	 *
	 * @var bool
	 */
	private $verbose = false;

	public function __construct( Settings $settings ) {
		$this->logger      = new Logger();
		$this->settings    = $settings;
		$this->config_sync = new SyncConfig( $settings );
	}

	/**
	 * @return bool
	 */
	private function is_sync_allowed_for_request() {
		return ! Request::is_frontend();
	}

	public function register_hooks() {
		add_action( 'update_option_blogname', [ $this, 'sync_current_site_ignore_error' ] );
		add_action( 'update_option_home', [ $this, 'sync_current_site_ignore_error' ] );
		add_action( 'update_option_siteurl', [ $this, 'sync_current_site_ignore_error' ] );
		add_action( 'update_option_timezone_string', [ $this, 'sync_current_site_ignore_error' ] );
		add_action( 'matomo_setting_change_track_ecommerce', [ $this, 'sync_current_site_ignore_error' ] );
		add_action( 'matomo_setting_change_site_currency', [ $this, 'sync_current_site_ignore_error' ] );
		add_filter( 'wpmu_drop_tables', [ $this, 'on_drop_blog_tables' ], 10, 2 );
		add_action( 'wp_delete_site', [ $this, 'on_delete_site' ], 10, 1 );

		foreach ( self::RETURN_TO_SERVICE_ACTIONS as $blog_action ) {
			add_action( $blog_action, [ $this, 'on_blog_returned_to_service' ], self::RETURN_TO_SERVICE_PRIORITY, 1 );
		}
	}

	/**
	 * Tests only
	 *
	 * @internal
	 */
	public function remove_hooks() {
		remove_action( 'update_option_blogname', [ $this, 'sync_current_site_ignore_error' ] );
		remove_action( 'update_option_home', [ $this, 'sync_current_site_ignore_error' ] );
		remove_action( 'update_option_siteurl', [ $this, 'sync_current_site_ignore_error' ] );
		remove_action( 'update_option_timezone_string', [ $this, 'sync_current_site_ignore_error' ] );
		remove_action( 'matomo_setting_change_track_ecommerce', [ $this, 'sync_current_site_ignore_error' ] );
		remove_action( 'matomo_setting_change_site_currency', [ $this, 'sync_current_site_ignore_error' ] );
		remove_filter( 'wpmu_drop_tables', [ $this, 'on_drop_blog_tables' ], 10 );
		remove_action( 'wp_delete_site', [ $this, 'on_delete_site' ], 10 );

		foreach ( self::RETURN_TO_SERVICE_ACTIONS as $blog_action ) {
			remove_action( $blog_action, [ $this, 'on_blog_returned_to_service' ], self::RETURN_TO_SERVICE_PRIORITY );
		}
	}

	/**
	 * @param int $blog_id
	 */
	public function on_blog_returned_to_service( $blog_id ) {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		// only one of the three flags was cleared, and the blog stays out of service while any
		// of the others is still set
		if ( Site::is_blog_out_of_service( $blog_id ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		$this->sync_current_site_ignore_error();

		restore_current_blog();
	}

	/**
	 * @param string[] $tables
	 * @param int      $blog_id
	 *
	 * @return string[]
	 */
	public function on_drop_blog_tables( $tables, $blog_id ) {
		$matomo_tables = ( new DbSettings() )->get_installed_matomo_tables();

		$this->logger->log( sprintf( 'Matomo will drop %s tables of deleted blog %s', count( $matomo_tables ), $blog_id ) );

		return array_merge( (array) $tables, $matomo_tables );
	}

	/**
	 * remove the matomo site mapping when a site is deleted
	 *
	 * @param WP_Site $old_site
	 */
	public function on_delete_site( $old_site ) {
		Site::map_matomo_site_id( $old_site->id, null );
	}

	public function sync_current_site_ignore_error() {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			// these hooks are not admin only, so this may run somewhere wp-admin/includes is not loaded
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'matomo/matomo.php' ) ) {
			// @see https://github.com/matomo-org/matomo-for-wordpress/issues/577
			return;
		}
		try {
			$this->sync_current_site();
		} catch ( Exception $e ) {
			$this->logger->log( 'Ignoring site sync error: ' . $e->getMessage() );
			$this->logger->log_exception( 'sync_site_ignore', $e );
		}
	}

	public function sync_all( $verbose = false ) {
		$this->verbose = (bool) $verbose;
		$succeed_all = true;

		Bootstrap::do_bootstrap();
		$this->log( 'Starting site sync. Multisite: ' . ( is_multisite() ? 'yes' : 'no' ) );

		if ( is_multisite() && function_exists( 'get_sites' ) ) {
			// number => 0 means no limit. WP_Site_Query defaults to 100, which would silently leave
			// every blog after that without a Matomo site
			$sites = get_sites( [ 'number' => 0 ] );
			$this->log( 'Found ' . count( $sites ) . ' WordPress sites to inspect.' );
			foreach ( $sites as $site ) {
				if ( Site::is_blog_out_of_service( $site ) ) {
					$this->log( 'Skipping blog ' . $site->blog_id . ' because it is out of service.' );
					continue;
				}

				/** @var WP_Site $site */
				switch_to_blog( $site->blog_id );
				$this->log( sprintf( 'Processing blog %d: name="%s", URL="%s".', $site->blog_id, $site->blogname, $site->siteurl ) );

				$success = false;

				try {
					$installer = new Installer( $this->settings );
					$installed = $installer->looks_like_it_is_installed();
					$this->log( 'Matomo installation state for blog ' . $site->blog_id . ': ' . ( $installed ? 'installed' : 'not installed' ) . '.' );
					if ( ! $installed ) {
						$this->log( sprintf( 'Matomo was not installed yet for blog: %s installing now.', $site->blog_id ) );

						// prevents error that it wouldn't fully install matomo for a different site as it would think it already did install it etc.
						// and would otherwise think plugins are already activated etc
						Bootstrap::set_not_bootstrapped();
						$config                        = Config::getInstance();
						$installed                     = $config->PluginsInstalled;
						$installed['PluginsInstalled'] = [];
						$config->PluginsInstalled      = $installed;

						if ( $installer->can_be_installed() ) {
							$this->log( 'Installer reports blog ' . $site->blog_id . ' can be installed; starting installation.' );
							$installer->install();
						} else {
							$this->log( sprintf( 'Matomo cannot be installed for blog: %s, skipping it.', $site->blog_id ) );
							$upload_dir = ( new \WpMatomo\Paths() )->get_upload_base_dir();
							$this->log( sprintf( 'Install check: upload directory "%s" writable=%s; parent directory writable=%s.', $upload_dir, is_writable( $upload_dir ) ? 'yes' : 'no', is_writable( dirname( $upload_dir ) ) ? 'yes' : 'no' ) );
							continue;
						}
					}

					$success = $this->sync_site( $site->blog_id, $site->blogname, $site->siteurl );
					$this->log( 'Site sync for blog ' . $site->blog_id . ( $success ? ' succeeded.' : ' returned failure.' ) );
				} catch ( Exception $e ) {
					$success = false;
					// we don't want to rethrow exception otherwise some other blogs might never sync
					$this->log( sprintf( 'Matomo error syncing blog %d (%s): %s in %s:%d', $site->blog_id, get_class( $e ), $e->getMessage(), $e->getFile(), $e->getLine() ) );
				} finally {
					restore_current_blog();

					$succeed_all = $succeed_all && $success;
				}
			}
		} else {
			$success     = $this->sync_current_site();
			$succeed_all = $succeed_all && $success;
			$this->log( 'Site sync for current blog ' . get_current_blog_id() . ( $success ? ' succeeded.' : ' returned failure.' ) );
		}
		$this->log( 'Finished site sync: ' . ( $succeed_all ? 'all processed sites succeeded.' : 'one or more sites failed or were skipped.' ) );

		return $succeed_all;
	}

	public function sync_current_site() {
		return $this->sync_site( get_current_blog_id(), get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
	}

	public function sync_site( $blog_id, $blog_name, $blog_url ) {
		Bootstrap::do_bootstrap();
		$this->log( 'Matomo is now syncing blogId ' . $blog_id );

		$idsite = Site::get_matomo_site_id( $blog_id );
		$this->log( 'Current WordPress to Matomo site mapping for blog ' . $blog_id . ': ' . ( empty( $idsite ) ? 'none' : $idsite ) . '.' );

		$sites_manager_model = new Model();

		if ( ! is_multisite() ) {
			// we should have here only one record in the matomo_site table then...
			$matomo_id_sites = $sites_manager_model->getSitesId();
			$this->log( 'Matomo site IDs currently in the database: ' . wp_json_encode( $matomo_id_sites ) . '.' );
			if ( count( $matomo_id_sites ) === 1 ) {
				$matomo_id_site = (int) $matomo_id_sites[0];
				if ( empty( $idsite ) ) {
					// we have one record in the matomo_site table but the mapping does not exist. Force usage of the ID found.
					$idsite = $matomo_id_site;
					$this->log( "Can't find the id site in the mapping, but there is already an existing site. Use its ID " . $idsite . ' for blog' );
					wp_cache_flush();
					add_site_option( Site::SITE_MAPPING_PREFIX . $blog_id, $idsite );
				} elseif ( (int) $idsite !== $matomo_id_site ) {
					// the mapped id in the WP config is different from the id in the matomo_site table: we force usage of the matomo table site ID
					$idsite = $matomo_id_site;
					$this->log( 'The id site in the mapping is different from the id site in the matomo table. Force usage of Matomo ID ' . $idsite . ' for blog' );
					Site::map_matomo_site_id( $blog_id, $idsite );
				}
			} elseif ( count( $matomo_id_sites ) > 1 ) {
				// there are more than one record: we'll have to identify which one has data and which one must be removed from the matomo_site table
				$this->log( 'There is a problem in your configuration. Multiple Matomo sites exist on a single-site WordPress install.' );
				return false;
			}
		}

		if ( empty( $blog_name ) ) {
			$blog_name = esc_html__( 'Default', 'matomo' );
		} else {
			$blog_name = substr( $blog_name, 0, self::MAX_LENGTH_SITE_NAME );
		}

		$track_ecommerce   = (int) $this->settings->get_global_option( 'track_ecommerce' );
		$site_currency     = $this->settings->get_global_option( Settings::SITE_CURRENCY );
		$detected_timezone = $this->detect_timezone();

		$data_provider    = StaticContainer::get( CurrencyDataProvider::class );
		$valid_currencies = $data_provider->getCurrencyList();
		if ( ! array_key_exists( $site_currency, $valid_currencies ) ) {
			$site_currency = 'USD';
		}

		if ( ! empty( $idsite ) ) {
			$this->log( 'Matomo site is known for blog (' . $idsite . ')... will update' );

			$site = $sites_manager_model->getSiteFromId( $idsite );
			if ( ! empty( $site ) ) {
				// if site has changed then we have to update it
				if ( $site['name'] !== $blog_name
					|| $site['main_url'] !== $blog_url
				     // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					|| $site['ecommerce'] != $track_ecommerce
					|| $site['currency'] !== $site_currency
					|| $site['timezone'] !== $detected_timezone ) {

					/** @var WP_Site $site */
					$params = [
						'name'      => $blog_name,
						'main_url'  => $blog_url,
						'ecommerce' => $track_ecommerce,
						'currency'  => $site_currency,
						'timezone'  => $detected_timezone,
					];
					$sites_manager_model->updateSite( $params, $idsite );
					$this->log( 'Updated Matomo site ' . $idsite . ' with current WordPress site details.' );

					// the site details the tracking code was built from changed so invalidate
					$this->invalidate_current_blogs_tracking_code();

					do_action( 'matomo_site_synced', $idsite, $blog_id );
				}

				$this->config_sync->sync_config_for_current_site();
				$this->log( 'Synced Matomo configuration for blog ' . $blog_id . '.' );

				return true;
			}
			$this->log( 'Mapping points to Matomo site ' . $idsite . ', but no matching site record was found; will create a replacement.' );
		}

		$this->log( 'Matomo site is not known for blog... will create site' );

		/** @var WP_Site $site */
		$idsite = null;

		$this->set_enable_sites_admin( 1 );

		Access::doAsSuperUser(
			function () use ( $blog_name, $blog_url, $detected_timezone, $track_ecommerce, &$idsite, $site_currency ) {
				SitesManager\API::unsetInstance();
				// we need to unset the instance to make sure it fetches the
				// up to date dependencies eg current plugin manager etc

				$idsite                         = SitesManager\API::getInstance()->addSite(
					$blog_name,
					[ $blog_url ],
					$track_ecommerce,
					$site_search                = null,
					$search_keyword_parameters  = null,
					$search_category_parameters = null,
					$excluded_ips               = null,
					$excluded_query_parameters  = null,
					$detected_timezone,
					$site_currency
				);
			}
		);
		$this->set_enable_sites_admin( 0 );

		$this->log( 'Matomo created site with ID ' . $idsite . ' for blog' );

		if ( ! is_numeric( $idsite ) || 0 === $idsite || '0' === $idsite ) {
			$this->log( sprintf( 'Creating the website failed for blog %s; returned Matomo site ID was invalid: %s', $blog_id, wp_json_encode( $idsite ) ) );

			return false;
		}

		Site::map_matomo_site_id( $blog_id, $idsite );
		$this->log( 'Saved WordPress blog ' . $blog_id . ' to Matomo site ' . $idsite . ' mapping.' );

		$this->config_sync->sync_config_for_current_site();

		// invalidate the tracking code in case this site existed before but was just out of service
		$this->invalidate_current_blogs_tracking_code();

		do_action( 'matomo_site_synced', $idsite, $blog_id );

		return true;
	}

	/**
	 * Log sync progress. In verbose WP-CLI mode, print it directly so it is visible in command output.
	 *
	 * @param string $message Message to log.
	 */
	private function log( $message ) {
		if ( $this->verbose && defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::log( '[site sync] ' . $message );
			return;
		}

		$this->logger->log( $message );
	}

	private function invalidate_current_blogs_tracking_code() {
		// only invalidate if the tracking mode autogenerates the tracking code, and if
		// the tracking code has been generated once. otherwise there is no reason to invalidate.
		if ( ! $this->settings->is_tracking_code_autogenerated()
			|| ! $this->settings->get_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE )
		) {
			return;
		}

		$this->settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, 0 );
		$this->settings->save();
	}

	private function set_enable_sites_admin( $enabled ) {
		$general                       = Config::getInstance()->General;
		$general['enable_sites_admin'] = (int) $enabled;
		Config::getInstance()->General = $general;
	}

	private function detect_timezone() {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone && $this->check_and_try_to_set_default_timezone( $timezone ) ) {
			return $timezone;
		}

		// older WordPress
		$utc_offset = (int) get_option( 'gmt_offset', 0 );

		if ( 0 === $utc_offset ) {
			return 'UTC';
		}

		$utc_offset_in_seconds = $utc_offset * 3600;
		$timezone              = timezone_name_from_abbr( '', $utc_offset_in_seconds );

		if ( $timezone && $this->check_and_try_to_set_default_timezone( $timezone ) ) {
			return $timezone;
		}

		$dst = (bool) gmdate( 'I' );
		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $dst === (bool) $city['dst']
					&& $city['timezone_id']
					&& (int) $city['offset'] === $utc_offset_in_seconds ) {
					return $city['timezone_id'];
				}
			}
		}

		if ( is_numeric( $utc_offset ) ) {
			if ( $utc_offset > 0 ) {
				$timezone = 'UTC+' . $utc_offset;
			} else {
				$timezone = 'UTC' . $utc_offset;
			}

			if ( $this->check_and_try_to_set_default_timezone( $timezone ) ) {
				return $timezone;
			}
		}

		return 'UTC';
	}

	private function check_and_try_to_set_default_timezone( $timezone ) {
		try {
			Access::doAsSuperUser(
				function () use ( $timezone ) {
					// make sure we're loading the latest instance with all up to date dependencies... mainly needed for tests
					SitesManager\API::unsetInstance();
					SitesManager\API::getInstance()->setDefaultTimezone( $timezone );
				}
			);
		} catch ( Exception $e ) {
			return false;
		}

		return true;
	}
}
