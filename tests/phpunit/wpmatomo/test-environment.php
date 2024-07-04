<?php
/**
 * @package matomo
 */
class EnvironmentTest extends MatomoUnit_TestCase {

	public function test_wp_debug_is_on() {
		$this->assertTrue( defined( 'WP_DEBUG' ) && WP_DEBUG );
	}
}
