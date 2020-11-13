<?php
/**
 * @package matomo
 */

use Piwik\Plugins\UsersManager\Model;
use WpMatomo\Access;
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

	public function ensure_user_exists( $wp_user ) {
		return parent::ensure_user_exists( $wp_user );
	}
}

class UserSyncTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Sync
	 */
	private $sync;

	/**
	 * @var MockMatomoUserSync
	 */
	private $mock;

	public function setUp() {
		parent::setUp();

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
		$blogid1   = self::factory()->blog->create( array( 'domain' => 'foobar.com' ) );
		$blogid2   = self::factory()->blog->create( array( 'domain' => 'foobar.baz' ) );
		$site_sync = new \WpMatomo\Site\Sync( new Settings() );

		$site_sync->sync_all();

		$this->mock->synced_users = array();
		$this->mock->sync_all();

		$idsite = $this->get_current_site_id();

		switch_to_blog($blogid1);
		$user2 = get_user_by('login', 'admin');
		restore_current_blog();
		switch_to_blog($blogid2);
		$user3 = get_user_by('login', 'admin');
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
			$this->assertEquals( '1', $matomo_user['superuser_access'] );
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
		$this->assertNotEmpty( $matomo_user['token_auth'] );
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

	private function get_matomo_user( $login ) {
		$model = new Model();

		return $model->getUser( $login );
	}
}
