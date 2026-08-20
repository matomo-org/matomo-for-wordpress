<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\MinimumRequirements;
use WpMatomo\MinimumRequirementsUpdateGuard;

require_once __DIR__ . '/../framework/traits/test-matomo-mock-db-server-test.php';

/**
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */
class MinimumRequirementsUpdateGuardTest extends MatomoUnit_TestCase {

	use MatomoMockDbServerTest;

	const UNMET_MESSAGE = 'MySQL 8.0 or higher is required (you are currently using MySQL 5.7.40).';

	const PACKAGE_V6 = 'https://downloads.wordpress.org/plugin/matomo.6.0.0.zip';
	const PACKAGE_V5 = 'https://downloads.wordpress.org/plugin/matomo.5.13.0.zip';

	/**
	 * @var string
	 */
	private $plugin_basename;

	public function setUp(): void {
		parent::setUp();

		$this->remember_global_wpdb();

		$this->plugin_basename = plugin_basename( MATOMO_ANALYTICS_FILE );
	}

	public function tearDown(): void {
		$this->restore_global_wpdb();

		delete_site_transient( 'update_plugins' );

		parent::tearDown();
	}

	public function test_block_download_ignores_packages_of_other_plugins() {
		$guard = $this->make_guard();

		$this->assertFalse(
			$guard->block_download_if_incompatible(
				false,
				'https://downloads.wordpress.org/plugin/some-other-plugin.6.0.0.zip',
				null,
				[ 'plugin' => 'some-other-plugin/some-other-plugin.php' ]
			)
		);
	}

	public function test_block_download_does_not_override_another_short_circuit() {
		$guard = $this->make_guard();

		$this->assertSame(
			'/tmp/already-downloaded.zip',
			$guard->block_download_if_incompatible(
				'/tmp/already-downloaded.zip',
				self::PACKAGE_V6,
				null,
				[ 'plugin' => $this->plugin_basename ]
			)
		);
	}

	public function test_block_download_allows_versions_that_do_not_need_the_new_requirements() {
		$guard = $this->make_guard();

		$this->assertFalse(
			$guard->block_download_if_incompatible(
				false,
				self::PACKAGE_V5,
				null,
				[ 'plugin' => $this->plugin_basename ]
			)
		);
	}

	public function test_block_download_allows_the_new_version_when_the_server_is_supported() {
		$guard = $this->make_guard( [] );

		$this->assertFalse(
			$guard->block_download_if_incompatible(
				false,
				self::PACKAGE_V6,
				null,
				[ 'plugin' => $this->plugin_basename ]
			)
		);
	}

