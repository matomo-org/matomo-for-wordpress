<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Db\Settings;

class DbInfoTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Settings
	 */
	private $db;

	public function setUp() {
		parent::setUp();
		$this->db = new Settings();
	}

	public function test_prefix_table_name() {
		$this->assertEquals( 'wptests_matomo_site', $this->db->prefix_table_name( 'site' ) );
	}


}
