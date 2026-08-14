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
require_once __DIR__ . '/traits/test-matomo-analytics-test.php';

/**
 * Piwik constants
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
class MatomoAnalytics_TestCase extends MatomoUnit_TestCase {

	use MatomoAnalyticsTest;

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	protected $matomo_fixture;

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
}
