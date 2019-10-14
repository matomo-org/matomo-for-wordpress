<?php
/**
 * @package Matomo_Analytics
 */


class MatomoUnit_TestCase extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

}
