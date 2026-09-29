<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\Info;
use WpMatomo\Admin\MatomoPage;
use WpMatomo\Capabilities;
use WpMatomo\Roles;

class MatomoPageTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var MatomoPage
	 */
	private $page;

	public function setUp(): void {
		parent::setUp();

		$this->assume_admin_page();

		$this->page = new MatomoPage( new Info() );
	}

	public function tearDown(): void {
		if ( is_multisite() ) {
			set_current_screen( 'dashboard' );
		}

		parent::tearDown();
	}

	public function test_show_renders_the_page_content() {
		ob_start();
		$this->page->show();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_refuse_a_matomo_superuser_role_holder_in_the_network_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$user_id = self::factory()->user->create( [ 'role' => Roles::ROLE_SUPERUSER ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );

		set_current_screen( 'dashboard-network' );

		$this->expectException( WPDieException::class );

		ob_start();

		try {
			$this->page->show();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_refuse_a_blog_administrator_in_the_network_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->assertFalse( is_super_admin( $user_id ) );
		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		set_current_screen( 'dashboard-network' );

		$this->expectException( WPDieException::class );

		ob_start();

		try {
			$this->page->show();
		} finally {
			ob_end_clean();
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_show_should_render_for_a_network_administrator_in_the_network_admin() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		wp_set_current_user( $this->create_set_super_admin() );

		set_current_screen( 'dashboard-network' );

		ob_start();
		$this->page->show();
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );
	}
}
