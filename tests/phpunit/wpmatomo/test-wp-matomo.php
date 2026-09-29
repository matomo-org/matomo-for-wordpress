<?php
/**
 * @package matomo
 */

use WpMatomo\Capabilities;
use WpMatomo\MinimumRequirements;
use WpMatomo\MinimumRequirementsNotice;
use WpMatomo\MinimumRequirementsUpdateGuard;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\TrackingCode;
use WpMatomo\User\Sync as UserSync;

class WpMatomoTest extends MatomoUnit_TestCase {

	public function tearDown(): void {
		WpMatomo::set_minimum_requirements( null );

		parent::tearDown();
	}

	public function test_get_safe_mode_features_should_include_capabilities_so_matomo_permissions_still_resolve() {
		$this->assertContains( Capabilities::class, $this->get_safe_mode_feature_classes() );
	}

	public function test_get_safe_mode_features_should_include_capabilities_outside_wp_admin() {
		set_current_screen( 'front' );
		$this->assertFalse( is_admin() );

		$this->assertContains( Capabilities::class, $this->get_safe_mode_feature_classes() );
	}

	public function test_get_safe_mode_features_should_not_include_features_that_boot_matomo() {
		$this->assume_admin_page();

		$classes = $this->get_safe_mode_feature_classes();

		// safe mode exists so that a broken Matomo cannot take WordPress down with it, so nothing
		// that reaches into the Matomo application may come back
		$this->assertNotContains( SiteSync::class, $classes );
		$this->assertNotContains( UserSync::class, $classes );
		$this->assertNotContains( ScheduledTasks::class, $classes );
		$this->assertNotContains( TrackingCode::class, $classes );
	}

	public function test_get_safe_mode_features_should_only_offer_the_admin_features_in_wp_admin() {
		$this->assume_admin_page();
		$admin_classes = $this->get_safe_mode_feature_classes();

		set_current_screen( 'front' );
		$this->assertFalse( is_admin() );
		$front_classes = $this->get_safe_mode_feature_classes();

		$this->assertContains( \WpMatomo\Admin\SafeModeMenu::class, $admin_classes );
		$this->assertNotContains( \WpMatomo\Admin\SafeModeMenu::class, $front_classes );
	}

	public function test_get_safe_mode_features_should_keep_blocking_incompatible_updates() {
		set_current_screen( 'front' );
		$this->assertFalse( is_admin() );

		$this->assertContains( MinimumRequirementsUpdateGuard::class, $this->get_safe_mode_feature_classes() );
	}

	public function test_get_safe_mode_features_should_explain_unmet_requirements_in_wp_admin() {
		$this->assume_admin_page();

		$this->assertContains( MinimumRequirementsNotice::class, $this->get_safe_mode_feature_classes() );
	}

	public function test_is_safe_mode_is_false_when_the_installed_version_can_run_on_this_server() {
		$this->skip_if_safe_mode_is_forced();

		WpMatomo::set_minimum_requirements( $this->make_requirements( true ) );

		$this->assertFalse( WpMatomo::is_safe_mode() );
	}

	public function test_is_safe_mode_is_true_when_the_installed_version_cannot_run_on_this_server() {
		$this->skip_if_safe_mode_is_forced();

		WpMatomo::set_minimum_requirements( $this->make_requirements( false ) );

		$this->assertTrue( WpMatomo::is_safe_mode() );
	}

	public function test_is_safe_mode_is_false_for_the_currently_installed_version() {
		$this->skip_if_safe_mode_is_forced();

		// the requirements only apply from Matomo 6 on, so nothing should change for now
		$this->assertFalse( WpMatomo::is_safe_mode() );
	}

	private function skip_if_safe_mode_is_forced() {
		if ( defined( 'MATOMO_SAFE_MODE' ) ) {
			$this->markTestSkipped( 'MATOMO_SAFE_MODE is defined, so the requirements check is not reached' );
		}
	}

	private function make_requirements( $are_met ) {
		$requirements               = new class() extends MinimumRequirements {
			public $test_are_met = true;

			public function can_this_system_run_plugin_version( $plugin_version, $php_version = PHP_VERSION ) {
				return $this->test_are_met;
			}
		};
		$requirements->test_are_met = $are_met;
		return $requirements;
	}

	/**
	 * @return string[]
	 */
	private function get_safe_mode_feature_classes() {
		$classes = [];

		foreach ( WpMatomo::get_safe_mode_features( new Settings() ) as $feature ) {
			$classes[] = get_class( $feature );
		}

		return $classes;
	}
}
