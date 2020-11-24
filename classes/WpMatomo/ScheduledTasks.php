<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Piwik\Config;
use Piwik\CronArchive;
use Piwik\Filesystem;
use Piwik\Option;
use Piwik\Plugins\GeoIp2\GeoIP2AutoUpdater;
use Piwik\Plugins\GeoIp2\LocationProvider\GeoIp2;
use Piwik\Plugins\GeoIp2\LocationProvider\GeoIp2\Php;
use Piwik\Plugins\UserCountry\LocationProvider;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\User\Sync as UserSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class ScheduledTasks {
	const EVENT_SYNC    = 'matomo_scheduled_sync';
	const EVENT_DISABLE_ADDHANDLER    = 'matomo_scheduled_disable_addhandler';
	const EVENT_ARCHIVE = 'matomo_scheduled_archive';
	const EVENT_GEOIP   = 'matomo_scheduled_geoipdb';
	const EVENT_UPDATE  = 'matomo_update_core';

	const KEY_BEFORE_CRON = 'before-cron-';
	const KEY_AFTER_CRON  = 'after-cron-';

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
		$schedules['matomo_monthly'] = array(
			'interval' => 60 * 60 * 24 * 30,
			'display'  => __( 'Monthly', 'matomo' ),
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
			add_action(
				$event_name,
				function () use ( $self, $event_name ) {
					$self->set_last_time_before_cron( $event_name, time() );
				},
				$event_priority / 2,
				$accepted_args = 0
			);

			// actual event
			add_action( $event_name, array( $this, $event_config['method'] ), $event_priority, $accepted_args = 0 );

			// logging last execution end time
			add_action(
				$event_name,
				function () use ( $self, $event_name ) {
					$self->set_last_time_after_cron( $event_name, time() );
				},
				$event_priority * 2,
				$accepted_args = 0
			);
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
		$events = array(
			self::EVENT_SYNC    => array(
				'name'     => 'Sync users & sites',
				'interval' => 'daily',
				'method'   => 'sync',
			),
			self::EVENT_ARCHIVE => array(
				'name'     => 'Archive',
				'interval' => 'hourly',
				'method'   => 'archive',
			),
			self::EVENT_GEOIP   => array(
				'name'     => 'Update GeoIP DB',
				'interval' => 'matomo_monthly',
				'method'   => 'update_geo_ip2_db',
			),
		);
		if ($this->settings->should_disable_addhandler()) {
			$events[self::EVENT_DISABLE_ADDHANDLER] = array(
				'name'     => 'Disable AddHandler',
				'interval' => 'hourly',
				'method'   => 'disable_add_handler',
			);
		}
		return $events;
	}

	public function disable_add_handler($forceUndo = false)
	{
		$disable_addhandler = $this->settings->should_disable_addhandler();
		if ($disable_addhandler) {
			$this->logger->log( 'Scheduled tasks disabling addhandler' );
			try {
				Bootstrap::do_bootstrap();

				$files = Filesystem::globr(dirname(MATOMO_ANALYTICS_FILE), '.htaccess');
				foreach ($files as $file) {
					if (is_readable($file)) {
						$content = file_get_contents($file);
						$search = 'AddHandler';
						$replace = '# AddHandler';
						if ($forceUndo) {
							$search = '# AddHandler';
							$replace = 'AddHandler';
						}
						if (strpos($content, $search) !== false && ($forceUndo || strpos($content,$replace) === false)) {
							if (is_writeable($file)) {
								$content = str_replace($search, $replace, $content);
								@file_put_contents($file, $content);
							} else {
								$this->logger->log('Cannot update file as not writable ' . $file);
							}
						}
					}
				}
			} catch ( \Exception $e ) {
				$this->logger->log_exception( 'disable_addhandler', $e );
				throw $e;
			}
		}
	}

	private function check_try_update()
	{
		try {
			$installer = new Installer( $this->settings );
			if ( $installer->looks_like_it_is_installed() ) {
				$updater = new Updater( $this->settings );
				$updater->update_if_needed();
			}
		} catch ( \Exception $e ) {
			// we don't want to rethrow exception otherwise some other blogs might never sync
			$this->logger->log_exception( 'check_try_update', $e );
		}
	}

	public function perform_update() {
		$this->logger->log( 'Scheduled tasks perform update' );

		try {
			$updater = new Updater( $this->settings );
			$updater->update();
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'cron_update', $e );
			throw $e;
		}
	}

	public function update_geo_ip2_db() {
		$this->logger->log( 'Scheduled tasks update geoip database' );
		try {
			Bootstrap::do_bootstrap();

			$maxmind_license = $this->settings->get_global_option('maxmind_license_key');
			if (empty($maxmind_license)) {
				$db_url = GeoIp2::getDbIpLiteUrl();
			} else {
				$db_url = 'https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz&license_key=' . $maxmind_license;
			}

			Option::set( GeoIP2AutoUpdater::LOC_URL_OPTION_NAME, $db_url);
			$updater = new GeoIP2AutoUpdater();
			$updater->update();
			if ( LocationProvider::getCurrentProviderId() !== Php::ID && LocationProvider::getProviderById( Php::ID ) ) {
				LocationProvider::setCurrentProvider( Php::ID );
			}
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'update_geoip2', $e );
			throw $e;
		}
	}

	public function sync() {
		$this->check_try_update();

		$this->logger->log( 'Scheduled tasks sync all sites and users' );

		try {
			$site = new SiteSync( $this->settings );
			$site->sync_all();
			$user = new UserSync();
			$user->sync_all();
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'cron_sync', $e );
			throw $e;
		}
	}

	public function archive( $force = false, $throw_exception = true ) {
		$this->check_try_update();

		if ( defined( 'MATOMO_DISABLE_WP_ARCHIVING' ) && MATOMO_DISABLE_WP_ARCHIVING ) {
			return;
		}

		$this->logger->log( 'Scheduled tasks archive data' );

		try {
			Bootstrap::do_bootstrap();
		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'archive_bootstrap', $e );
			throw $e;
		}

		$archiver                               = new CronArchive();
		$archiver->concurrentRequestsPerWebsite = 1;
		$archiver->maxConcurrentArchivers       = 1;

		if ( $force ) {
			$archiver->shouldArchiveAllSites        = true;
			$archiver->disableScheduledTasks        = true;
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

			$archive_errors = $archiver->getErrors();

		} catch ( \Exception $e ) {
			$this->logger->log_exception( 'archive_main' , $e);
			$archive_errors = $archiver->getErrors();

			if (!empty($archive_errors)) {
				$message = '';
				foreach ($archiver->getErrors() as $error) {
					$message .= var_export($error, 1) . ' ';
				}
				$message = new \Exception(trim($message));
				$this->logger->log_exception('archive_errors', $message);
			}

			if ($throw_exception) {
				throw $e;
			} else {
				$archive_errors[] = $e->getMessage();
			}
		}

		return $archive_errors;
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
