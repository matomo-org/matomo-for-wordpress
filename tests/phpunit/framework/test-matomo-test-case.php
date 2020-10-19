<?php
/**
 * Matomo test case bootstrapping an entire Matomo.
 *
 * @package matomo
 */

use Piwik\Archive;
use Piwik\ArchiveProcessor\PluginsArchiver;
use Piwik\Cache;
use Piwik\Config;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable\Manager;
use Piwik\Date;
use Piwik\FrontController;
use Piwik\Option;
use Piwik\Plugin\API;
use Piwik\Site;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Installer;
use WpMatomo\Logger;
use WpMatomo\Paths;
use WpMatomo\Report\Metadata;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;
use WpMatomo\User;

class MatomoAnalytics_TestCase extends MatomoUnit_TestCase {

	/**
	 * Disable creation of temporary tables. This may be needed when you're writing a test that is
	 * tracking/archiving data. Problem is with temp tables many queries fail like this
	 *
	 * can't really use temporary tables as we otherwise get errors like
	 * : WP DB Error: Can't reopen table: 'log_action' - in plugin Actions at PluginsArchiver.php:186
	 * because temp tables cannot be joined
	 *
	 * @var bool
	 */
	protected $disable_temp_tables = false;

	public function _create_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_create_temporary_tables( $query );
		}

		return $query;
	}

	public function _drop_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_drop_temporary_tables( $query );
		}

		return $query;
	}

	public function setUp() {
		parent::setUp();

		if (!defined('PIWIK_TEST_MODE')) {
			define('PIWIK_TEST_MODE', true);
		}
		$uninstall = new Uninstaller();
		$uninstall->uninstall( true );
		clearstatcache();

		Bootstrap::set_not_bootstrapped();

		$settings  = new Settings();
		$installer = new Installer( $settings );
		$installer->install();

		// we need to init roles again... seems like WP isn't doing this by themselves...
		// otherwise if one test adds eg Capability WRITE_MATOMO to a role "editor", in other tests this
		// capability will still be present
		global $wp_roles;
		$wp_roles->init_roles();

		$roles = new Roles( $settings );
		$roles->add_roles();

		add_action(
			'set_current_user',
			function () {
				// auth might still be pointing to a different user...
				Bootstrap::set_not_bootstrapped();
			}
		);

		add_action(
			'matomo_uninstall',
			function () {
				Option::clearCache();
				Cache::flushAll();
				\Piwik\Singleton::clearAll();
				API::unsetAllInstances();
				ArchiveTableCreator::clear();
				Site::clearCache();
				Archive::clearStaticCache();
				FrontController::$requestId = null;
				Date::$now                  = null;
				\Piwik\Tracker\Cache::deleteTrackerCache();
				\Piwik\NumberFormatter::getInstance()->clearCache();
				\Piwik\Plugins\ScheduledReports\API::$cache = array();
				Manager::getInstance()->deleteAll();
				PluginsArchiver::$archivers = array();
				$_GET                       = $_REQUEST = array();
				\Piwik\Container\StaticContainer::get(\Piwik\Translation\Translator::class)->reset();
				\Piwik\Log::unsetInstance();
			}
		);

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}
	}

	public function tearDown() {
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}

		$uninstall = new Uninstaller();
		$uninstall->uninstall( true );

		unset( $_GET['trigger'] );
		Metadata::clear_cache();
		parent::tearDown();
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

	protected function assert_tracking_response( $tracking_response ) {
		$trans_gif_64      = 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		$expected_response = base64_decode( $trans_gif_64 );
		$this->assertEquals( $expected_response, $tracking_response );
	}

	protected function enable_browser_archiving() {
		$_GET['trigger']                                = 'archivephp';
		$general                                        = Config::getInstance()->General;
		$general['enable_browser_archiving_triggering'] = 1;
		$general['time_before_today_archive_considered_outdated'] = 1;
		Config::getInstance()->General                            = $general;

		$debug                            = Config::getInstance()->Debug;
		$debug['always_archive_data_day'] = 1;
		Config::getInstance()->Debug      = $debug;
	}

	protected function make_local_tracker( $date_time ) {
		Bootstrap::do_bootstrap();

		include_once 'test-local-tracker.php';
		$site     = new WpMatomo\Site();
		$paths    = new Paths();
		$endpoint = $paths->get_tracker_api_rest_api_endpoint();
		$tracker  = new MatomoLocalTracker( $site->get_current_matomo_site_id(), $endpoint );

		$tracker->setForceVisitDateTime( $date_time );
		$tracker->setIp( '156.5.3.2' );
		// Optional tracking
		$tracker->setUserAgent( 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)' );
		$tracker->setBrowserLanguage( 'fr' );
		$tracker->setLocalTime( '12:34:06' );
		$tracker->setResolution( 1024, 768 );
		$tracker->setBrowserHasCookies( true );
		$tracker->setPlugins( $flash = true, $java = true, $director = false );

		return $tracker;
	}

	protected function create_set_super_admin() {
		$logger = new Logger();
		$logger->log( 'creating super admin' );
		$id = self::factory()->user->create();

		$sync = new User\Sync();
		$sync->sync_current_users();

		wp_set_current_user( $id );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			grant_super_admin( $id );
			$user->add_cap( Capabilities::KEY_SUPERUSER );
		} else {
			$user->add_role( 'administrator' );
			$user->add_role( Roles::ROLE_SUPERUSER );
			$user->add_cap( Capabilities::KEY_SUPERUSER );
		}

		$sync = new User\Sync();
		$sync->sync_current_users();

		return $id;
	}
}