	public function test_block_download_blocks_the_new_version_when_the_server_is_not_supported() {
		$guard = $this->make_guard();

		$result = $guard->block_download_if_incompatible(
			false,
			self::PACKAGE_V6,
			null,
			[ 'plugin' => $this->plugin_basename ]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( MinimumRequirementsUpdateGuard::ERROR_CODE, $result->get_error_code() );

		$message = $result->get_error_message();
		$this->assertStringContainsString( '6.0.0', $message );
		$this->assertStringContainsString( self::UNMET_MESSAGE, $message );
		// WP_Upgrader_Skin::feedback() runs vsprintf() over any message containing a percent sign
		$this->assertStringNotContainsString( '%', $message );
	}

	public function test_block_download_blocks_a_fresh_install_which_does_not_pass_the_plugin_along() {
		$guard = $this->make_guard();

		$result = $guard->block_download_if_incompatible(
			false,
			self::PACKAGE_V6,
			null,
			[
				'type'   => 'plugin',
				'action' => 'install',
			]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_block_download_blocks_when_hook_extra_is_not_available() {
		// WordPress older than 5.5 does not pass $hook_extra to the filter at all
		$guard = $this->make_guard();

		$result = $guard->block_download_if_incompatible( false, self::PACKAGE_V6 );

		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_block_download_reads_the_version_from_the_update_transient_when_the_package_is_not_recognised() {
		$this->set_available_update( '6.1.0' );

		$guard = $this->make_guard();

		$result = $guard->block_download_if_incompatible(
			false,
			'https://example.com/private-mirror/matomo-latest.zip',
			null,
			[ 'plugin' => $this->plugin_basename ]
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( '6.1.0', $result->get_error_message() );
	}

	public function test_block_download_does_not_block_a_package_whose_version_is_unknown() {
		$guard = $this->make_guard();

		$this->assertFalse(
			$guard->block_download_if_incompatible(
				false,
				'/tmp/manually-uploaded.zip',
				null,
				[ 'plugin' => $this->plugin_basename ]
			)
		);
	}

	public function test_disable_auto_update_leaves_other_plugins_alone() {
		$guard = $this->make_guard();

		$item = (object) [
			'plugin'      => 'some-other-plugin/some-other-plugin.php',
			'new_version' => '6.0.0',
		];

		$this->assertTrue( $guard->disable_auto_update_if_incompatible( true, $item ) );
	}

	public function test_disable_auto_update_leaves_supported_versions_alone() {
		$guard = $this->make_guard();

		$item = (object) [
			'plugin'      => $this->plugin_basename,
			'new_version' => '5.13.0',
		];

		$this->assertTrue( $guard->disable_auto_update_if_incompatible( true, $item ) );
	}

	public function test_disable_auto_update_disables_an_incompatible_update() {
		$guard = $this->make_guard();

		$item = (object) [
			'plugin'      => $this->plugin_basename,
			'new_version' => '6.0.0',
		];

		$this->assertFalse( $guard->disable_auto_update_if_incompatible( true, $item ) );
	}

	public function test_disable_auto_update_handles_an_unexpected_item() {
		$guard = $this->make_guard();

		$this->assertTrue( $guard->disable_auto_update_if_incompatible( true, null ) );
		$this->assertTrue( $guard->disable_auto_update_if_incompatible( true, [ 'plugin' => $this->plugin_basename ] ) );
	}

	public function test_show_update_row_message_explains_an_incompatible_update() {
		$guard = $this->make_guard();

		$output = $this->capture_update_row_message( $guard, '6.0.0' );

		$this->assertStringContainsString( 'This update cannot be installed', $output );
		$this->assertStringContainsString( self::UNMET_MESSAGE, $output );
	}

	public function test_show_update_row_message_outputs_nothing_for_a_supported_update() {
		$guard = $this->make_guard();

		$this->assertSame( '', $this->capture_update_row_message( $guard, '5.13.0' ) );
	}

	public function test_show_update_row_message_outputs_nothing_when_the_server_is_supported() {
		$guard = $this->make_guard( [] );

		$this->assertSame( '', $this->capture_update_row_message( $guard, '6.0.0' ) );
	}

	private function capture_update_row_message( MinimumRequirementsUpdateGuard $guard, $new_version ) {
		ob_start();
		$guard->show_update_row_message( [], (object) [ 'new_version' => $new_version ] );
		return ob_get_clean();
	}

	private function set_available_update( $new_version ) {
		$updates           = new stdClass();
		$updates->response = [
			$this->plugin_basename => (object) [
				'plugin'      => $this->plugin_basename,
				'new_version' => $new_version,
			],
		];

		set_site_transient( 'update_plugins', $updates );
	}

	/**
	 * @param string[]|null $unmet what the requirements check should report, defaults to an
	 *                             unsupported MySQL version.
	 */
	private function make_guard( $unmet = null ) {
		$requirements             = new class() extends MinimumRequirements {
			public $test_unmet = [];

			public function get_unmet_requirements( $php_version = PHP_VERSION ) {
				return $this->test_unmet;
			}
		};
		$requirements->test_unmet = null === $unmet ? [ self::UNMET_MESSAGE ] : $unmet;

		return new MinimumRequirementsUpdateGuard( $requirements );
	}
}
