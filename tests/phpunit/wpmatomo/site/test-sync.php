<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model;
use WpMatomo\Bootstrap;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Site\Sync;
use WpMatomo\Db\Settings as DbSettings;
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

class SiteSyncTest extends MatomoAnalytics_SharedFixture_TestCase {

	/**
	 * @var Sync
	 */
	private $sync;

	/**
	 * @var MockMatomoSiteSync
	 */
	private $mock;

	/**
	 * @var Site
	 */
	private $site;

	public function setUp(): void {
		parent::setUp();

		$settings   = new Settings();
		$this->sync = new Sync( $settings );
		$this->mock = new MockMatomoSiteSync( $settings );
		$this->site = new Site();
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
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
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

	/**
	 * @group ms-required
	 */
	public function test_sync_all_should_not_limit_the_number_of_blogs_it_syncs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$limits = [];

		$capture = function ( $query ) use ( &$limits ) {
			$limits[] = (int) $query->query_vars['number'];
		};

		add_action( 'pre_get_sites', $capture );
		try {
			$this->mock->sync_all();
		} finally {
			remove_action( 'pre_get_sites', $capture );
		}

		$this->assertNotEmpty( $limits, 'expected sync_all() to query the list of blogs' );
		$this->assertSame(
			[ 0 ],
			array_values( array_unique( $limits ) ),
			'WP_Site_Query defaults to 100 blogs, so a limit leaves the rest without a Matomo site'
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
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
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

	/**
	 * In this case (multisite mode), we can create and update existing sites,
	 * Matomo data will be updated then.
	 *
	 * @return void
	 */
	public function test_sync_site_creates_new_matomo_site_when_blogid_is_unknown_and_updates_when_needed_in_multisite_mode() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}
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

	/**
	 * In this case (non-multisite mode), we can update anything we want,
	 * we'll still have only one Matomo site and we'll only update that one.
	 *
	 * @return void
	 */
	public function test_sync_site_creates_new_matomo_site_when_blogid_is_unknown_and_updates_when_needed_in_non_multisite_mode() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}
		$matomo_sites = new Model();
		$this->assertCount( 1, $matomo_sites->getAllSites() );

		$blogid = 243;
		$this->assertEmpty( Site::get_matomo_site_id( $blogid ) );
		$this->assertTrue( $this->sync->sync_site( $blogid, 'myname422', 'https://baz1.foobar.com' ) );

		$matomo_sites_records = $this->get_matomo_sites_records();
		$expected_id_site     = Site::get_matomo_site_id( $blogid );
		$this->assertEquals( $matomo_sites_records[0]->idsite, $expected_id_site );

		$sites = $matomo_sites->getAllSites();

		$this->assertCount( 1, $sites );
		$this->assertSame( 'myname422', $sites[0]['name'] );
		$this->assertSame( 'https://baz1.foobar.com', $sites[0]['main_url'] );
		$this->assertSame( 'UTC', $sites[0]['timezone'] );
		$this->assertEquals( '1', $sites[0]['ecommerce'] );

		// now we updated
		$this->assertTrue( $this->sync->sync_site( $blogid, 'myname422changed', 'https://changed1.foobar.com' ) );

		$expected_id_site = Site::get_matomo_site_id( $blogid );
		$this->assertEquals( 1, $expected_id_site );

