<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\MinimumRequirementsNotice;

/**
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
 * phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */
class MinimumRequirementsNoticeTest extends MatomoUnit_TestCase {

	/**
	 * @var MinimumRequirementsNotice
	 */
	private $notice;

	/**
	 * @var mixed
	 */
	private $original_global_wpdb;

	public function setUp(): void {
		parent::setUp();

		$this->original_global_wpdb = isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb'] : null;

		$this->notice = new MinimumRequirementsNotice();

		unset( $_GET['page'] );
	}

	public function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_global_wpdb;

		unset( $_GET['page'] );

		parent::tearDown();
	}

	public function test_get_db_server_returns_empty_when_wpdb_is_not_set() {
		$GLOBALS['wpdb'] = null;

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '',
			],
			$this->notice->get_db_server()
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
			$this->notice->get_db_server()
		);
	}

	public function test_get_db_server_detects_mysql() {
		$fake              = $this->get_mock_wpdb();
		$fake->server_info = '8.0.32';
		$fake->version     = '8.0.32';
		$GLOBALS['wpdb']   = $fake;

		$this->assertSame(
			[
				'is_mariadb' => false,
				'version'    => '8.0.32',
			],
			$this->notice->get_db_server()
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
			$this->notice->get_db_server()
		);
	}

	/**
	 * @dataProvider get_mariadb_server_info_provider
	 */
	public function test_get_db_server_detects_mariadb( $server_info, $db_version, $expected_version ) {
		$fake              = $this->get_mock_wpdb();
		$fake->server_info = $server_info;
		$fake->version     = $db_version;
		$GLOBALS['wpdb']   = $fake;

		$this->assertSame(
			[
				'is_mariadb' => true,
				'version'    => $expected_version,
			],
			$this->notice->get_db_server()
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

		$this->assertSame( $expected, $this->notice->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_flags_outdated_mariadb() {
		$this->set_fake_db( '5.5.5-10.3.39-MariaDB', '5.5.5' );

		$expected = [ $this->mariadb_requirement_message( '10.3.39' ) ];

		$this->assertSame( $expected, $this->notice->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_supported_mysql() {
		$this->set_fake_db( '8.0.32', '8.0.32' );
		$this->assertSame( [], $this->notice->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_supported_mariadb() {
		$this->set_fake_db( '5.5.5-10.6.12-MariaDB', '5.5.5' );

		$this->assertSame( [], $this->notice->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_does_not_flag_database_when_version_unknown() {
		$fake            = $this->get_mock_wpdb();
		$fake->is_mysql  = false;
		$GLOBALS['wpdb'] = $fake;

		$this->assertSame( [], $this->notice->get_unmet_requirements( '8.5' ) );
	}

	public function test_get_unmet_requirements_is_empty_when_everything_is_supported() {
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$this->assertSame( [], $this->notice->get_unmet_requirements( '8.5' ) );
	}

	/**
	 * @dataProvider get_php_version_test_data_for_get_unmet_requirements
	 */
	public function test_get_unmet_requirements_checks_the_given_php_version( $php_version, $is_unmet ) {
		// use a supported database so only the PHP version can be reported.
		$this->set_fake_db( '8.0.32', '8.0.32' );

		$expected = $is_unmet ? [ $this->php_requirement_message( $php_version ) ] : [];

		$this->assertSame( $expected, $this->notice->get_unmet_requirements( $php_version ) );
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

		$this->assertSame( $expected, $this->notice->get_unmet_requirements( '7.4.33' ) );
	}

	public function test_check_requirements_outputs_nothing_for_non_admin_user() {
		wp_set_current_user( 0 );
		$_GET['page'] = 'matomo-systemreport';

		$notice = $this->make_notice_with_unmet( [ 'some requirement' ] );
		$this->assertSame( '', $this->capture_notice( $notice ) );
	}

	public function test_check_requirements_outputs_nothing_when_not_on_matomo_or_plugins_page() {
		$this->create_set_super_admin();
		unset( $_GET['page'] );
		set_current_screen( 'edit.php' );

		$notice = $this->make_notice_with_unmet( [ 'some requirement' ] );
		$this->assertSame( '', $this->capture_notice( $notice ) );
	}

	public function test_check_requirements_outputs_nothing_when_requirements_are_met() {
		$this->create_set_super_admin();
		$_GET['page'] = 'matomo-systemreport';

		$notice = $this->make_notice_with_unmet( [] );
		$this->assertSame( '', $this->capture_notice( $notice ) );
	}

	public function test_check_requirements_shows_non_dismissible_notice_on_matomo_page() {
		$this->create_set_super_admin();
		$_GET['page'] = 'matomo-systemreport';

		$notice = $this->make_notice_with_unmet( [ 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).' ] );
		$output = $this->capture_notice( $notice );

		$this->assertStringContainsString( 'id="matomo-minimumrequirements"', $output );
		$this->assertStringContainsString( 'Matomo Analytics version 6 and later will require a newer server environment', $output );
		$this->assertStringContainsString( 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).', $output );
		// on matomo admin pages the notice cannot be dismissed.
		$this->assertStringNotContainsString( 'is-dismissible', $output );
	}

	public function test_check_requirements_shows_dismissible_notice_on_plugins_page() {
		$this->create_set_super_admin();
		unset( $_GET['page'] );
		set_current_screen( 'plugins' );

		$notice = $this->make_notice_with_unmet( [ 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).' ] );
		$output = $this->capture_notice( $notice );

		$this->assertStringContainsString( 'id="matomo-minimumrequirements"', $output );
		$this->assertStringContainsString( 'is-dismissible', $output );
	}

	public function test_check_requirements_outputs_nothing_on_plugins_page_when_dismissed() {
		$user_id = $this->create_set_super_admin();
		update_user_meta( $user_id, MinimumRequirementsNotice::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED, true );

		unset( $_GET['page'] );
		set_current_screen( 'plugins' );

		$notice = $this->make_notice_with_unmet( [ 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).' ] );
		$this->assertSame( '', $this->capture_notice( $notice ) );
	}

	private function set_fake_db( $server_info, $db_version ) {
		$fake              = $this->get_mock_wpdb();
		$fake->is_mysql    = true;
		$fake->server_info = $server_info;
		$fake->version     = $db_version;
		$GLOBALS['wpdb']   = $fake;
		return $fake;
	}

	private function php_requirement_message( $version ) {
		return sprintf(
			'PHP %1$s or higher is required (you are currently using PHP %2$s).',
			MinimumRequirementsNotice::REQUIRED_PHP_VERSION,
			$version
		);
	}

	private function mysql_requirement_message( $version ) {
		return sprintf(
			'MySQL %1$s or higher is required (you are currently using MySQL %2$s).',
			MinimumRequirementsNotice::REQUIRED_MYSQL_VERSION,
			$version
		);
	}

	private function mariadb_requirement_message( $version ) {
		return sprintf(
			'MariaDB %1$s or higher is required (you are currently using MariaDB %2$s).',
			MinimumRequirementsNotice::REQUIRED_MARIADB_VERSION,
			$version
		);
	}

	private function make_notice_with_unmet( array $unmet ) {
		$notice             = new class() extends MinimumRequirementsNotice {
			public $test_unmet = [];

			public function get_unmet_requirements( $php_version = PHP_VERSION ) {
				return $this->test_unmet;
			}
		};
		$notice->test_unmet = $unmet;
		return $notice;
	}

	private function capture_notice( MinimumRequirementsNotice $notice ) {
		ob_start();
		$notice->check_requirements();
		return ob_get_clean();
	}

	private function get_mock_wpdb() {
		return new class( $this->original_global_wpdb ) {
			private $original_db;

			public $is_mysql = true;

			public $server_info = '';

			public $version = '';

			public function __construct( $original_db ) {
				$this->original_db = $original_db;
			}

			public function db_server_info() {
				return $this->server_info;
			}

			public function db_version() {
				return $this->version;
			}

			public function __call( $name, $arguments ) {
				return call_user_func_array( [ $this->original_db, $name ], $arguments );
			}

			public function __get( $name ) {
				return $this->original_db->$name;
			}

			public function __set( $name, $value ) {
				$this->original_db->$name = $value;
			}
		};
	}

	private function get_mock_wpdb_without_server_info() {
		return new class() {
			public $is_mysql = true;
			public $version  = '';

			public function db_version() {
				return $this->version;
			}
		};
	}
}
