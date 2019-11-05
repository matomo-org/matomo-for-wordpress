<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

use Piwik\CronArchive;
use Piwik\Option;
use Piwik\Plugins\GeoIp2\GeoIP2AutoUpdater;
use Piwik\Plugins\GeoIp2\LocationProvider\GeoIp2\Php;
use Piwik\Plugins\UserCountry\LocationProvider;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\User\Sync as UserSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class ScheduledTasks {
	const EVENT_SYNC = 'matomo_scheduled_sync';
	const EVENT_ARCHIVE = 'matomo_scheduled_archive';
	const EVENT_GEOIP = 'matomo_scheduled_geoipdb';
	const EVENT_UPDATE = 'matomo_update_core';

	const KEY_BEFORE_CRON = 'before-cron-';
	const KEY_AFTER_CRON = 'after-cron-';

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

	public function add_weekly_schedule( $schedules ) {
		$schedules['matomo_weekly'] = array(
			'interval' => 60 * 60 * 24 * 7, # 604,800, seconds in a week
			'display'  => __( 'Weekly' )
		);

		return $schedules;
	}

	public function schedule() {
		add_action( self::EVENT_UPDATE, array( $this, 'perform_update' ) );
		add_filter( 'cron_schedules', array( $this, 'add_weekly_schedule' ) );

		$self           = $this;
		$event_priority = 10;

		foreach ( $this->get_all_events() as $event_name => $event_config ) {
			if ( ! wp_next_scheduled( $event_name ) ) {
				wp_schedule_event( time(), $event_config['interval'], $event_name );
			}

			// logging last execution start time
			add_action( $event_name, function () use ( $self, $event_name ) {
				$self->set_last_time_before_cron( $event_name, time() );
			}, $event_priority / 2, $accepted_args = 0 );

			// actual event
			add_action( $event_name, array( $this, $event_config['method'] ), $event_priority, $accepted_args = 0 );

			// logging last execution end time
			add_action( $event_name, function () use ( $self, $event_name ) {
				$self->set_last_time_after_cron( $event_name, time() );
			}, $event_priority * 2, $accepted_args = 0 );
		}

		register_deactivation_hook( MATOMO_ANALYTICS_FILE, array( $this, 'uninstall' ) );
	}

	public function get_last_time_before_cron( $event_name ) {
		return get_option( Settings::OPTION_PREFIX . self::KEY_BEFORE_CRON . $event_name );
	}

	public function set_last_time_before_cron( $event_name, $time ) {
		// we use settings prefix so data automatically gets removed when uninstalling
		update_option( Settings::OPTION_PREFIX . self::KEY_BEFORE_CRON . $event_name, $time );
	}

	public function get_last_time_after_cron( $event_name ) {
		return get_option( Settings::OPTION_PREFIX . self::KEY_AFTER_CRON . $event_name );
	}

	public function set_last_time_after_cron( $event_name, $time ) {
		// we use settings prefix so data automatically gets removed when uninstalling
		update_option( Settings::OPTION_PREFIX . self::KEY_AFTER_CRON . $event_name, $time );
	}

	public function get_all_events() {
		return array(
			self::EVENT_SYNC    => array( 'name' => 'Sync users & sites',
			                              'interval' => 'daily',
			                              'method' => 'sync'
			),
			self::EVENT_ARCHIVE => array( 'name' => 'Archive',
			                              'interval' => 'hourly',
			                              'method' => 'archive'
			),
			self::EVENT_GEOIP   => array( 'name'     => 'Update GeoIP DB',
			                              'interval' => 'matomo_weekly',
			                              'method'   => 'update_geo_ip2_db'
			)
		);
	}

	public function perform_update() {
		$this->logger->log( 'Scheduled tasks perform update' );

		try {
			$updater = new Updater( $this->settings );
			$updater->update();
		} catch ( \Exception $e ) {
			$this->logger->log( 'Update failed: ' . $e->getMessage() );
			throw $e;
		}
	}

	public function update_geo_ip2_db() {
		$this->logger->log( 'Scheduled tasks update geoip database' );
		try {
			Bootstrap::do_bootstrap();
			Option::set( GeoIP2AutoUpdater::LOC_URL_OPTION_NAME, 'https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz' );
			$updater = new GeoIP2AutoUpdater();
			$updater->update();
			if ( LocationProvider::getCurrentProviderId() !== Php::ID && LocationProvider::getProviderById( Php::ID ) ) {
				LocationProvider::setCurrentProvider( Php::ID );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'Update GeoIP DB failed' . $e->getMessage() );
			throw $e;
		}
	}

	public function sync() {
		$this->logger->log( 'Scheduled tasks sync all sites and users' );

		try {
			$site = new SiteSync( $this->settings );
			$site->sync_all();
			$user = new UserSync();
			$user->sync_all();
		} catch ( \Exception $e ) {
			$this->logger->log( 'Sync failed' . $e->getMessage() );
			throw $e;
		}
	}

	public function archive( $force = false ) {
		if ( defined( 'MATOMO_DISABLE_WP_ARCHIVING' ) && MATOMO_DISABLE_WP_ARCHIVING ) {
			return;
		}

		$this->logger->log( 'Scheduled tasks archive data' );

		try {
			Bootstrap::do_bootstrap();
		} catch ( \Exception $e ) {
			$this->logger->log( 'Archive bootstrap failed' . $e->getMessage() );
			throw $e;
		}

		$archiver                               = new CronArchive();
		$archiver->concurrentRequestsPerWebsite = 1;
		$archiver->maxConcurrentArchivers       = 1;
		$archiver->dateLastForced               = 2;

		if ( $force ) {
			$archiver->shouldArchiveAllSites        = true;
			$archiver->disableScheduledTasks        = true;
			$archiver->shouldArchiveAllPeriodsSince = true;
		}

		if ( is_multisite() ) {
			if ( is_network_admin() ) {
				return; // nothing to archive
			} else {
				$blog_id = get_current_blog_id();
				$idsite  = Site::get_matomo_site_id( $blog_id );
				if ( ! empty( $idsite ) ) {
					$archiver->shouldArchiveSpecifiedSites = array( $idsite );
				} else {
					// there is no site mapped to it so there's no point in archiving it
					return;
				}
			}
		}

		try {
			$archiver->main();
		} catch ( \Exception $e ) {
			$this->logger->log( 'Failed Matomo Archive: ' . $e->getMessage() );
			throw $e;
		}
	}

	public function uninstall() {
		$this->logger->log( 'Scheduled tasks uninstall all events' );

		foreach ( $this->get_all_events() as $event_name => $config ) {
			$timestamp = wp_next_scheduled( $event_name );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $event_name );
			}
		}
		foreach ( $this->get_all_events() as $event_name => $config ) {
			wp_clear_scheduled_hook( $event_name );
		}
	}
}
