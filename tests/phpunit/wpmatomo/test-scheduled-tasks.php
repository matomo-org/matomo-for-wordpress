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

class ScheduledTasksTest extends MatomoAnalytics_TestCase {

	/**
	 * @var ScheduledTasks
	 */
	private $tasks;
	
	protected $disable_temp_tables = true;

	public function setUp() {
		parent::setUp();

		$settings    = new Settings();
		$this->tasks = new ScheduledTasks( $settings );
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
		$this->tasks->sync();
	}

	public function test_archive_does_not_fail() {
		$this->assertEquals(array(), $this->tasks->archive());
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
		$this->tasks->perform_update();
	}


}
