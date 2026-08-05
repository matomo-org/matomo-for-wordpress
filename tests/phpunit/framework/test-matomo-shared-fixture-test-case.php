<?php
/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 */

require_once __DIR__ . '/fixture/test-matomo-fixture.php';

use WpMatomo\Bootstrap;

/**
 * Test case that sets up Matomo/WP once in setUpBeforeClass and creates
 * a DB snapshot to restore to this blank state in setUp(). Allows avoiding
 * a full install/uninstall per test.
 */
class MatomoAnalytics_SharedFixture_TestCase extends MatomoUnit_TestCase {

	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	private static $matomo_fixture;

	private static $saved_snapshot;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		self::$matomo_fixture = new MatomoUnit_Matomo_Fixture();
		self::$matomo_fixture->set_up();

		// force the parent's per-test restore to target THIS class's installed database:
		// emptying the snapshot makes the first test's start_transaction() re-capture it now,
		// with Matomo installed. previous value saved for other classes.
		self::$saved_snapshot     = self::$initial_table_data;
		self::$initial_table_data = [];
	}

	public static function tearDownAfterClass(): void {
		self::$matomo_fixture->tear_down();
		self::$matomo_fixture = null;

		// restore original snapshot
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
}
