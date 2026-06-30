<?php
/**
 * @package matomo
 */

use WpMatomo\Settings;
use WpMatomo\Site\Sync;

class SiteSyncConfigTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Sync\SyncConfig
	 */
	private $sync_config;

	public function setUp(): void {
		parent::setUp();

		$settings = new Settings();
		if ( is_multisite() ) {
			$settings->set_assume_is_network_enabled_in_tests( true );
		}
		$this->sync_config = new Sync\SyncConfig( $settings );
	}

	public function test_get_config_value_no_value_set() {
		$val = $this->sync_config->get_config_value( 'Geenral', 'foo' );
		$this->assertNull( $val );
	}

	public function test_set_config_value_get_config_value_string() {
		$this->sync_config->set_config_value( 'General', 'foo', 'bar' );

		$val = $this->sync_config->get_config_value( 'General', 'foo' );
		$this->assertSame( 'bar', $val );
	}

	public function test_set_config_value_get_config_value_array() {
		$this->sync_config->set_config_value(
			'General',
			'foo',
			array(
				'baz',
				'bar',
			)
		);

		$val = $this->sync_config->get_config_value( 'General', 'foo' );
		$this->assertEquals( array( 'baz', 'bar' ), $val );
	}
}
