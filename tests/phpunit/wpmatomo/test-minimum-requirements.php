<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\MinimumRequirements;

require_once __DIR__ . '/../framework/traits/test-matomo-mock-db-server-test.php';

/**
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */
class MinimumRequirementsTest extends MatomoUnit_TestCase {

	use MatomoMockDbServerTest;

	/**
	 * @var MinimumRequirements
	 */
	private $requirements;

	public function setUp(): void {
		parent::setUp();

		$this->remember_global_wpdb();

		$this->requirements = new MinimumRequirements();
	}

	public function tearDown(): void {
		$this->restore_global_wpdb();

		parent::tearDown();
	}

	public function test_get_db_server_returns_empty_when_wpdb_is_not_set() {
		$GLOBALS['wpdb'] = null;

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '',
			],
			$this->requirements->get_db_server()
		);
	}

	public function test_get_db_server_returns_empty_when_not_mysql() {
		$fake            = $this->get_mock_wpdb();
		$fake->is_mysql  = false;
		$fake->version   = '8.0.32';
		$GLOBALS['wpdb'] = $fake;

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '',
			],
			$this->requirements->get_db_server()
		);
	}

	public function test_get_db_server_detects_mysql() {
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '8.0.32',
			],
			$this->requirements->get_db_server()
		);
	}

	public function test_get_db_server_falls_back_to_db_version_when_server_info_unavailable() {
		$fake            = $this->get_mock_wpdb_without_server_info();
		$fake->version   = '5.7.40';
		$GLOBALS['wpdb'] = $fake;

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '5.7.40',
			],
			$this->requirements->get_db_server()
		);
	}

	public function test_get_db_server_is_only_detected_once() {
		$this->set_fake_db( '8.0.32', '8.0.32' );
		$this->requirements->get_db_server();

		// changing the db afterwards must not change the already detected result
		$this->set_fake_db( '5.7.40', '5.7.40' );

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '8.0.32',
			],
			$this->requirements->get_db_server()
		);
	}

	/**
	 * @dataProvider get_mariadb_server_info_provider
	 */
	public function test_get_db_server_detects_mariadb( $server_info, $db_version, $expected_version ) {
		$this->set_fake_db( $server_info, $db_version );

		$this->assertSame(
			[
				'is_mariadb' => true,
				'version'    => $expected_version,
			],
			$this->requirements->get_db_server()
		);
	}

	public function get_mariadb_server_info_provider() {
		return [
			// with 5.5.5 compatibility prefix
			[ '5.5.5-10.6.12-MariaDB-1:10.6.12+maria~ubu2004', '5.5.5', '10.6.12' ],

			// without compatibility prefix
			[ '10.3.39-MariaDB', '10.3.39', '10.3.39' ],

			// older mariadb
			[ '5.5.5-10.2.44-MariaDB', '5.5.5', '10.2.44' ],
		];
	}

	public function test_get_unmet_requirements_flags_outdated_mysql() {
		$this->set_fake_db( '5.7.40', '5.7.40' );

		$expected = [ $this->mysql_requirement_message( '5.7.40' ) ];

		$this->assertSame( $expected, $this->requirements->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_flags_outdated_mariadb() {
		$this->set_fake_db( '5.5.5-10.3.39-MariaDB', '5.5.5' );

		$expected = [ $this->mariadb_requirement_message( '10.3.39' ) ];

		$this->assertSame( $expected, $this->requirements->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_supported_mysql() {
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$this->assertSame( [], $this->requirements->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_supported_mariadb() {
		$this->set_fake_db( '5.5.5-10.6.12-MariaDB', '5.5.5' );

		$this->assertSame( [], $this->requirements->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_database_when_version_unknown() {
		$fake            = $this->get_mock_wpdb();
		$fake->is_mysql  = false;
		$GLOBALS['wpdb'] = $fake;

		$this->assertSame( [], $this->requirements->get_unmet_requirements( '8.5' ) );
	}

	/**
	 * @dataProvider get_php_version_test_data_for_get_unmet_requirements
	 */
	public function test_get_unmet_requirements_checks_the_given_php_version( $php_version, $is_unmet ) {
		// use a supported database so only the PHP version can be reported.
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$expected = $is_unmet ? [ $this->php_requirement_message( $php_version ) ] : [];

		$this->assertSame( $expected, $this->requirements->get_unmet_requirements( $php_version ) );
	}

	public function get_php_version_test_data_for_get_unmet_requirements() {
		return [
			[ '7.2.5', true ],
			[ '7.4.33', true ],
			[ '8.0.30', true ],
			[ '8.0.99', true ],
			[ '8.1', false ],
			[ '8.3.0', false ],
		];
	}

	public function test_get_unmet_requirements_reports_both_php_and_database() {
		$this->set_fake_db( '5.7.40', '5.7.40' );

		$expected = [
			$this->php_requirement_message( '7.4.33' ),
			$this->mysql_requirement_message( '5.7.40' ),
		];

		$this->assertSame( $expected, $this->requirements->get_unmet_requirements( '7.4.33' ) );
	}

	/**
	 * @dataProvider get_does_plugin_version_require_new_minimums_provider
	 */
	public function test_does_plugin_version_require_new_minimums( $version, $expected ) {
		$this->assertSame( $expected, $this->requirements->does_plugin_version_require_new_minimums( $version ) );
	}

	public function get_does_plugin_version_require_new_minimums_provider() {
		return [
			[ '', false ],
			[ null, false ],
			[ '5.12.2', false ],
			[ '5.99.99', false ],
			[ '6.0.0-b1', false ],
			[ '6.0.0', true ],
			[ '6.0.1', true ],
			[ '7.0.0', true ],
		];
	}

	public function test_can_this_system_run_plugin_version_ignores_older_versions_on_an_unsupported_server() {
		$this->set_fake_db( '5.7.40', '5.7.40' );

		$this->assertTrue( $this->requirements->can_this_system_run_plugin_version( '5.12.2', '7.4.33' ) );
	}

	public function test_can_this_system_run_plugin_version_is_false_when_the_new_version_cannot_run() {
		$this->set_fake_db( '5.7.40', '5.7.40' );

		$this->assertFalse( $this->requirements->can_this_system_run_plugin_version( '6.0.0', '7.4.33' ) );
	}

	public function test_can_this_system_run_plugin_version_is_true_when_the_server_is_supported() {
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$this->assertTrue( $this->requirements->can_this_system_run_plugin_version( '6.0.0', '8.1.0' ) );
	}

	private function php_requirement_message( $version ) {
		return sprintf(
			'PHP %1$s or higher is required (you are currently using PHP %2$s).',
			MinimumRequirements::REQUIRED_PHP_VERSION,
			$version
		);
	}

	private function mysql_requirement_message( $version ) {
		return sprintf(
			'MySQL %1$s or higher is required (you are currently using MySQL %2$s).',
			MinimumRequirements::REQUIRED_MYSQL_VERSION,
			$version
		);
	}

	private function mariadb_requirement_message( $version ) {
		return sprintf(
			'MariaDB %1$s or higher is required (you are currently using MariaDB %2$s).',
			MinimumRequirements::REQUIRED_MARIADB_VERSION,
			$version
		);
	}
}
