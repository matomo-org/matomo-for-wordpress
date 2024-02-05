<?php
/**
 * @package matomo
 */

use WpMatomo\Settings;
use WpMatomo\Updater;
use WpMatomo\User;

class UpdaterTest extends MatomoAnalytics_TestCase {


	/**
	 * @var User
	 */
	private $updater;

	public function setUp(): void {
		parent::setUp();

		$this->updater = new Updater( new Settings() );
	}

	public function test_lock_unlock() {
		$this->assertFalse( $this->updater->is_upgrade_in_progress() );

		$locked = Updater::lock();
		$this->assertTrue( $locked );

		$this->assertTrue( $this->updater->is_upgrade_in_progress() );

		// cannot lock it again
		$locked = Updater::lock();
		$this->assertFalse( $locked );

		// still in progress
		$this->assertTrue( $this->updater->is_upgrade_in_progress() );

		// when unlocking then we can lock it again
		$unlocked = Updater::unlock();
		$this->assertTrue( $unlocked );

		// cannot unlock again
		$unlocked = Updater::unlock();
		$this->assertFalse( $unlocked );

		// not in progress anymore
		$this->assertFalse( $this->updater->is_upgrade_in_progress() );

		// can lock it again now
		$locked = Updater::lock();
		$this->assertTrue( $locked );
		// make sure to unlock
		Updater::unlock();
	}

	public function test_load_plugin_functions_should_always_work() {
		$this->assertTrue( $this->updater->load_plugin_functions() );
	}

	public function test_default_has_no_outstanding_plugin_updates() {
		$required_updates = $this->updater->get_plugins_requiring_update();

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$this->assertSame( array( 'matomo-plugin-version-matomo' => $plugin_data['Version'] ), $required_updates );
	}

	public function test_update_if_needed() {
		$this->assertNotEmpty( $this->updater->get_plugins_requiring_update() );

		$keys = $this->updater->update_if_needed();
		$this->assertSame( array( 'matomo-plugin-version-matomo' ), $keys );

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$this->assertSame( $plugin_data['Version'], get_option( $keys[0] ) );

		$this->assertSame( array(), $this->updater->get_plugins_requiring_update() );

		// does not execute the update again
		$keys = $this->updater->update_if_needed();
		$this->assertSame( array(), $keys );
	}

	public function test_update_does_not_fail() {
		$this->updater->update();
		// for the phpunit warning
		$this->assertTrue( true );
	}

}
