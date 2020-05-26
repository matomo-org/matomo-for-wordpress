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

	public function setUp() {
		parent::setUp();

		$settings   = new Settings();
		if (is_multisite()) {
			$settings->set_assume_is_network_enabled_in_tests(true);
		}
		$this->sync_config = new Sync\SyncConfig( $settings );
	}

	public function test_get_config_value_no_value_set() {
		$val = $this->sync_config->get_config_value('Geenral', 'foo');
		$this->assertNull($val);
	}

	public function test_set_config_value_get_config_value_string() {
		$this->sync_config->set_config_value('General', 'foo', 'bar');

		$val = $this->sync_config->get_config_value('General', 'foo');
		$this->assertSame('bar', $val);
	}

	public function test_set_config_value_get_config_value_array() {
		$this->sync_config->set_config_value('General', 'foo', array(
			'baz',
			'bar'
		));

		$val = $this->sync_config->get_config_value('General', 'foo');
		$this->assertEquals(['baz', 'bar'], $val);
	}

	public function test_sync_config_for_current_site_when_no_config_set() {
		$sync = $this->sync_config->sync_config_for_current_site();
		$this->assertNull($sync);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_config_for_current_site_when_config_set() {

		$this->sync_config->set_config_value('General', 'foo', array(
			'baz',
			'bar'
		));
		$general = \Piwik\Config::getInstance()->General;
		$this->assertTrue(empty($general['foo']));

		$this->sync_config->sync_config_for_current_site();

		$general = \Piwik\Config::getInstance()->General;
		$this->assertEquals(['baz', 'bar'], $general['foo']);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_config_for_current_site_when_multiple_values() {

		$this->sync_config->set_config_value('General', 'foo', array('baz','bar'));
		$this->sync_config->set_config_value('NewCategory', 'bar', 'baz');
		$this->sync_config->set_config_value('NewCategory', 'hello', 'world');

		$general = \Piwik\Config::getInstance()->General;
		$this->assertTrue(empty($general['foo']));
		$newCategory = \Piwik\Config::getInstance()->NewCategory;
		$this->assertEmpty($newCategory);

		$this->sync_config->sync_config_for_current_site();

		$general = \Piwik\Config::getInstance()->General;
		$this->assertEquals(['baz', 'bar'], $general['foo']);

		$newCategory = \Piwik\Config::getInstance()->NewCategory;
		$this->assertEquals(['bar' => 'baz', 'hello' => 'world'], $newCategory);

		// now we change one key
		$this->sync_config->set_config_value('NewCategory', 'bar', '');
		$this->sync_config->sync_config_for_current_site();

		$newCategory = \Piwik\Config::getInstance()->NewCategory;
		$this->assertEquals(['bar' => '', 'hello' => 'world'], $newCategory);

	}

}
