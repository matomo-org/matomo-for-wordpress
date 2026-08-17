<?php
/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */

require_once __DIR__ . '/fixture/test-matomo-fixture.php';
require_once __DIR__ . '/traits/test-matomo-analytics-test.php';

use Piwik\Application\Kernel\GlobalSettingsProvider;
use Piwik\Container\StaticContainer;
use WpMatomo\Bootstrap;
use WpMatomo\Paths;
use WpMatomo\Report\Metadata;

/**
 * Test case that sets up Matomo/WP once and creates a DB snapshot to restore to this blank state
 * in setUp(). Allows avoiding a full install/uninstall per test.
 *
 * Only the first class using this fixture in a process runs the Matomo installer. It is torn down
 * at the end of every class as usual, so nothing leaks into classes that do not use the fixture;
 * later classes rebuild it from the snapshot instead, which is far faster than installing.
 */
class MatomoAnalytics_SharedFixture_TestCase extends MatomoUnit_TestCase {

	use MatomoAnalyticsTest;

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
			self::$matomo_fixture->set_up( null, ! static::class_has_no_test_mode_test() );

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

		// a @runInSeparateProcess test tears its class down in the child process, so the parent
		// never runs the tearDown() that would have put the tables back
		self::restore_db_snapshot_if_tables_are_missing();

		// the parent restores the database in tearDown(), but a test that uninstalls Matomo also
		// deletes config.ini.php and the rest of the upload dir, so those have to come back too
		self::restore_matomo_upload_dir();

		$fixture = new MatomoUnit_Matomo_Fixture();

		// the installer does not run per test here, but the annotations it reads still have to be
		// honoured before anything bootstraps
		$fixture->apply_test_annotations( $this );

		// reset all in memory caches
		Bootstrap::destroy_bootstrapped_environment();
		Metadata::clear_cache();
		Bootstrap::do_bootstrap();

		$fixture->register_hooks();

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}

		global $wp_roles;
		if ( $wp_roles instanceof WP_Roles ) {
			$wp_roles->init_roles();
		}
	}

	public function tearDown(): void {
		restore_error_handler();

		parent::tearDown();
	}

	private static function class_has_no_test_mode_test() {
		foreach ( get_class_methods( static::class ) as $method ) {
			if ( strpos( $method, 'test' ) !== 0 ) {
				continue;
			}

			$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( static::class, $method );
			if ( ! empty( $annotations['method']['noTestMode'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Restoring the snapshot on every setUp() would roughly double what the per test restore costs,
	 * so only do it when something really is gone - which in practice means Matomo's tables were
	 * dropped by an uninstall this process did not get to clean up after.
	 */
	private static function restore_db_snapshot_if_tables_are_missing() {
		global $wpdb;

		$existing = [];
		foreach ( $wpdb->get_results( 'SHOW TABLES', ARRAY_A ) as $row ) {
			$existing[ reset( $row ) ] = true;
		}

		foreach ( array_keys( self::$initial_table_data ) as $table ) {
			if ( isset( $existing[ $table ] ) ) {
				continue;
			}

			self::restore_db_snapshot();

			// the rows are back, but the WordPress cache still has the old data, so clear it
			wp_cache_flush();

			return;
		}
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

		self::delete_files_not_in_snapshot( $base );

		foreach ( self::$shared_matomo_files as $relative => $contents ) {
			$path = $base . '/' . $relative;

			wp_mkdir_p( dirname( $path ) );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $path, $contents );
		}

		clearstatcache();
	}

	/**
	 * A reinstall would leave the upload dir with only the files the installer creates, so anything
	 * a test wrote there has to go, or it is visible to the next test.
	 *
	 * @param string $base
	 */
	private static function delete_files_not_in_snapshot( $base ) {
		if ( ! is_dir( $base ) ) {
			return;
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file ) {
			$relative = substr( $file->getPathname(), strlen( $base ) + 1 );

			// the tmp dir is excluded from the snapshot, so it is not ours to clean up
			if ( 'tmp' === $relative || strpos( $relative, 'tmp/' ) === 0 ) {
				continue;
			}

			if ( $file->isDir() ) {
				if ( ! ( new FilesystemIterator( $file->getPathname() ) )->valid() ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
					rmdir( $file->getPathname() );
				}
				continue;
			}

			if ( ! isset( self::$shared_matomo_files[ $relative ] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $file->getPathname() );
			}
		}
	}
}
