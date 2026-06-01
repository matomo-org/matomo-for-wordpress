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

require_once __DIR__ . '/fixture/test-matomo-fixture.php';

/**
 * Piwik constants
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
class MatomoAnalytics_TestCase extends MatomoUnit_TestCase {

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	protected $matomo_fixture;

	/**
	 * Disable creation of temporary tables. This may be needed when you're writing a test that is
	 * tracking/archiving data. Problem is with temp tables many queries fail like this
	 *
	 * Can't really use temporary tables as we otherwise get errors like
	 * : WP DB Error: Can't reopen table: 'log_action' - in plugin Actions at PluginsArchiver.php:186
	 * because temp tables cannot be joined
	 *
	 * @var bool
	 */
	protected $disable_temp_tables = false;

	protected $tracker_user;

	protected $application_password;

	/**
	 * @param string $query
	 *
	 * @return mixed
	 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	 */
	public function _create_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_create_temporary_tables( $query );
		}

		return $query;
	}

	/**
	 * @param string $query
	 *
	 * @return mixed
	 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
	 */
	public function _drop_temporary_tables( $query ) {
		if ( ! $this->disable_temp_tables ) {
			$query = parent::_drop_temporary_tables( $query );
		}

		return $query;
	}

	public function setUp(): void {
		parent::setUp();

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
		error_reporting( E_ALL );

		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			function ( $errno, $errstr, $errfile, $errline ) {
				static $error_names = [
					E_ERROR             => 'Error',
					E_WARNING           => 'Warning',
					E_PARSE             => 'Parse',
					E_NOTICE            => 'Notice',
					E_CORE_ERROR        => 'Core Error',
					E_CORE_WARNING      => 'Core Warning',
					E_COMPILE_ERROR     => 'Compile Error',
					E_COMPILE_WARNING   => 'Compile Warning',
					E_USER_ERROR        => 'User Error',
					E_USER_WARNING      => 'User Warning',
					E_USER_NOTICE       => 'User Notice',
					E_STRICT            => 'Strict',
					E_RECOVERABLE_ERROR => 'Recoverable Error',
					E_DEPRECATED        => 'Deprecated',
					E_USER_DEPRECATED   => 'User Deprecated',
				];

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
				if ( ! ( error_reporting() & $errno ) ) {
					return;
				}

				// the core matomo code can have uses of deprecated functions
				if ( E_USER_DEPRECATED !== $errno ) {
					$errtype = isset( $error_names[ $errno ] ) ? $error_names[ $errno ] : $errno;
					throw new \Exception( "[$errtype] $errstr in $errfile:$errline" );
				}
			}
		);

		$this->matomo_fixture = new MatomoUnit_Matomo_Fixture();
		$this->matomo_fixture->set_up( $this );
	}

	public function tearDown(): void {
		$this->matomo_fixture->tear_down();
		parent::tearDown();
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

	protected function assert_tracking_response( $tracking_response ) {
		$this->assertEquals( $this->get_expected_tracking_response(), $tracking_response );
	}

	protected function assert_not_tracking_response( $tracking_response ) {
		$this->assertNotEquals( $this->get_expected_tracking_response(), $tracking_response );
	}

	private function get_expected_tracking_response() {
		$trans_gif_64 = 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$expected_response = base64_decode( $trans_gif_64 );
		return $expected_response;
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

	protected function make_local_tracker( $date_time, $set_auth = false ) {
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
		$tracker->setPlugins( true, true, false );

		if ( $set_auth ) {
			$this->create_user_for_tracker();
			$tracker->setTokenAuth( 'testtesttest' ); // ignored
			$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->tracker_user );
			$tracker->setExtraServerVar( 'PHP_AUTH_PW', $this->application_password );
		}

		return $tracker;
	}

	protected function create_set_super_admin() {
		return $this->matomo_fixture->create_set_super_admin( self::factory() );
	}

	protected function create_user_for_tracker() {
		if ( isset( $this->tracker_user ) ) {
			return;
		}

		$user_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user_id );
		$user_login = wp_get_current_user()->user_login;

		$this->assertNotEmpty( $user_login );

		$sync = new \WpMatomo\User\Sync();
		$sync->sync_all();

		$user_model = new \Piwik\Plugins\UsersManager\Model();
		$this->assertNotEmpty( $user_model->getUser( \WpMatomo\User::get_matomo_user_login( $user_id ) ) );

		\Piwik\Tracker\TrackerConfig::setConfigValue( 'allow_wp_app_password_auth', 1 );

		// add application password
		// NOTE: we don't skip all the tests here to make sure the auth code
		// works when application password functions do not exist
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '>=' ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_true' );

			$request = new WP_REST_Request( 'POST', '/wp/v2/users/me/application-passwords' );
			$request->set_param( 'name', 'test' );
			$response = rest_get_server()->dispatch( $request );

			$response_data        = $response->get_data();
			$application_password = $response_data['password'];

			$this->application_password = $application_password;
		}

		$this->tracker_user = $user_login;
	}

	protected function skip_if_old_wordpress() {
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '<' ) ) {
			$this->markTestSkipped( 'WordPress version does not support application passwords.' );
		}
	}
}
