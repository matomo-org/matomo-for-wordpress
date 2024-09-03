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
		// TODO
	}

	public function test_is_marketplace_active_returns_true_if_plugin_is_active() {
		// TODO
	}

	public function test_is_marketplace_active_returns_false_if_plugin_is_not_active() {
		// TODO
	}
}
