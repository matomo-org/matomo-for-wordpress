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

	/**
	 * @dataProvider get_test_data_for_matomo_rel_path
	 */
	public function test_matomo_rel_path( $to_dir, $from_dir, $expected_rel_path ) {
		$actual = matomo_rel_path( $to_dir, $from_dir );
		$this->assertEquals( $expected_rel_path, $actual );
	}

	public function get_test_data_for_matomo_rel_path() {
		return [
			['/var/www/html/wordpress', '/var/www/html/wordpress/matomo/path', '../../'],
			['/var/www/html/wordpress/matomo/path', '/var/www/html/wordpress', 'matomo/path'],
			['/var/www/html/wordpress', '/var/www/html/wordpress', ''],
			['/var/www/html/wordpress', '/var/www/matomo/for/wordpress', '../../matomo/for/wordpress'],
			['/var/www/matomo/for/wordpress', '/var/www/html/wordpress', '../../../html/wordpress'],
		];
	}
}
