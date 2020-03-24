<?php
/**
 * Test matomo.php.
 *
 * @package matomo
 */

class MatomoTest extends MatomoUnit_TestCase {

	public function test_matomo_has_compatible_content_dir() {
		$this->assertTrue( matomo_has_compatible_content_dir() );
	}

}
