<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Admin;

class AdminTest extends MatomoUnit_TestCase {

	public function test_get_current_page_returns_page_param_when_no_page() {
		$page = Admin::get_current_page();

		$this->assertEquals( '', $page );
	}

	/**
	 * @dataProvider get_test_data_for_get_current_page
	 */
	public function test_get_current_page_returns_page_param( $page, $expected ) {
		$_GET['page'] = $page;

		$this->assertEquals( $expected, Admin::get_current_page() );
	}

	public function get_test_data_for_get_current_page() {
		return [
			[ null, '' ],
			[ '', '' ],
			[ 'matomo-page', 'matomo-page' ],
		];
	}
}
