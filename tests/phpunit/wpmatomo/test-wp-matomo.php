<?php
/**
 * @package matomo
 */

use WpMatomo\Capabilities;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\TrackingCode;
use WpMatomo\User\Sync as UserSync;

class WpMatomoTest extends MatomoUnit_TestCase {

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
