<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Site\Sync;

class MockMatomoSiteSync extends Sync {
	public $synced_sites = array();

	public function sync_site( $blog_id, $blog_name, $blog_url ) {
		$this->synced_sites[] = array(
			'id'   => $blog_id,
			'name' => $blog_name,
			'url'  => $blog_url,
		);
	}
}

class SiteSyncTest extends MatomoAnalytics_TestCase {

	/**
	 * @var Sync
	 */
	private $sync;
	/**
	 * @var MockMatomoSiteSync
	 */
	private $mock;

	public function setUp() {
		parent::setUp();

		$settings   = new Settings();
		$this->sync = new Sync( $settings );
		$this->mock = new MockMatomoSiteSync( $settings );
	}

	public function test_sync_all_does_not_fail() {
		$this->assertTrue( $this->sync->sync_all() );
	}

	public function test_sync_all_passes_correct_values_to_sync_site() {
		$this->mock->sync_all();
		$this->assertEquals(
			array(
				array(
					'id'   => 1,
					'name' => 'Test Blog',
					'url'  => 'http://example.org',
				),
			),
			$this->mock->synced_sites
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_all_passes_correct_values_to_sync_site_when_there_are_multiple_blogs() {
		if (!is_multisite()) {
			$this->markTestSkipped('Not multisite.');
			return;
		}
		$blogid1 = self::factory()->blog->create(
			array(
				'domain' => 'foobar.com',
				'title'  => 'Site 22',
				'path'   => '/testpath22',
			)
		);
		$blogid2 = self::factory()->blog->create(
			array(
				'domain' => 'foobar.baz',
				'title'  => 'Site 23',
				'path'   => '/testpath23',
			)
		);

		$this->mock->sync_all();
		wp_delete_site( $blogid1 );
		wp_delete_site( $blogid2 );
		$this->assertEquals(
			array(
				array(
					'id'   => 1,
					'name' => 'Test Blog',
					'url'  => 'http://example.org',
				),
				array(
					'id'   => $blogid1,
					'name' => 'Site 22',
					'url'  => 'http://foobar.com/testpath22',
				),
				array(
					'id'   => $blogid2,
					'name' => 'Site 23',
					'url'  => 'http://foobar.baz/testpath23',
				),
			),
			$this->mock->synced_sites
		);
	}

	public function test_sync_current_site_does_not_fail() {
		$this->assertTrue( $this->sync->sync_current_site() );
	}

	public function test_sync_current_site_passes_correct_values_to_sync_site() {
		$this->mock->sync_current_site();
		$this->assertEquals(
			array(
				array(
					'id'   => 1,
					'name' => 'Test Blog',
					'url'  => 'http://example.org',
				),
			),
			$this->mock->synced_sites
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_current_site_passes_correct_values_to_sync_site_when_we_are_on_different_blog() {
		if (!is_multisite()) {
			$this->markTestSkipped('Not multisite.');
			return;
		}
		$blogid1 = self::factory()->blog->create(
			array(
				'domain' => 'foobar.com',
				'title'  => 'Site 24',
				'path'   => '/testpath24',
			)
		);
		switch_to_blog( $blogid1 );

		$this->mock->sync_current_site();

		wp_delete_site( $blogid1 );

		$this->assertEquals(
			array(
				array(
					'id'   => $blogid1,
					'name' => 'Site 24',
					'url'  => 'http://foobar.com/testpath24',
				),
			),
			$this->mock->synced_sites
		);
	}

	public function test_sync_site_creates_new_matomo_site_when_blogid_is_unknown_and_updates_when_needed() {
		$matomo_sites = new Model();
		$this->assertCount( 1, $matomo_sites->getAllSites() );

		$blogid = 243;
		$this->assertEmpty( Site::get_matomo_site_id( $blogid ) );
		$this->assertTrue( $this->sync->sync_site( $blogid, 'myname422', 'https://baz1.foobar.com' ) );

		$created_idsite = Site::get_matomo_site_id( $blogid );
		$this->assertEquals( 2, $created_idsite );

		$sites = $matomo_sites->getAllSites();

		$this->assertCount( 2, $sites );
		$this->assertEquals( $created_idsite, $sites[1]['idsite'] );
		$this->assertSame( 'myname422', $sites[1]['name'] );
		$this->assertSame( 'https://baz1.foobar.com', $sites[1]['main_url'] );
		$this->assertSame( 'UTC', $sites[1]['timezone'] );
		$this->assertEquals( '1', $sites[1]['ecommerce'] );

		// now we updated
		$this->assertTrue( $this->sync->sync_site( $blogid, 'myname422changed', 'https://changed1.foobar.com' ) );

		$created_idsite = Site::get_matomo_site_id( $blogid );
		$this->assertEquals( 2, $created_idsite );

		$sites = $matomo_sites->getAllSites();
		$this->assertCount( 2, $sites );
		$this->assertEquals( $created_idsite, $sites[1]['idsite'] );
		$this->assertSame( 'myname422changed', $sites[1]['name'] );
		$this->assertSame( 'https://changed1.foobar.com', $sites[1]['main_url'] );
	}

}
