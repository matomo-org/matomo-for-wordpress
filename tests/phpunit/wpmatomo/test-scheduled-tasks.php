<?php
/**
 * @package matomo
 */

use Piwik\Container\StaticContainer;
use Piwik\Plugins\GeoIp2\LocationProvider\GeoIp2\Php;
use Piwik\Plugins\SitesManager\Model as SitesModel;
use Piwik\Plugins\UserCountry\LocationProvider;
use Piwik\Plugins\UsersManager\Model as UsersModel;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Paths;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;

/**
 * Don't need remote access
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
 */
class ScheduledTasksTest extends MatomoAnalytics_TestCase {

	/**
	 * @var ScheduledTasks
	 */
	private $tasks;

	protected $disable_temp_tables = true;
	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings = new Settings();
		$this->tasks    = new ScheduledTasks( $this->settings );
		$this->tasks->schedule();
	}

	public function test_schedule_schedules_events() {
		foreach ( $this->tasks->get_all_events() as $event => $config ) {
			$this->assertNotEmpty( wp_next_scheduled( $event ) );
		}
	}

	public function test_uninstall_unschedules_events() {
		$this->tasks->uninstall();
		foreach ( $this->tasks->get_all_events() as $event => $config ) {
			$this->assertEmpty( wp_next_scheduled( $event ) );
		}
	}

	public function test_sync_does_not_fail() {
		try {
			$this->tasks->sync();
			$this->assertTrue( true );
		} catch ( Exception $e ) {
			$this->assertFalse( true );
		}
	}

	public function test_disable_add_handler_wontfail_when_addhandler_enabled() {
		$this->assertFalse( $this->settings->should_disable_addhandler() );
		$this->tasks->disable_add_handler();
	}

	public function test_disable_add_handler_wontfail_when_addhandler_disabled() {
		$this->assertFalse( $this->settings->should_disable_addhandler() );
		$this->settings->force_disable_addhandler = true;
		$this->tasks->disable_add_handler();
		$filename_to_check = dirname( MATOMO_ANALYTICS_FILE ) . '/.htaccess';
		$this->assertStringContainsString( '# AddHandler', file_get_contents( $filename_to_check ) );
		$undo = true;
		$this->tasks->disable_add_handler( $undo );
		$this->assertStringNotContainsString( '# AddHandler', file_get_contents( $filename_to_check ) );
		$this->assertStringContainsString( 'AddHandler', file_get_contents( $filename_to_check ) );
		$this->settings->force_disable_addhandler = false;
	}

	public function test_archive_does_not_fail() {
		$this->assertEquals( array(), $this->tasks->archive() );
	}

	public function test_set_last_time_before_cron() {
		$this->assertFalse( $this->tasks->get_last_time_before_cron( 'matomo-event' ) );

		$this->tasks->set_last_time_before_cron( 'matomo-event', '454545454' );
		$this->assertEquals( '454545454', $this->tasks->get_last_time_before_cron( 'matomo-event' ) );

		$this->assertFalse( $this->tasks->get_last_time_before_cron( 'matomo-event-foo' ) );
	}

	public function test_set_last_time_after_cron() {
		$this->assertFalse( $this->tasks->get_last_time_after_cron( 'matomo-event' ) );

		$this->tasks->set_last_time_after_cron( 'matomo-event', '454545454' );
		$this->assertEquals( '454545454', $this->tasks->get_last_time_after_cron( 'matomo-event' ) );

		$this->assertFalse( $this->tasks->get_last_time_after_cron( 'matomo-event-foo' ) );
	}

	/**
	 * @group external-http
	 */
	public function test_update_geo_ip2_db_does_not_fail() {
		$this->tasks->update_geo_ip2_db();

		$this->assertFileExists( StaticContainer::get( 'path.geoip2' ) . 'DBIP-City.mmdb' );
		$this->assertEquals( Php::ID, LocationProvider::getCurrentProviderId() );
		$this->assertTrue( LocationProvider::getCurrentProvider()->isWorking() );
	}

	public function test_perform_update_does_not_fail() {
		$success = $this->tasks->perform_update();
		$this->assertTrue( $success );
	}

	/**
	 * @provideContainerConfig getContainerConfigForGeoIpFail
	 */
	public function test_geoip_update_reschedules_to_tomorrow_on_failure() {
		// remove event scheduled during install
		wp_unschedule_event( wp_next_scheduled( ScheduledTasks::EVENT_GEOIP ), ScheduledTasks::EVENT_GEOIP );
		$tasks = $this->get_tasks_for_event( ScheduledTasks::EVENT_GEOIP );
		$this->assertEmpty( $tasks );

		// schedule the next event 10 days in the future
		wp_schedule_single_event( time() + 10 * 24 * 60 * 60, ScheduledTasks::EVENT_GEOIP );

		$this->tasks->update_geo_ip2_db();

		$tasks = $this->get_tasks_for_event( ScheduledTasks::EVENT_GEOIP );
		$this->assertNotEmpty( $tasks );

		$earliest_task_time = key( $tasks );
		$time_diff          = $earliest_task_time - time();

		$seconds_in_a_day = 60 * 60 * 24;
		$this->assertGreaterThanOrEqual( $seconds_in_a_day - 5, $time_diff );
		$this->assertLessThanOrEqual( $seconds_in_a_day + 5, $time_diff );

		$task_failures = $this->tasks->get_recorded_task_failures();
		$this->assertEquals( [ 'update_geoip2' ], array_keys( $task_failures ) );
	}

	/**
	 * @provideContainerConfig getContainerConfigForGeoIpFail
	 */
	public function test_geoip_update_does_not_reschedule_if_already_scheduled_within_two_days() {
		// remove event scheduled during install
		wp_unschedule_event( wp_next_scheduled( ScheduledTasks::EVENT_GEOIP ), ScheduledTasks::EVENT_GEOIP );
		$tasks = $this->get_tasks_for_event( ScheduledTasks::EVENT_GEOIP );
		$this->assertEmpty( $tasks );

		// schedule the next event 10 days in the future
		wp_schedule_single_event( time() + 1.5 * 24 * 60 * 60, ScheduledTasks::EVENT_GEOIP );

		$pre_scheduled_time = wp_next_scheduled( ScheduledTasks::EVENT_GEOIP );

		$this->tasks->update_geo_ip2_db();

		$tasks = $this->get_tasks_for_event( ScheduledTasks::EVENT_GEOIP );
		$this->assertCount( 1, $tasks );

		$earliest_task_time = key( $tasks );
		$this->assertEquals( $pre_scheduled_time, $earliest_task_time );

		$task_failures = $this->tasks->get_recorded_task_failures();
		$this->assertEquals( [ 'update_geoip2' ], array_keys( $task_failures ) );
	}

	public function getContainerConfigForGeoIpFail() {
		return [
			\Piwik\Plugins\GeoIp2\GeoIP2AutoUpdater::class => function () {
				// phpcs:ignore WordPress.Classes.ClassInstantiation.MissingParenthesis
				return new class extends \Piwik\Plugins\GeoIp2\GeoIP2AutoUpdater {
					public function update() {
						throw new \Exception( 'forced error' );
					}
				};
			},
		];
	}

	private function get_tasks_for_event( $event_name ) {
		$tasks = _get_cron_array();
		$tasks = array_filter(
			$tasks,
			function ( $tasks_for_time ) use ( $event_name ) {
				return ! empty( $tasks_for_time[ $event_name ] );
			}
		);
		return $tasks;
	}
}
