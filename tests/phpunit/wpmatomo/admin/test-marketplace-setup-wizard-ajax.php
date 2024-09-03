<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\MarketplaceSetupWizard;

class MarketplaceSetupWizardAjaxTest extends MatomoAnalytics_Ajax_TestCase {
	public function setUp(): void {
		parent::setUp();
		MarketplaceSetupWizard::register_ajax();
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

		wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );

		try {
			$this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => 'garbagevalue' ] );
			$this->fail( 'ajax method did not fail as expected' );
		} catch ( WPAjaxDieStopException $e ) {
			$this->assertEquals( '-1', $e->getMessage() ); // see check_ajax_referer()
		}
	}

	public function test_is_marketplace_active_returns_true_if_plugin_is_active() {
		$nonce_value = wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );
		activate_plugin( 'matomo-marketplace-for-wordpress' );

		$response = $this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => $nonce_value ] );
		$this->assertEquals( [ 'active' => true ], $response );
	}

	public function test_is_marketplace_active_returns_false_if_plugin_is_not_active() {
		$nonce_value = wp_create_nonce( MarketplaceSetupWizard::AJAX_IS_ACTIVE_NONCE_NAME );

		$response = $this->call_ajax( 'matomo_is_marketplace_active', [], [ '_ajax_nonce' => $nonce_value ] );
		$this->assertEquals( [ 'active' => false ], $response );
	}
}
