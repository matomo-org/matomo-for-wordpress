<?php
/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 */

require_once __DIR__ . '/fixture/test-matomo-fixture.php';

use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Container\StaticContainer;
use WpMatomo\Bootstrap;
use WpMatomo\Paths;

/**
 * Test case that sets up Matomo/WP once and creates a DB snapshot to restore to this blank state
 * in setUp(). Allows avoiding a full install/uninstall per test.
 *
 * Only the first class using this fixture in a process runs the Matomo installer. It is torn down
 * at the end of every class as usual, so nothing leaks into classes that do not use the fixture;
 * later classes rebuild it from the snapshot instead, which is far faster than installing.
 */
class MatomoAnalytics_SharedFixture_TestCase extends MatomoUnit_TestCase {

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	private static $matomo_fixture;

	private static $saved_snapshot;

	/**
	 * Snapshot of WordPress with Matomo installed, kept across classes.
	 *
	 * @var array
	 */
	private static $shared_matomo_snapshot = [];

	/**
	 * The Matomo upload dir as it looks once installed, as relative path => contents. The database
	 * snapshot cannot cover this, and uninstalling deletes the whole directory, so we need to
	 * keep track and restore it in setUp.
	 *
	 * @var array
	 */
	private static $shared_matomo_files = [];

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// the parent's per-test restore has to target the database with Matomo installed, so swap
		// the snapshot out and put the original back in tearDownAfterClass() for the classes that
		// do not use this fixture.
		self::$saved_snapshot = self::$initial_table_data;

		if ( empty( self::$shared_matomo_snapshot ) ) { // snapshot empty, so build it
			self::$matomo_fixture = new MatomoUnit_Matomo_Fixture();
			self::$matomo_fixture->set_up();

			self::$shared_matomo_snapshot = self::capture_db_snapshot();
			self::$shared_matomo_files    = self::capture_matomo_upload_dir();
		} else { // snapshot full, so re-use it
			self::$initial_table_data = self::$shared_matomo_snapshot;

			self::restore_shared_fixture();
		}

		self::$initial_table_data = self::$shared_matomo_snapshot;
	}

	public static function tearDownAfterClass(): void {
		( new MatomoUnit_Matomo_Fixture() )->tear_down();
		self::$matomo_fixture = null;

		self::$initial_table_data = self::$saved_snapshot;
		self::$saved_snapshot     = null;

		parent::tearDownAfterClass();
	}

	public function setUp(): void {
		parent::setUp();

		// match MatomoAnalytics_TestCase: surface PHP warnings/notices as exceptions.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
		error_reporting( E_ALL );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
		set_error_handler(
			function ( $errno, $errstr, $errfile, $errline ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
				if ( ! ( error_reporting() & $errno ) ) {
					return;
				}
				if ( E_USER_DEPRECATED !== $errno ) {
					throw new \Exception( "[$errno] $errstr in $errfile:$errline" );
				}
			}
		);

		// reset all in memory caches
		Bootstrap::destroy_bootstrapped_environment();
		Bootstrap::do_bootstrap();

		global $wp_roles;
		if ( $wp_roles instanceof WP_Roles ) {
			$wp_roles->init_roles();
		}
	}

	public function tearDown(): void {
		restore_error_handler();

		parent::tearDown();
	}

	private static function restore_shared_fixture() {
		self::restore_matomo_upload_dir();

		clearstatcache();
		Bootstrap::set_not_bootstrapped();

		self::restore_db_snapshot();

		// the rows are back, but the WordPress cache still has the old data, so clear it
		wp_cache_flush();

		global $wp_roles;
		if ( $wp_roles instanceof WP_Roles ) {
			$wp_roles->for_site();
		}

		if ( class_exists( StaticContainer::class ) && StaticContainer::getContainer() ) {
			StaticContainer::get( GlobalSettingsProvider::class )->reload();
		}
	}

	/**
	 * @return array relative path => contents
	 */
	private static function capture_matomo_upload_dir() {
		$base = ( new Paths() )->get_upload_base_dir();
		if ( ! is_dir( $base ) ) {
			return [];
		}

		$files = [];

		$iterator = new RecursiveIteratorIterator(
			// RecursiveDirectoryIterator rather than glob(), which would skip the .htaccess files
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			$relative = substr( $file->getPathname(), strlen( $base ) + 1 );
			if ( strpos( $relative, 'tmp/' ) === 0 ) {
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_get_contents
			$files[ $relative ] = file_get_contents( $file->getPathname() );
		}

		return $files;
	}

	private static function restore_matomo_upload_dir() {
		$base = ( new Paths() )->get_upload_base_dir();

		foreach ( self::$shared_matomo_files as $relative => $contents ) {
			$path = $base . '/' . $relative;

			wp_mkdir_p( dirname( $path ) );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $path, $contents );
		}
	}
}