		$sites = $matomo_sites->getAllSites();
		$this->assertCount( 1, $sites );
		$this->assertEquals( $expected_id_site, $sites[0]['idsite'] );
		$this->assertSame( 'myname422changed', $sites[0]['name'] );
		$this->assertSame( 'https://changed1.foobar.com', $sites[0]['main_url'] );
	}

	/**
	 * In non-multisite mode, we expect to have only one record in the matomo_site table
	 * and the mapping of this id in the wp_option table.
	 * When the sync process is not able to find the mapping value, it creates a new Matomo site record.
	 * This case should not exist in a non-multisite context.
	 *
	 * This case can happen for example when some configuration options have been deleted by a plugin.
	 *
	 * We check with this test that the sync process is able to find the expected matomo site id,
	 * and restore the expected configuration option
	 *
	 * @return void
	 */
	public function test_create_id_mapping_from_matomo_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}
		global $wpdb;
		$options_table_name = $wpdb->prefix . 'options';
        // @phpcs:ignore WordPress.DB
		$wpdb->query( "DELETE FROM $options_table_name WHERE option_name LIKE '" . $this->site::SITE_MAPPING_PREFIX . "%'" );
		wp_cache_flush();
		$this->assertCount( 0, $this->get_wp_site_mapping_records() );

		$sites = $this->get_matomo_sites_records();
		$this->assertCount( 1, $sites );

		$wp_mapping = $this->get_wp_site_mapping_records();
		$this->assertCount( 0, $wp_mapping );
		// here we don't have idsite mapping in WordPress, and one site in the Matomo table
		$idsite = (int) $sites[0]->idsite;
		// sync
		$this->assertTrue( $this->sync->sync_current_site() );

		// check if the WordPress mapping is not made on the Matomo site
		$wp_mapping = $this->get_wp_site_mapping_records();
		$this->assertCount( 1, $wp_mapping );
		$this->assertEquals( $idsite, (int) $wp_mapping[0]->option_value );
	}

	/**
	 * Same thing than the previous test case but in this one the mapping id does not match the
	 * Matomo site record. It can happen when a database has been migrated from one environment to another.
	 *
	 * In this case (non-multisite, one record in Matomo site, one mapping option in wp_options but with a different id
	 * than the expected one), we expect that the sync process will restore the expected value in the wp_option.
	 *
	 * @return void
	 */
	public function test_sync_id_mapping_from_matomo_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'Multisite.' );
			return;
		}
		global $wpdb;
		$options_table_name = $wpdb->prefix . 'options';
        // @phpcs:ignore WordPress.DB
		$wpdb->query( "DELETE FROM $options_table_name WHERE option_name LIKE '" . $this->site::SITE_MAPPING_PREFIX . "%'" );
		wp_cache_flush();
		$this->assertCount( 0, $this->get_wp_site_mapping_records() );

		$sites = $this->get_matomo_sites_records();
		$this->assertCount( 1, $sites );
		$idsite = (int) $sites[0]->idsite;

		$false_mapping_idsite = $idsite + 1;
		// create a false mapping
		$this->assertTrue( add_site_option( $this->site::SITE_MAPPING_PREFIX . get_current_blog_id(), $false_mapping_idsite ) );
		wp_cache_flush();
        // @phpcs:ignore WordPress.DB
		$mappings = $wpdb->get_results( "SELECT * FROM $options_table_name WHERE option_name = '" . $this->site::SITE_MAPPING_PREFIX . get_current_blog_id() . "' LIMIT 1" );
		$this->assertEquals( $false_mapping_idsite, (int) $mappings[0]->option_value );
		// here the mapping is not correct in the WP DB
		// sync
		$this->assertTrue( $this->sync->sync_current_site() );

		$wp_mapping = $this->get_wp_site_mapping_records();
		$this->assertCount( 1, $wp_mapping );
		$this->assertEquals( $idsite, (int) $wp_mapping[0]->option_value );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_drop_the_matomo_tables_of_a_deleted_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();
		$this->assertNotEmpty( $this->get_matomo_tables_for_blog( $blog_id ) );

		wp_delete_site( $blog_id );

		$this->assertSame( [], $this->get_matomo_tables_for_blog( $blog_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_remove_the_site_mapping_of_a_deleted_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		$this->assertNotEmpty( Site::get_matomo_site_id( $blog_id ) );

		wp_delete_site( $blog_id );

		$this->assertEmpty( Site::get_matomo_site_id( $blog_id ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_keep_the_matomo_data_of_other_blogs_when_one_blog_is_deleted() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$deleted_blog_id = $this->create_blog_with_matomo();
		$kept_blog_id    = $this->create_blog_with_matomo();

		$kept_tables = $this->get_matomo_tables_for_blog( $kept_blog_id );
		$kept_idsite = Site::get_matomo_site_id( $kept_blog_id );
		$this->assertNotEmpty( $kept_tables );
		$this->assertNotEmpty( $kept_idsite );

		wp_delete_site( $deleted_blog_id );

		// the mapping is deleted per blog, not with a LIKE across the whole network
		$this->assertSame( $kept_tables, $this->get_matomo_tables_for_blog( $kept_blog_id ) );
		$this->assertSame( $kept_idsite, Site::get_matomo_site_id( $kept_blog_id ) );

		wp_delete_site( $kept_blog_id );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_keep_the_matomo_data_of_a_blog_that_is_only_flagged_deleted() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		$tables = $this->get_matomo_tables_for_blog( $blog_id );
		$idsite = Site::get_matomo_site_id( $blog_id );
		$this->assertNotEmpty( $tables );

		// flagging a blog deleted is reversible, so nothing may be destroyed
		wpmu_delete_blog( $blog_id, false );

		$this->assertSame( $tables, $this->get_matomo_tables_for_blog( $blog_id ) );
		$this->assertSame( $idsite, Site::get_matomo_site_id( $blog_id ) );

		wp_delete_site( $blog_id );
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_sync_a_blog_that_has_been_restored_and_is_no_longer_flagged_deleted() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// restoring a blog only ever happens in the network admin, and the sync is skipped on front
		// end requests
		$this->assume_admin_page();

		$blog_id = $this->create_blog_with_matomo();
		$idsite  = Site::get_matomo_site_id( $blog_id );

		update_blog_status( $blog_id, 'deleted', '1' );

		// renamed before the hooks are registered, so update_option_blogname cannot sync it for us
		// and the only thing left that could is the restore below
		switch_to_blog( $blog_id );
		update_option( 'blogname', 'Renamed While Deleted' );
		restore_current_blog();

		// sync_all() skips blogs flagged deleted, so the Matomo site stays stale
		$this->sync->sync_all();
		$this->assertNotSame( 'Renamed While Deleted', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

		$this->sync->register_hooks();

		update_blog_status( $blog_id, 'deleted', '0' );

		$this->assertSame( 'Renamed While Deleted', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

		wp_delete_site( $blog_id );
	}

	private function create_blog_with_matomo() {
		$blog_id = self::factory()->blog->create();

		$this->sync->sync_all();

		$this->assertNotEmpty( Site::get_matomo_site_id( $blog_id ) );

		return $blog_id;
	}

	/**
	 * every blog has its own matomo_site table, so the row has to be read while switched to it
	 *
	 * @param int $blog_id
	 * @param int $idsite
	 * @return string
	 */
	private function get_matomo_site_name_for_blog( $blog_id, $idsite ) {
		switch_to_blog( $blog_id );
		Bootstrap::do_bootstrap();

		$site = ( new Model() )->getSiteFromId( $idsite );

		restore_current_blog();
		Bootstrap::do_bootstrap();

		return $site['name'];
	}

	/**
	 * @param int $blog_id
	 * @return string[]
	 */
	private function get_matomo_tables_for_blog( $blog_id ) {
		switch_to_blog( $blog_id );
		$tables = ( new DbSettings() )->get_installed_matomo_tables();
		restore_current_blog();

		return $tables;
	}

	/**
	 * get the mapping options in the wp_options table
	 *
	 * @return stdClass[]|null
	 */
	private function get_wp_site_mapping_records() {
		global $wpdb;
		$options_table_name = $wpdb->prefix . 'options';
        // @phpcs:ignore WordPress.DB
		return $wpdb->get_results( "SELECT * FROM $options_table_name WHERE option_name LIKE '" . $this->site::SITE_MAPPING_PREFIX . "%'" );
	}

	/**
	 * get the records in the matomo_site table
	 *
	 * @return stdClass[]|null
	 */
	private function get_matomo_sites_records() {
		global $wpdb;
		$db_settings     = new DbSettings();
		$site_table_name = $db_settings->prefix_table_name( 'site' );
        // @phpcs:ignore WordPress.DB
		return $wpdb->get_results( "SELECT idsite, main_url FROM $site_table_name" );
	}
}
