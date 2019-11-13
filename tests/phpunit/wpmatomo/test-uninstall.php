<?php
/**
 * @package matomo
 */

use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Uninstaller;
use WpMatomo\User;

class UninstallTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Installer
	 */
	private $installer;
	/**
	 * @var Uninstaller
	 */
	private $uninstaller;

	public function setUp() {
		parent::setUp();

		$this->uninstaller = new Uninstaller();
		$this->installer   = new Installer( new Settings() );
	}

	public function test_uninstall_can_run_multiple_times() {
		$this->uninstaller->uninstall( false );
		$this->uninstaller->uninstall( true );
		$this->uninstaller->uninstall( false );
	}

	public function test_uninstall_removes_config_file_when_should_remove_all_data() {
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );

		$this->uninstaller->uninstall( true );

		$this->assertFalse( $this->installer->looks_like_it_is_installed() );
	}

	public function test_uninstall_leaves_config_file_when_should_not_remove_all_data() {
		$this->uninstaller->uninstall( false );

		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
	}

	public function test_uninstall_removes_sync_mappings() {
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		Site::map_matomo_site_id( get_current_blog_id(), 10 );
		User::map_matomo_user_login( get_current_user_id(), 'foo' );

		$this->uninstaller->uninstall( true );

		$this->assertFalse( $this->installer->looks_like_it_is_installed() );
		$this->assertFalse( Site::get_matomo_site_id( get_current_blog_id() ) );
		$this->assertFalse( User::get_matomo_user_login( get_current_user_id() ) );
	}

	public function test_uninstall_keeps_sync_mappings() {
		Site::map_matomo_site_id( get_current_blog_id(), 10 );
		User::map_matomo_user_login( get_current_user_id(), 'foo' );

		$this->uninstaller->uninstall( false );

		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		$this->assertNotEmpty( Site::get_matomo_site_id( get_current_blog_id() ) );
		$this->assertNotEmpty( User::get_matomo_user_login( get_current_user_id() ) );
	}

	public function test_uninstall_resets_settings() {
		$settings = new Settings();
		$settings->apply_tracking_related_changes( array( 'track_mode' => 'manually' ) );
		$this->assertSame( 'manually', $settings->get_global_option( 'track_mode' ) );

		$this->uninstaller->uninstall( true );

		$settings = new Settings();
		$this->assertSame( 'disabled', $settings->get_global_option( 'track_mode' ) );
	}

	public function test_uninstall_keeps_settings() {
		$settings = new Settings();
		$settings->apply_tracking_related_changes( array( 'track_mode' => 'manually' ) );
		$this->assertSame( 'manually', $settings->get_global_option( 'track_mode' ) );

		$this->uninstaller->uninstall( false );

		$this->assertSame( 'manually', $settings->get_global_option( 'track_mode' ) );
	}

	public function test_uninstall_removes_roles_even_when_not_remove_data() {
		$this->uninstaller->uninstall( false );

		$this->assertEmpty( get_role( Roles::ROLE_WRITE ) );
	}

}
