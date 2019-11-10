<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Site;

use Piwik\Access;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Plugins\SitesManager\Model;
use Piwik\Plugins\SitesManager;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Logger;
use WpMatomo\Settings;
use WpMatomo\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Sync {
	const MAX_LENGTH_SITE_NAME = 90;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->logger   = new Logger();
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_action( 'update_option_blogname', array( $this, 'sync_current_site' ) );
		add_action( 'update_option_home', array( $this, 'sync_current_site' ) );
		add_action( 'update_option_siteurl', array( $this, 'sync_current_site' ) );
		add_action( 'update_site_option_matomo-global_track_ecommerce', array( $this, 'sync_current_site' ) );
		add_action( 'update_option_' . Settings::GLOBAL_OPTION_PREFIX .'track_ecommerce', array( $this, 'sync_current_site' ) );
	}

	public function sync_all() {
		$succeed_all = true;

		Bootstrap::do_bootstrap();

		if ( is_multisite() && function_exists( 'get_sites' ) ) {
			foreach ( get_sites() as $site ) {
				/** @var \WP_Site $site */
				switch_to_blog( $site->blog_id );
				try {
					$installer = new Installer( $this->settings );
					if ( ! $installer->looks_like_it_is_installed() ) {
						$this->logger->log( sprintf( 'Matomo was not installed yet for blog: %s installing now.', $site->blog_id ) );

						// prevents error that it wouldn't fully install matomo for a different site as it would think it already did install it etc.
						// and would otherwise think plugins are already activated etc
						Bootstrap::set_not_bootstrapped();
						$config = \Piwik\Config::getInstance();
						$installed = $config->PluginsInstalled;
						$installed['PluginsInstalled'] = array();
						$config->PluginsInstalled = $installed;

						$installer->install();
					}
					$success = $this->sync_site( $site->blog_id, $site->blogname, $site->siteurl );
				} catch ( \Exception $e ) {
					$success = false;
					// we don't want to rethrow exception otherwise some other blogs might never sync
					$this->logger->log( 'Matomo error syncing site: ' . $e->getMessage() );
				}

				$succeed_all = $succeed_all && $success;
				restore_current_blog();
			}
		} else {
			$success     = $this->sync_current_site();
			$succeed_all = $succeed_all && $success;
		}

		return $succeed_all;
	}

	public function sync_current_site() {
		return $this->sync_site( get_current_blog_id(), get_bloginfo( 'name' ), get_bloginfo( 'url' ) );
	}

	public function sync_site( $blog_id, $blog_name, $blog_url ) {

		Bootstrap::do_bootstrap();
		$this->logger->log( 'Matomo is now syncing blogId ' . $blog_id );

		$idsite = Site::get_matomo_site_id( $blog_id );

		if ( empty( $blog_name ) ) {
			$blog_name = __('Default');
		} else {
			$blog_name = substr( $blog_name, 0, self::MAX_LENGTH_SITE_NAME );
		}

		if ( ! empty( $idsite ) ) {
			// todo only update site when name or URL (or maybe also when timezone)changes!
			$this->logger->log( 'Matomo site is known for blog (' . $idsite . ')... will update' );

			/** @var \WP_Site $site */
			$params              = array(
				'name'      => $blog_name,
				'main_url'  => $blog_url,
				'ecommerce' => (int) $this->settings->get_global_option('track_ecommerce'),
				'timezone'  => $this->detect_timezone()
			);
			$sites_manager_model = new Model();
			$sites_manager_model->updateSite( $params, $idsite );

			do_action( 'matomo_site_synced', $idsite, $blog_id );

			// no actual setting changed but we make sure the tracking code will be updated after an update
			$this->settings->apply_tracking_related_changes( array() );

			return true;
		}

		$this->logger->log( 'Matomo site is not known for blog... will create site' );

		/** @var \WP_Site $site */
		$timezone = $this->detect_timezone();
		$idsite   = null;

		$this->set_enable_sites_admin(1);

		$track_ecommerce = (int) $this->settings->get_global_option('track_ecommerce');

		Access::doAsSuperUser( function () use ( $blog_name, $blog_url, $timezone, $track_ecommerce, &$idsite ) {
			SitesManager\API::unsetInstance();
			// we need to unset the instance to make sure it fetches the
			// up to date dependencies eg current plugin manager etc

			$idsite = SitesManager\API::getInstance()->addSite(
				$blog_name,
				array( $blog_url ),
				$track_ecommerce,
				$siteSearch = null,
				$search_keyword_parameters = null,
				$search_category_parameters = null,
				$excluded_ips = null,
				$excluded_query_parameters = null,
				$timezone
			);
		} );
		$this->set_enable_sites_admin(0);

		$this->logger->log( 'Matomo created site with ID ' . $idsite . ' for blog' );

		Site::map_matomo_site_id( $blog_id, $idsite );

		if ( ! is_numeric( $idsite ) || 0 == $idsite ) {
			$this->logger->log( sprintf( 'Creating the website failed: %s', json_encode( $blog_id ) ) );

			return false;
		}

		do_action( 'matomo_site_synced', $idsite, $blog_id );

		return true;
	}

	private function set_enable_sites_admin($enabled)
	{
		$general = Config::getInstance()->General;
		$general['enable_sites_admin'] = (int) $enabled;
		Config::getInstance()->General = $general;
	}

	private function detect_timezone() {
		$timezone = get_option( 'timezone_string' );

		if ( $timezone && $this->check_and_try_to_set_default_timezone( $timezone ) ) {
			return $timezone;
		}

		// older wordpress
		$utc_offset = (int) get_option( 'gmt_offset', 0 );

		if ( 0 === $utc_offset ) {
			return 'UTC';
		}

		$utc_offset_in_seconds = $utc_offset * 3600;
		$timezone              = timezone_name_from_abbr( '', $utc_offset_in_seconds );

		if ( $timezone && $this->check_and_try_to_set_default_timezone( $timezone ) ) {
			return $timezone;
		}

		$dst = (bool) date( 'I' );
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
			SitesManager\API::unsetInstance(); // make sure we're loading the latest instance with all up to date dependencies... mainly needed for tests
			SitesManager\API::getInstance()->setDefaultTimezone( $timezone );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

}
