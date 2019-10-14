<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Settings;
use WpMatomo\Updater;
use WpMatomo\User;

class UpdaterTest extends MatomoAnalytics_TestCase {

	/**
	 * @var User
	 */
	private $updater;

	public function setUp() {
		parent::setUp();

		$this->updater = new Updater( new Settings() );
	}

	public function test_update_does_not_fail() {
		$this->updater->update();
	}

	public function test_update_if_needed() {
		$keys = $this->updater->update_if_needed();
		$this->assertSame( array( 'matomo-plugin-version-matomo' ), $keys );

		$pluginData = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$this->assertSame( $pluginData['Version'], get_option( $keys[0] ) );

		// does not execute the update again
		$keys = $this->updater->update_if_needed();
		$this->assertSame( array(), $keys );
	}

}
