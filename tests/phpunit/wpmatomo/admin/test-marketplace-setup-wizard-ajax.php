<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\MarketplaceSetupWizard;

/**
 * @group only
 */
class MarketplaceSetupWizardAjaxTest extends MatomoAnalytics_Ajax_TestCase {
	public function setUp(): void {
		parent::setUp();
		MarketplaceSetupWizard::register_ajax();
		$this->wordpress_fixture->switch_to_admin_page();

		// create dummy marketplace plugin
		$marketplace_plugin_dir = $this->get_marketplace_plugin_dir();
		if ( is_dir( $marketplace_plugin_dir )
			&& count( scandir( $marketplace_plugin_dir ) ) > 1
		) {
			$this->markTestSkipped( 'cannot run this test while the real matomo-marketplace-for-wordpress plugin is installed' );
		}

		$dummy_plugin_content = <<<PHP
<?php
/**
 * Plugin Name: test plugin
 */
PHP;

		mkdir( $marketplace_plugin_dir, 0777, true );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
		file_put_contents( $marketplace_plugin_dir . '/matomo-marketplace-for-wordpress.php', $dummy_plugin_content );

		$this->deactivate_marketplace();
	}

	public function tearDown(): void {
		$this->deactivate_marketplace();
		$marketplace_plugin_dir = $this->get_marketplace_plugin_dir();
		unlink( $marketplace_plugin_dir . '/matomo-marketplace-for-wordpress.php' );
		rmdir( $marketplace_plugin_dir );
	}

	public function test_is_marketplace_active_fails_if_incorrect_nonce_given() {
		wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );

		try {
			$this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => 'garbagevalue' ] );
			$this->fail( 'ajax method did not fail as expected' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertEquals( '-1', $e->getMessage() ); // see check_ajax_referer()
		}
	}

	public function test_is_marketplace_active_fails_if_user_cannot_activate_plugins() {
		$userid = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $userid );

		$nonce = wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );

		$response = $this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => $nonce ] );
		$this->assertEquals(
			[
				'success' => false,
				'data'    => [
					'message' => 'forbidden',
				],
			],
			$response
		);
	}

	public function test_is_marketplace_active_returns_true_if_plugin_is_active() {
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$nonce_value = wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );
		$result      = activate_plugin( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' );
		$this->assertNull( $result );

		$response = $this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => $nonce_value ] );
		$this->assertEquals( [ 'active' => true ], $response );
	}

	public function test_is_marketplace_active_returns_false_if_plugin_is_not_active() {
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$nonce_value = wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );

		$response = $this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => $nonce_value ] );
		$this->assertEquals( [ 'active' => false ], $response );
	}

	public function test_activate_marketplace_plugin_fails_if_incorrect_nonce_given() {
		$this->assertFalse( is_plugin_active( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' ) );

		wp_create_nonce( MarketplaceSetupWizard::AJAX_ACTIVATE_NONCE_NAME );

		try {
			$this->call_ajax( 'matomo_activate_marketplace', [], [ '_ajax_nonce' => 'garbagevalue' ] );
			$this->fail( 'ajax method did not fail as expected' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertEquals( '-1', $e->getMessage() ); // see check_ajax_referer()
		}
	}

	public function test_activate_marketplace_plugin_fails_if_user_cannot_activate_plugins() {
		$this->assertFalse( is_plugin_active( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' ) );

		$userid = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $userid );

		$nonce = wp_create_nonce( MarketplaceSetupWizard::AJAX_ACTIVATE_NONCE_NAME );

		$response = $this->call_ajax( 'matomo_activate_marketplace', [], [ '_ajax_nonce' => $nonce ] );
		$this->assertEquals(
			[
				'success' => false,
				'data'    => [
					'message' => 'forbidden',
				],
			],
			$response
		);
	}

	public function test_activate_marketplace_plugin_correctly_activates_the_plugin() {
		$this->assertFalse( is_plugin_active( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' ) );

		$userid = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $userid );

		$nonce = wp_create_nonce( MarketplaceSetupWizard::AJAX_ACTIVATE_NONCE_NAME );

		$response = $this->call_ajax( 'matomo_activate_marketplace', [], [ '_ajax_nonce' => $nonce ] );
		$this->assertEquals( [], $response );

		$this->assertTrue( is_plugin_active( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' ) );
	}

	private function get_marketplace_plugin_dir() {
		return ABSPATH . '/wp-content/plugins/matomo-marketplace-for-wordpress';
	}

	private function deactivate_marketplace() {
		deactivate_plugins( 'matomo-marketplace-for-wordpress/matomo-marketplace-for-wordpress.php' );
	}
}
