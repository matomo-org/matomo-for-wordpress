<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model as SitesModel;
use Piwik\Plugins\UsersManager\Model as UsersModel;
use WpMatomo\Bootstrap;
use WpMatomo\Installer;
use WpMatomo\Paths;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;

class InstallTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Installer
	 */
	private $installer;
	/**
	 * @var Uninstaller
	 */
	private $uninstaller;

	public function setUp(): void {
		parent::setUp();

		$this->installer   = $this->make_installer();
		$this->uninstaller = new Uninstaller();
	}

	private function make_installer() {
		return new Installer( new Settings() );
	}

	public function test_looks_like_it_is_installed_is_intalled_when_installed() {
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		$this->assertTrue( Installer::is_intalled() );
	}

	public function test_can_be_installed() {
		$this->assertTrue( $this->installer->can_be_installed() );
	}

	public function test_install_adds_sites_and_users() {
		Bootstrap::set_not_bootstrapped();

		$this->installer->install();

		$sites_model = new SitesModel();
		$all_sites   = $sites_model->getAllSites();

		$install_date = get_option( Installer::OPTION_NAME_INSTALL_DATE );

		// sets install date
		$this->assertTrue( time() - 600 < $install_date );
		$this->assertTrue( time() >= $install_date );

		unset( $all_sites[0]['ts_created'] );
		$this->assertEquals(
			array(
				array(
					'idsite'                         => 1,
					'name'                           => 'Test Blog',
					'main_url'                       => '',
					'ecommerce'                      => 0,
					'sitesearch'                     => 1,
					'sitesearch_keyword_parameters'  => '',
					'sitesearch_category_parameters' => '',
					'timezone'                       => 'UTC',
					'currency'                       => 'USD',
					'exclude_unknown_urls'           => 0,
					'excluded_ips'                   => '',
					'excluded_parameters'            => '',
					'excluded_referrers'             => '',
					'excluded_user_agents'           => '',
					'group'                          => '',
					'type'                           => 'website',
					'keep_url_fragment'              => 0,
					'creator_login'                  => 'super user was set',
				),
			),
			$all_sites
		);

		$users_model = new UsersModel();
		$all_users   = $users_model->getUsers( array() );

		foreach ( array( 'password', 'date_registered', 'ts_password_modified' ) as $field ) {
			$this->assertNotEmpty( $all_users[0][ $field ] );
			unset( $all_users[0][ $field ] );
		}
		$this->assertEquals(
			array(
				array(
					'login'                => 'admin',
					'email'                => 'admin@example.org',
					'twofactor_secret'     => '',
					'superuser_access'     => '1',
					'idchange_last_viewed' => null,
					'invited_by'           => null,
					'invite_token'         => null,
					'invite_expired_at'    => null,
					'invite_accept_at'     => null,
					'invite_link_token'    => null,
				),
			),
			$all_users
		);
	}

	public function test_install_can_run_multiple_times() {
		$this->uninstaller->uninstall( true );
		$this->assertFalse( $this->installer->looks_like_it_is_installed() );
		$this->assertFalse( Installer::is_intalled() );

		Bootstrap::set_not_bootstrapped();
		$this->assertTrue( $this->installer->install() );
		$this->assertFalse( $this->installer->install() );

		Bootstrap::set_not_bootstrapped();
		$this->assertFalse( $this->installer->install() );
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );
		$this->assertTrue( Installer::is_intalled() );
	}

	/**
	 * @group ms-required
	 */
	public function test_install_also_installs_on_other_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blogid1 = self::factory()->blog->create();
		switch_to_blog( $blogid1 );

		// we trigger install manually... we could listen to an action like "wp_initialize_site" and then install
		// automatically... but bit scared of "fatal errors etc" and breaking anything in WordPress... instead
		// the site sync will install it and/or when someone visits that site
		Bootstrap::set_not_bootstrapped();

		$this->installer->install();

		$blogid = get_current_blog_id();

		$paths = new Paths();
		$this->assertStringContainsString( 'wp-content/uploads/sites/' . $blogid . '/matomo/config/config.ini.php', $paths->get_config_ini_path() );
		$this->assertTrue( $this->installer->looks_like_it_is_installed() );

		$sites_model = new SitesModel();
		$all_sites   = $sites_model->getAllSites();

		wp_delete_site( $blogid1 );

		$this->assertCount( 1, $all_sites );
	}

}
