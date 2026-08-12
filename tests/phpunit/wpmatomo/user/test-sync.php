<?php
/**
 * @package matomo
 */

use Piwik\Access\Role\Admin;
use Piwik\Access\Role\View;
use Piwik\Plugins\UsersManager\Model;
use WpMatomo\Access;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\User;
use WpMatomo\User\Sync;

class MockMatomoUserSync extends Sync {
	public $mock_sync    = true;
	public $synced_users = array();

	public function sync_users( $users, $idsite ) {
		if ( $this->mock_sync ) {
			$this->synced_users[] = array(
				'users'  => $users,
				'idSite' => $idsite,
			);
		} else {
			parent::sync_users( $users, $idsite );
		}
	}
	// phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
	public function ensure_user_exists( $wp_user ) {
		return parent::ensure_user_exists( $wp_user );
	}
}

class UserSyncTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Sync
	 */
	private $sync;

	/**
	 * @var MockMatomoUserSync
	 */
	private $mock;

	public function setUp(): void {
		parent::setUp();

		$this->assume_admin_page();

		// if left active, the plugin's own Sync would start syncing on every role change. these
		// tests drive their own instance, so leave only the test one hooked
		$registered = WpMatomo::get_active_feature( Sync::class );
		if ( $registered ) {
			$registered->remove_hooks();
		}

		$this->sync            = new MockMatomoUserSync();
		$this->sync->mock_sync = false;
		$this->mock            = new MockMatomoUserSync();
	}

	public function test_sync_all_does_not_fail() {
		$this->assertNull( $this->sync->sync_all() );
	}

	public function test_sync_all_passes_correct_values_to_sync_site() {
		$this->mock->sync_all();

		$idsite = $this->get_current_site_id();

		$this->assertCount( 1, $this->mock->synced_users[0]['users'] );
		unset( $this->mock->synced_users[0]['users'] );
		$this->assertEquals(
			array(
				array(
					'idSite' => $idsite,
				),
			),
			$this->mock->synced_users
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_all_passes_correct_values_to_sync_site_when_there_are_multiple_blogs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}
		$blogid1   = self::factory()->blog->create( array( 'domain' => 'foobar.com' ) );
		$blogid2   = self::factory()->blog->create( array( 'domain' => 'foobar.baz' ) );
		$site_sync = new \WpMatomo\Site\Sync( new Settings() );

		$site_sync->sync_all();

		$this->mock->synced_users = array();
		$this->mock->sync_all();

		$idsite = $this->get_current_site_id();

		switch_to_blog( $blogid1 );
		$user2 = get_user_by( 'login', 'admin' );
		restore_current_blog();
		switch_to_blog( $blogid2 );
		$user3 = get_user_by( 'login', 'admin' );
		restore_current_blog();

		$this->assertCount( 1, $this->mock->synced_users[0]['users'] );
		unset( $this->mock->synced_users[0]['users'] );

		$this->assertCount( 1, $this->mock->synced_users[1]['users'] );
		$this->assertEquals( $user2->ID, $this->mock->synced_users[1]['users'][0]->ID );
		unset( $this->mock->synced_users[1]['users'] );

		$this->assertCount( 1, $this->mock->synced_users[2]['users'] );
		$this->assertEquals( $user3->ID, $this->mock->synced_users[2]['users'][0]->ID );
		unset( $this->mock->synced_users[2]['users'] );

		$this->assertEquals(
			array(
				array(
					'idSite' => $idsite,
				),
				array(
					'idSite' => $idsite,
				),
				array(
					'idSite' => $idsite,
				),
			),
			array_slice( $this->mock->synced_users, 0, 3 )
		);

		wp_delete_site( $blogid1 );// remove the blogs again so they don't break other tests
		wp_delete_site( $blogid2 );
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_all_should_not_limit_the_number_of_blogs_it_reconciles() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$limits = $this->capture_blog_query_limits(
			function () {
				$this->mock->sync_all();
			}
		);

		$this->assertNotEmpty( $limits, 'expected sync_all() to query the list of blogs' );
		$this->assertSame(
			[ 0 ],
			array_values( array_unique( $limits ) ),
			'WP_Site_Query defaults to 100 blogs, so a limit leaves the rest unreconciled'
		);
	}

	public function test_sync_current_site_does_not_fail() {
		$this->assertNull( $this->sync->sync_current_users() );
	}

	private function get_current_site_id() {
		$site = new WpMatomo\Site();

		return $site->get_current_matomo_site_id();
	}

	public function test_sync_current_site_passes_correct_values_to_sync_site() {
		$this->mock->sync_current_users();

		$this->assertCount( 1, $this->mock->synced_users[0]['users'] );
		unset( $this->mock->synced_users[0]['users'] );

		$this->assertEquals(
			array(
				array(
					'idSite' => $this->get_current_site_id(),
				),
			),
			$this->mock->synced_users
		);
	}

	public function test_sync_current_users_by_default_gives_only_access_to_administrators() {
		$this->createManyUsers();

		$this->sync->sync_current_users();

		$model  = new Model();
		$logins = $model->getUsersLogin();
		$this->assertSame( array( 'admin', 'admin1', 'admin2' ), $logins );

		// all admins should also be super users
		foreach ( $logins as $login ) {
			$matomo_user = $this->get_matomo_user( $login );
			$this->assertEquals( '1', $matomo_user['superuser_access'] );
		}
	}

	public function test_sync_current_users_creates_users_where_needed() {
		$this->createManyUsers();
		$settings = new Settings();
		$caps     = new Capabilities( $settings );
		$caps->register_hooks(); // access and capabilities need to share same settings instance otherwise tests won't work correctly

		$access = new Access( $settings );
		$access->save(
			array(
				'editor' => Capabilities::KEY_WRITE,
				'author' => Capabilities::KEY_VIEW,
			)
		);

		$this->sync->sync_current_users();

		$model  = new Model();
		$logins = $model->getUsersLogin();
		$this->assertSame(
			array(
				'admin',
				'admin1',
				'admin2',
				'author1',
				'author2',
				'editor1',
				'editor2',
			),
			$logins
		);

		$idsite = $this->get_current_site_id();

		$view_access = $model->getUsersSitesFromAccess( 'view' );
		$this->assertEquals(
			array(
				'author1' => array( $idsite ),
				'author2' => array( $idsite ),
			),
			$view_access
		);

		$write_access = $model->getUsersSitesFromAccess( 'write' );
		$this->assertEquals(
			array(
				'editor1' => array( $idsite ),
				'editor2' => array( $idsite ),
			),
			$write_access
		);

		$view_access = $model->getUsersSitesFromAccess( 'admin' );
		$this->assertSame( array(), $view_access );

		foreach ( array( 'admin', 'admin1', 'admin2' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '1', $matomo_user['superuser_access'] );
		}

		// now we change permission and matomo should adjust

		$access = new Access( $settings );
		$access->save(
			array(
				'editor'      => Capabilities::KEY_ADMIN,
				'contributor' => Capabilities::KEY_VIEW,
			)
		);

		$user = get_user_by( 'login', 'admin2' );
		self::delete_user( $user->ID );
		$id6 = self::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'admin4',
			)
		);

		// it should now remove all author users... change permission for editor, and add new permission for contributor
		// we are also creating one more user and deleting another existing user
		$this->sync->sync_current_users();

		$logins = $model->getUsersLogin();
		$this->assertSame(
			array(
				'admin',
				'admin1',
				'admin4',
				'contributor1',
				'editor1',
				'editor2',
			),
			$logins
		);

		$view_access = $model->getUsersSitesFromAccess( 'view' );
		$this->assertEquals( array( 'contributor1' => array( $idsite ) ), $view_access );

		$write_access = $model->getUsersSitesFromAccess( 'write' );
		$this->assertSame( array(), $write_access );

		$view_access = $model->getUsersSitesFromAccess( 'admin' );
		$this->assertEquals(
			array(
				'editor1' => array( $idsite ),
				'editor2' => array( $idsite ),
			),
			$view_access
		);

		foreach ( array( 'admin', 'admin1', 'admin4' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '1', $matomo_user['superuser_access'] );
		}

		$user = get_user_by( 'login', 'editor1' );
		$user->add_role( Roles::ROLE_SUPERUSER );

		// now we're giving editors super user access
		$this->sync->sync_current_users();

		$logins = $model->getUsersLogin();
		$this->assertSame(
			array(
				'admin',
				'admin1',
				'admin4',
				'contributor1',
				'editor1',
				'editor2',
			),
			$logins
		);

		foreach ( array( 'admin', 'admin1', 'admin4', 'editor1' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '1', $matomo_user['superuser_access'], "user $user_login expected to be superuser" );
		}

		foreach ( array( 'contributor1', 'editor2' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '0', $matomo_user['superuser_access'] );
		}

		// and now we're taking it away again from them and we make sure they do not still have superuser access set
		$user = get_user_by( 'login', 'editor1' );
		$user->remove_role( Roles::ROLE_SUPERUSER );

		$this->sync->sync_current_users();

		foreach ( array( 'admin', 'admin1', 'admin4' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '1', $matomo_user['superuser_access'] );
		}
		foreach ( array( 'editor1', 'editor2' ) as $user_login ) {
			$matomo_user = $this->get_matomo_user( $user_login );
			$this->assertEquals( '0', $matomo_user['superuser_access'] );
		}

		$caps->remove_hooks();
	}

	private function createManyUsers() {
		$id1 = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'editor1',
			)
		);
		$id2 = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'editor2',
			)
		);
		$id3 = self::factory()->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'author1',
			)
		);
		$id4 = self::factory()->user->create(
			array(
				'role'       => 'author',
				'user_login' => 'author2',
			)
		);
		$id5 = self::factory()->user->create(
			array(
				'role'       => 'contributor',
				'user_login' => 'contributor1',
			)
		);
		$id6 = self::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'admin1',
			)
		);
		$id6 = self::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_login' => 'admin2',
			)
		);
	}

	public function test_ensure_user_exists_creates_user_when_not_exists_yet() {
		$id1  = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'foobaz',
				'user_email' => 'foobaz3@example.org',
			)
		);
		$user = new WP_User( $id1 );

		$login = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'foobaz', $login );
		$this->assertSame( 'foobaz', User::get_matomo_user_login( $id1 ) );

		$matomo_user = $this->get_matomo_user( 'foobaz' );
		$this->assertSame( 'foobaz', $matomo_user['login'] );
		$this->assertSame( 'foobaz3@example.org', $matomo_user['email'] );
		$this->assertNotEmpty( $matomo_user['password'] );
	}

	public function test_ensure_user_exists_when_username_already_exists_uses_different_user() {
		$id1  = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'foobar',
			)
		);
		$user = new WP_User( $id1 );

		$model = new Model();
		$model->addUser( 'foobar', md5( 1 ), 'email@example.org', 'foobar', md5( 1 ), '2018-01-02 03:04:05' );
		$model->addUser( 'wp_foobar', md5( 2 ), 'email1@example.org', 'wp_foobar', md5( 2 ), '2018-01-03 03:04:05' );
		$model->addUser( 'wp_foobar1', md5( 3 ), 'email2@example.org', 'wp_foobar1', md5( 3 ), '2018-01-04 03:04:05' );

		$login = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'wp_foobar2', $login );
		$this->assertSame( 'wp_foobar2', User::get_matomo_user_login( $id1 ) );
	}

	public function test_ensure_user_exists_when_usermapping_exists_but_user_not_exists_in_matomo_will_create_that_user() {
		$id1  = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'foobar',
			)
		);
		$user = new WP_User( $id1 );

		User::map_matomo_user_login( $id1, 'wp_foobar434' );

		$login = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'wp_foobar434', $login );
		$this->assertSame( 'wp_foobar434', User::get_matomo_user_login( $id1 ) );
	}

	public function test_ensure_user_exists_updates_user_when_email_changes() {
		$id1  = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'foobaa',
				'user_email' => 'foobaz5@example.org',
			)
		);
		$user = new WP_User( $id1 );

		// create user
		$login = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'foobaa', $login );

		$matomo_user = $this->get_matomo_user( 'foobaa' );
		$this->assertSame( 'foobaz5@example.org', $matomo_user['email'] );

		// now we update the user
		$user->user_email = 'baafoo@example.org';
		$login            = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'foobaa', $login );

		$matomo_user = $this->get_matomo_user( 'foobaa' );
		$this->assertSame( 'baafoo@example.org', $matomo_user['email'] );

		// now we update the same user again but it should not do anything basically
		$login = $this->sync->ensure_user_exists( $user );
		$this->assertSame( 'foobaa', $login );

		$matomo_user = $this->get_matomo_user( 'foobaa' );
		$this->assertSame( 'baafoo@example.org', $matomo_user['email'] );
	}

	public function test_ensure_user_exists_does_not_reuse_a_login_still_mapped_to_another_user() {
		// two live WP users whose logins only differ by a space vs '_' and therefore
		// normalize to the same Matomo login
		$attacker_id = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'test_user',
				'user_email' => 'attacker@example.org',
			)
		);
		$victim_id   = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'test user',
				'user_email' => 'victim@example.org',
			)
		);

		// the victim WP -> Matomo user mapping still exists, even though the Matomo user
		// itself no longer exists.
		User::map_matomo_user_login( $victim_id, 'test_user' );

		$attacker_login = $this->sync->ensure_user_exists( new WP_User( $attacker_id ) );

		$this->assertNotSame( 'test_user', $attacker_login );
		$this->assertSame( 'test_user', User::get_matomo_user_login( $victim_id ) );
		$this->assertNotSame(
			User::get_matomo_user_login( $victim_id ),
			User::get_matomo_user_login( $attacker_id )
		);
	}

	public function test_ensure_user_exists_reallocates_when_retained_mapping_is_owned_by_another_user() {
		$attacker_id = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'test_user',
				'user_email' => 'attacker@example.org',
			)
		);
		$victim_id   = self::factory()->user->create(
			array(
				'role'       => 'editor',
				'user_login' => 'test user',
				'user_email' => 'victim@example.org',
			)
		);

		// corrupted pre-existing state: both WP users are mapped to the same Matomo login and
		// the matomo user exists
		$model = new Model();
		$model->addUser( 'test_user', md5( 1 ), 'attacker@example.org', 'test_user', md5( 1 ), '2018-01-02 03:04:05' );
		User::map_matomo_user_login( $attacker_id, 'test_user' );
		User::map_matomo_user_login( $victim_id, 'test_user' );

		// the victim must not keep the contested mapping when syncing
		$victim_login = $this->sync->ensure_user_exists( new WP_User( $victim_id ) );

		$this->assertNotSame( 'test_user', $victim_login );
		$this->assertSame( 'test_user', User::get_matomo_user_login( $attacker_id ) );
		$this->assertNotSame(
			User::get_matomo_user_login( $attacker_id ),
			User::get_matomo_user_login( $victim_id )
		);
	}

	public function test_colliding_wp_users_never_share_a_matomo_login_across_role_lifecycle() {
		$settings = new Settings();
		$caps     = new Capabilities( $settings );
		$caps->register_hooks();

		$attacker_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'test_user',
				'user_email' => 'attacker@example.org',
			)
		);
		$victim_id   = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'user_login' => 'test user',
				'user_email' => 'victim@example.org',
			)
		);

		// victim receives Matomo View
		( new WP_User( $victim_id ) )->add_role( Roles::ROLE_VIEW );
		$this->sync->sync_current_users();
		$this->assertNotEmpty( User::get_matomo_user_login( $victim_id ) );

		// victim goes back to subscriber -> the Matomo row AND the WP mapping must be removed
		( new WP_User( $victim_id ) )->remove_role( Roles::ROLE_VIEW );
		$this->sync->sync_current_users();
		$this->assertEmpty(
			User::get_matomo_user_login( $victim_id ),
			'a stale mapping must not survive deletion of the Matomo user'
		);

		// attacker receives Matomo View and may now be allocated the freed canonical login
		( new WP_User( $attacker_id ) )->add_role( Roles::ROLE_VIEW );
		$this->sync->sync_current_users();
		$this->assertNotEmpty( User::get_matomo_user_login( $attacker_id ) );
		$this->assertEmpty( User::get_matomo_user_login( $victim_id ) );

		// only the victim is promoted to an ordinary WordPress administrator (== superuser)
		( new WP_User( $victim_id ) )->add_role( 'administrator' );
		$this->sync->sync_current_users();

		$victim_login   = User::get_matomo_user_login( $victim_id );
		$attacker_login = User::get_matomo_user_login( $attacker_id );

		// the two WP users must never resolve to the same Matomo user
		$this->assertNotEmpty( $victim_login );
		$this->assertNotEmpty( $attacker_login );
		$this->assertNotSame( $attacker_login, $victim_login );

		// the superuser flag must land on the victim's own row, not the attacker's shared one
		$this->assertEquals( '1', $this->get_matomo_user( $victim_login )['superuser_access'] );
		$this->assertEquals( '0', $this->get_matomo_user( $attacker_login )['superuser_access'] );

		$caps->remove_hooks();
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_revoke_matomo_access_when_a_user_is_removed_from_a_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'betaviewer',
			]
		);
		add_user_to_blog( $beta, $user_id, Roles::ROLE_VIEW );

		// check that the user really does have Matomo View on the other blog before we remove them
		$this->switch_to_bootstrapped_blog( $beta );
		( new Sync() )->sync_current_users();
		$beta_idsite = $this->get_current_site_id();
		$login       = User::get_matomo_user_login( $user_id );
		$this->assertNotEmpty( $login );
		$this->assertEquals( [ $beta_idsite ], $this->get_view_sites_for( $login ) );
		$this->restore_bootstrapped_blog();

		( new Sync() )->register_hooks();

		remove_user_from_blog( $user_id, $beta );

		$this->switch_to_bootstrapped_blog( $beta );

		// sanity check: WordPress itself no longer considers the user able to view Matomo here, so
		// anything left below is Matomo's own stale state and not a broken fixture
		$this->assertFalse( user_can( new WP_User( $user_id ), Capabilities::KEY_VIEW ) );

		$this->assertEquals( [], $this->get_view_sites_for( $login ) );
		$this->assertEmpty( $this->get_matomo_user( $login ) );
		$this->assertFalse( User::get_matomo_user_login( $user_id ) );

		$this->restore_bootstrapped_blog();

		wp_delete_site( $beta );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_keep_matomo_access_on_other_blogs_when_a_user_is_removed_from_one_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'dualsiteviewer',
			]
		);
		add_user_to_blog( $beta, $user_id, Roles::ROLE_VIEW );

		// the user keeps Matomo View on the main blog, which is the access that must be preserved
		( new WP_User( $user_id ) )->add_role( Roles::ROLE_VIEW );
		( new Sync() )->sync_current_users();
		$main_idsite = $this->get_current_site_id();
		$main_login  = User::get_matomo_user_login( $user_id );
		$this->assertNotEmpty( $main_login );
		$this->assertEquals( [ $main_idsite ], $this->get_view_sites_for( $main_login ) );

		$this->switch_to_bootstrapped_blog( $beta );
		( new Sync() )->sync_current_users();
		$this->restore_bootstrapped_blog();

		( new Sync() )->register_hooks();

		remove_user_from_blog( $user_id, $beta );

		Bootstrap::do_bootstrap();

		$this->assertTrue( user_can( new WP_User( $user_id ), Capabilities::KEY_VIEW ) );
		$this->assertSame( $main_login, User::get_matomo_user_login( $user_id ) );
		$this->assertNotEmpty( $this->get_matomo_user( $main_login ) );
		$this->assertEquals( [ $main_idsite ], $this->get_view_sites_for( $main_login ) );

		wp_delete_site( $beta );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_revoke_matomo_superuser_access_on_every_blog_when_super_admin_is_revoked() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'netadmin',
			]
		);
		grant_super_admin( $user_id );

		$logins = $this->sync_and_get_logins_per_blog( $user_id, [ $beta ] );

		foreach ( $logins as $blog_id => $login ) {
			$this->assertSame( '1', $this->get_matomo_user_on_blog( $blog_id, $login )['superuser_access'] );
		}

		( new Sync() )->register_hooks();

		revoke_super_admin( $user_id );

		// sanity check: WordPress itself no longer considers them a Matomo superuser, so anything
		// left below is Matomo's own stale state and not a broken fixture
		$this->assertFalse( user_can( new WP_User( $user_id ), Capabilities::KEY_SUPERUSER ) );

		foreach ( $logins as $blog_id => $login ) {
			// a subscriber has no Matomo capability at all, so they are removed
			$this->assertEmpty( $this->get_matomo_user_on_blog( $blog_id, $login ) );
		}

		wp_delete_site( $beta );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_grant_matomo_superuser_access_on_every_blog_when_super_admin_is_granted() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'futureadmin',
			]
		);

		$logins = $this->sync_and_get_logins_per_blog( $user_id, [ $beta ] );

		foreach ( $logins as $login ) {
			$this->assertFalse( $login, 'a subscriber should not be in Matomo yet' );
		}

		( new Sync() )->register_hooks();

		grant_super_admin( $user_id );

		foreach ( array_keys( $logins ) as $blog_id ) {
			$this->switch_to_bootstrapped_blog( $blog_id );
			$login = User::get_matomo_user_login( $user_id );
			$this->assertNotEmpty( $login );
			$this->assertSame( '1', $this->get_matomo_user( $login )['superuser_access'] );
			$this->restore_bootstrapped_blog();
		}

		wp_delete_site( $beta );
	}

	/**
	 * @group ms-required
	 */
	public function test_on_super_admin_change_should_correct_blogs_other_than_the_current_one() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_login' => 'formeradmin',
			]
		);
		grant_super_admin( $user_id );

		$logins = $this->sync_and_get_logins_per_blog( $user_id, [ $beta ] );

		// take the status away without the hooks registered, leaving the flag stale everywhere
		revoke_super_admin( $user_id );

		foreach ( $logins as $blog_id => $login ) {
			$this->assertSame( '1', $this->get_matomo_user_on_blog( $blog_id, $login )['superuser_access'] );
		}

		( new Sync() )->on_super_admin_change( $user_id );

		foreach ( $logins as $blog_id => $login ) {
			$this->assertEmpty( $this->get_matomo_user_on_blog( $blog_id, $login ) );
		}

		wp_delete_site( $beta );
	}

	public function test_on_remove_user_from_blog_should_not_change_matomo_access_on_its_own() {
		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		( new Sync() )->on_remove_user_from_blog( $user_id, get_current_blog_id() );

		$this->assertEquals( [ $this->get_current_site_id() ], $this->get_view_sites_for( $login ) );
		$this->assertNotEmpty( $this->get_matomo_user( $login ) );
		$this->assertSame( $login, User::get_matomo_user_login( $user_id ) );
	}

	public function test_on_remove_user_from_blog_should_fall_back_to_the_current_blog_when_no_blog_id_is_given() {
		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		$sync = new Sync();
		$sync->on_remove_user_from_blog( $user_id, 0 );

		$this->remove_matomo_view_from_user( $user_id );

		$sync->on_clean_user_cache( $user_id );

		$this->assertEquals( [], $this->get_view_sites_for( $login ) );
		$this->assertEmpty( $this->get_matomo_user( $login ) );
	}

	public function test_on_clean_user_cache_should_do_nothing_when_the_user_was_not_removed() {
		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		// the capability is gone but no removal was recorded, so this is one of the many unrelated
		// clean_user_cache() calls that must be ignored
		$this->remove_matomo_view_from_user( $user_id );

		( new Sync() )->on_clean_user_cache( $user_id );

		$this->assertEquals( [ $this->get_current_site_id() ], $this->get_view_sites_for( $login ) );
		$this->assertNotEmpty( $this->get_matomo_user( $login ) );
	}

	public function test_on_clean_user_cache_should_do_nothing_when_the_removal_was_recorded_for_a_different_blog() {
		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		$this->remove_matomo_view_from_user( $user_id );

		$sync = new Sync();
		$sync->on_remove_user_from_blog( $user_id, get_current_blog_id() + 1234 );

		$sync->on_clean_user_cache( $user_id );

		$this->assertEquals( [ $this->get_current_site_id() ], $this->get_view_sites_for( $login ) );
		$this->assertNotEmpty( $this->get_matomo_user( $login ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_on_clean_user_cache_should_flush_only_the_blog_it_is_called_on() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->activate_matomo_plugin();

		$beta = $this->create_blog_with_matomo();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		add_user_to_blog( $beta, $user_id, Roles::ROLE_VIEW );

		$main_login = $this->grant_matomo_view_and_sync( $user_id );

		$this->switch_to_bootstrapped_blog( $beta );
		( new Sync() )->sync_current_users();
		$beta_idsite = $this->get_current_site_id();
		$beta_login  = User::get_matomo_user_login( $user_id );
		$this->assertEquals( [ $beta_idsite ], $this->get_view_sites_for( $beta_login ) );
		$this->remove_matomo_view_from_user( $user_id );
		$this->restore_bootstrapped_blog();

		$this->remove_matomo_view_from_user( $user_id );

		$sync = new Sync();
		$sync->on_remove_user_from_blog( $user_id, $beta );
		$sync->on_remove_user_from_blog( $user_id, get_current_blog_id() );

		// flushing on the main blog must leave the queued entry for the other blog alone
		$sync->on_clean_user_cache( $user_id );

		$this->assertEquals( [], $this->get_view_sites_for( $main_login ) );

		$this->switch_to_bootstrapped_blog( $beta );
		$this->assertEquals( [ $beta_idsite ], $this->get_view_sites_for( $beta_login ) );

		$sync->on_clean_user_cache( $user_id );

		$this->assertEquals( [], $this->get_view_sites_for( $beta_login ) );
		$this->restore_bootstrapped_blog();

		wp_delete_site( $beta );
	}

	public function test_on_clean_user_cache_should_keep_access_for_a_user_who_still_has_the_capability() {
		$this->activate_matomo_plugin();

		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		$sync = new Sync();
		$sync->on_remove_user_from_blog( $user_id, get_current_blog_id() );

		// deliberately not taking the capability away
		$sync->on_clean_user_cache( $user_id );

		$this->assertEquals( [ $this->get_current_site_id() ], $this->get_view_sites_for( $login ) );
		$this->assertNotEmpty( $this->get_matomo_user( $login ) );
		$this->assertSame( $login, User::get_matomo_user_login( $user_id ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_downgrade_an_access_row_that_outranks_the_wp_capability() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		// the admin access a sync left behind before the user was downgraded to view in WordPress
		$this->set_access_for_current_site( $login, Admin::ID );

		$this->assertTrue( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );

		$this->assertSame( View::ID, $this->get_access_for_current_site( $login ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_leave_an_access_row_that_matches_the_wp_capability() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		$this->assertFalse( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );

		$this->assertSame( View::ID, $this->get_access_for_current_site( $login ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_not_upgrade_an_access_row_below_the_wp_capability() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		// promoted in WordPress but not synced yet. granting access is a sync's job, not ours
		( new WP_User( $user_id ) )->add_role( Roles::ROLE_ADMIN );
		$this->assertTrue( user_can( new WP_User( $user_id ), Capabilities::KEY_ADMIN ) );

		$this->assertFalse( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );

		$this->assertSame( View::ID, $this->get_access_for_current_site( $login ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_clear_a_stale_superuser_flag() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		// the flag a sync left behind after the WordPress side was downgraded
		( new Model() )->setSuperUserAccess( $login, true );
		$this->assertNotEmpty( $this->get_matomo_user( $login )['superuser_access'] );

		$this->assertTrue( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );

		$this->assertEmpty( $this->get_matomo_user( $login )['superuser_access'] );
		$this->assertSame( View::ID, $this->get_access_for_current_site( $login ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_do_nothing_when_matomo_capabilities_cannot_be_resolved() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		$login   = $this->grant_matomo_view_and_sync( $user_id );

		$this->set_access_for_current_site( $login, Admin::ID );

		$capabilities = WpMatomo::get_active_feature( Capabilities::class );
		$this->assertNotEmpty( $capabilities, 'Capabilities is expected to be registered by default' );

		// safe mode: without the user_has_cap filter nobody resolves any matomo capability, so
		// every user would look like they no longer qualify for the access they have
		$capabilities->remove_hooks();
		try {
			$this->assertFalse( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );
		} finally {
			$capabilities->register_hooks();
		}

		$this->assertSame( Admin::ID, $this->get_access_for_current_site( $login ) );
	}

	public function test_sync_user_if_access_exceeds_capabilities_should_do_nothing_when_the_user_is_not_mapped_to_a_matomo_user() {
		$user_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );

		$this->assertEmpty( User::get_matomo_user_login( $user_id ) );

		$this->assertFalse( ( new Sync() )->sync_user_if_access_exceeds_capabilities( $user_id ) );
	}

	/**
	 * @param callable $callback
	 * @return int[] the `number` query var of every blog query the callback made
	 */
	private function capture_blog_query_limits( $callback ) {
		$limits = [];

		$capture = function ( $query ) use ( &$limits ) {
			$limits[] = (int) $query->query_vars['number'];
		};

		add_action( 'pre_get_sites', $capture );
		try {
			$callback();
		} finally {
			remove_action( 'pre_get_sites', $capture );
		}

		return $limits;
	}

	private function get_matomo_user( $login ) {
		$model = new Model();

		return $model->getUser( $login );
	}

	/**
	 * @param int   $wp_user_id
	 * @param int[] $other_blog_ids
	 * @return array<int, string|false>
	 */
	private function sync_and_get_logins_per_blog( $wp_user_id, $other_blog_ids ) {
		$logins = [];

		foreach ( array_merge( [ get_current_blog_id() ], $other_blog_ids ) as $blog_id ) {
			$this->switch_to_bootstrapped_blog( $blog_id );
			( new Sync() )->sync_current_users();
			$logins[ $blog_id ] = User::get_matomo_user_login( $wp_user_id );
			$this->restore_bootstrapped_blog();
		}

		return $logins;
	}

	/**
	 * @param int    $blog_id
	 * @param string $login
	 *
	 * @return array
	 */
	private function get_matomo_user_on_blog( $blog_id, $login ) {
		$this->switch_to_bootstrapped_blog( $blog_id );
		$matomo_user = $this->get_matomo_user( $login );
		$this->restore_bootstrapped_blog();

		return $matomo_user;
	}

	/**
	 * @param string $login
	 * @return string|null the Matomo access the login has to the current site
	 */
	private function get_access_for_current_site( $login ) {
		$idsite = $this->get_current_site_id();

		foreach ( ( new Model() )->getSitesAccessFromUser( $login ) as $access ) {
			if ( (int) $access['site'] === (int) $idsite ) {
				return $access['access'];
			}
		}

		return null;
	}

	/**
	 * @param string $login
	 * @param string $role
	 */
	private function set_access_for_current_site( $login, $role ) {
		$model  = new Model();
		$idsite = $this->get_current_site_id();

		$model->deleteUserAccess( $login, [ $idsite ] );
		$model->addUserAccess( $login, $role, [ $idsite ] );

		$this->assertSame( $role, $this->get_access_for_current_site( $login ) );
	}

	private function activate_matomo_plugin() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_multisite() ) {
			update_site_option( 'active_sitewide_plugins', [ 'matomo/matomo.php' => time() ] );
		} else {
			// is_plugin_active_for_network() always returns false outside multisite
			update_option( 'active_plugins', [ 'matomo/matomo.php' ] );
		}

		$this->assertTrue( is_plugin_active( 'matomo/matomo.php' ) );
	}

	/**
	 * @param int $wp_user_id
	 * @return string the Matomo login the user was synced to
	 */
	private function grant_matomo_view_and_sync( $wp_user_id ) {
		( new WP_User( $wp_user_id ) )->add_role( Roles::ROLE_VIEW );

		( new Sync() )->sync_current_users();

		$login = User::get_matomo_user_login( $wp_user_id );
		$this->assertNotEmpty( $login );
		$this->assertEquals( [ $this->get_current_site_id() ], $this->get_view_sites_for( $login ) );

		return $login;
	}

	/**
	 * @param int $wp_user_id
	 */
	private function remove_matomo_view_from_user( $wp_user_id ) {
		( new WP_User( $wp_user_id ) )->remove_role( Roles::ROLE_VIEW );

		$this->assertFalse( user_can( new WP_User( $wp_user_id ), Capabilities::KEY_VIEW ) );
	}

	private function create_blog_with_matomo() {
		$blog_id = self::factory()->blog->create();

		( new \WpMatomo\Site\Sync( new Settings() ) )->sync_all();

		$this->assertNotEmpty( Site::get_matomo_site_id( $blog_id ) );

		return $blog_id;
	}

	private function switch_to_bootstrapped_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		Bootstrap::do_bootstrap();
	}

	private function restore_bootstrapped_blog() {
		restore_current_blog();
		Bootstrap::do_bootstrap();
	}

	/**
	 * @param string $login
	 * @return array
	 */
	private function get_view_sites_for( $login ) {
		$view_access = ( new Model() )->getUsersSitesFromAccess( 'view' );
		return isset( $view_access[ $login ] ) ? $view_access[ $login ] : [];
	}
}
