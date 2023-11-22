<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Info;
use WpMatomo\Roles;

class AdminInfoTest extends MatomoUnit_TestCase {

	/**
	 * @var Info
	 */
	private $info;

	public function setUp(): void {
		parent::setUp();

		$this->info = new Info();

		wp_get_current_user()->add_role( Roles::ROLE_SUPERUSER );

		$this->assume_admin_page();
	}

	public function tearDown(): void {
		$_REQUEST = array();
		$_POST    = array();
		parent::tearDown();
	}

	public function test_show_renders_ui() {
		ob_start();
		$this->info->show();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( '100% data ownership', $output );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_multisite_renders_ui() {
		ob_start();
		$this->info->show_multisite();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Multi Site mode', $output );
	}

}
