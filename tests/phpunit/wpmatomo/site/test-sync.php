<?php
/**
 * @package matomo
 */

use Piwik\Plugins\SitesManager\Model;
use WpMatomo\Admin\TrackingSettings;
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

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_not_copy_source_blog_tracking_settings_into_another_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// destination blog, installed and synced with its own default settings,
		$dest_blog = $this->create_blog_with_matomo();

		// emulate a request that starts on the source blog: the Settings object is
		// built while the source blog is current and then handed to the scheduled sync
		$source_settings = $this->make_network_enabled_settings();
		// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
		$injected_code = '<script src="https://somesite.com/other-script.js"></script>';
		$source_settings->set_global_option( 'track_mode', 'manually' );
		$source_settings->set_global_option( 'track_admin', true );
		$source_settings->set_global_option( 'track_codeposition', 'header' );
		$source_settings->set_option( 'tracking_code', $injected_code );
		$source_settings->save();

		// renaming the destination blog is what makes sync_site() take its metadata-update
		// branch, which is where the tracking settings get written
		switch_to_blog( $dest_blog );
		try {
			update_option( 'blogname', 'Renamed Destination Blog' );

			// the scheduled sync switches to the destination blog but keeps using the source
			// scoped Settings object
			( new Sync( $source_settings ) )->sync_current_site();
			$stored_dest_options = get_option( Settings::OPTION, [] );
		} finally {
			restore_current_blog();
			wp_delete_site( $dest_blog );
		}

		$stored_dest_code = isset( $stored_dest_options['tracking_code'] ) ? $stored_dest_options['tracking_code'] : '';

		$this->assertNotSame(
			$injected_code,
			$stored_dest_code,
			'the source blog manual tracking code must not be written into another blog during sync'
		);
		$this->assertStringNotContainsString( 'other-script.js', $stored_dest_code );
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_invalidate_the_tracking_code_of_the_synced_blog() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		// what the tracking code generator sees when it reacts to matomo_site_synced. it runs on
		// the default priority, so this has to run before it to observe the invalidation itself
		// rather than the fresh timestamp the generator writes
		$seen_as_current = null;

		switch_to_blog( $blog_id );
		try {
			$settings = $this->make_network_enabled_settings();
			$settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );

			// is_current_tracking_code() compares the two strictly, so both are set rather
			// than left to whatever second the fixture happened to write
			$settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() - 100 );
			$settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 50 );
			$settings->save();

			$this->assertTrue( $settings->is_current_tracking_code() );

			$listener = function () use ( $settings, &$seen_as_current ) {
				$seen_as_current = $settings->is_current_tracking_code();
			};
			add_action( 'matomo_site_synced', $listener, 1 );

			try {
				update_option( 'blogname', 'Renamed Blog Needing A New Tracking Code' );
				( new Sync( $settings ) )->sync_current_site();
			} finally {
				remove_action( 'matomo_site_synced', $listener, 1 );
			}
		} finally {
			restore_current_blog();
			wp_delete_site( $blog_id );
		}

		$this->assertFalse(
			$seen_as_current,
			'a blog whose metadata changed must have its own tracking code invalidated before matomo_site_synced fires'
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_not_fire_the_tracking_settings_changed_action_when_only_metadata_changed() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		$site_synced_fired               = 0;
		$tracking_settings_changed_fired = 0;

		$on_site_synced = function () use ( &$site_synced_fired ) {
			++$site_synced_fired;
		};

		$on_tracking_settings_changed = function () use ( &$tracking_settings_changed_fired ) {
			++$tracking_settings_changed_fired;
		};

		switch_to_blog( $blog_id );
		try {
			$settings = $this->make_network_enabled_settings();
			$settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
			$settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() - 100 );
			$settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 50 );
			$settings->save();

			update_option( 'blogname', 'Renamed Blog Whose Tracking Settings Did Not Change' );

			// hooked after the setup above, so only what the sync itself fires is counted
			add_action( 'matomo_site_synced', $on_site_synced );
			add_action( 'matomo_tracking_settings_changed', $on_tracking_settings_changed );

			try {
				( new Sync( $settings ) )->sync_current_site();
			} finally {
				remove_action( 'matomo_tracking_settings_changed', $on_tracking_settings_changed );
				remove_action( 'matomo_site_synced', $on_site_synced );
			}
		} finally {
			restore_current_blog();
			wp_delete_site( $blog_id );
		}

		// so a sync that never reached its metadata-update branch cannot satisfy the one below
		$this->assertSame( 1, $site_synced_fired );

		$this->assertSame(
			0,
			$tracking_settings_changed_fired,
			'a sync that only updated a blog\'s metadata changed no tracking setting'
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_invalidate_the_tracking_code_when_the_blog_gets_a_new_matomo_site() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		$seen_as_current = null;

		switch_to_blog( $blog_id );
		try {
			$settings = $this->make_network_enabled_settings();
			$settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
			$settings->set_option( 'tracking_code', '<!-- tracking code of the previous matomo site -->' );
			// is_current_tracking_code() compares the two strictly, so both are pinned rather
			// than left to whatever second the fixture happened to write
			$settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() - 100 );
			$settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 50 );
			$settings->save();

			$this->assertTrue( $settings->is_current_tracking_code() );

			// dropping the mapping is what sends sync_site() down its create branch, the same
			// way deleting the Matomo site of a blog does. the blog comes back with a brand new
			// Matomo site id, which the tracking code it kept knows nothing about
			Site::map_matomo_site_id( $blog_id, null );

			$listener = function () use ( $settings, &$seen_as_current ) {
				$seen_as_current = $settings->is_current_tracking_code();
			};
			add_action( 'matomo_site_synced', $listener, 1 );

			try {
				( new Sync( $settings ) )->sync_current_site();
			} finally {
				remove_action( 'matomo_site_synced', $listener, 1 );
			}
		} finally {
			restore_current_blog();
			wp_delete_site( $blog_id );
		}

		$this->assertFalse(
			$seen_as_current,
			'a blog that was given a new Matomo site must have its tracking code invalidated before matomo_site_synced fires'
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_not_invalidate_a_manually_entered_tracking_code() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = $this->create_blog_with_matomo();

		// a manually entered tracking code is never regenerated, so there is nothing to
		// invalidate and the sync must not write to this blog's settings at all. the filter
		// runs even when the stored value would not change, so it catches a pointless save()
		// as well as a harmful one
		$blog_settings_writes = 0;
		$count_writes         = function ( $value ) use ( &$blog_settings_writes ) {
			++$blog_settings_writes;

			return $value;
		};

		$last_code_update = time();

		switch_to_blog( $blog_id );
		try {
			$settings = $this->make_network_enabled_settings();
			$settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_MANUALLY );
			$settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, $last_code_update );
			$settings->save();

			update_option( 'blogname', 'Renamed Blog With A Manual Tracking Code' );

			// counted around the sync only, so that anything the rename itself writes is not
			// mistaken for the sync writing
			add_filter( 'pre_update_option_' . Settings::OPTION, $count_writes );

			try {
				( new Sync( $settings ) )->sync_current_site();
			} finally {
				remove_filter( 'pre_update_option_' . Settings::OPTION, $count_writes );
			}

			$stored = get_option( Settings::OPTION, [] );
		} finally {
			restore_current_blog();
			wp_delete_site( $blog_id );
		}

		$this->assertSame( 0, $blog_settings_writes );
		$this->assertSame( $last_code_update, $stored[ Settings::OPTION_LAST_TRACKING_CODE_UPDATE ] );
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_all_should_skip_blogs_that_are_archived_or_marked_as_spam() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$archived_blog = self::factory()->blog->create();
		$spam_blog     = self::factory()->blog->create();
		$live_blog     = self::factory()->blog->create();

		wp_update_site( $archived_blog, [ 'archived' => 1 ] );
		wp_update_site( $spam_blog, [ 'spam' => 1 ] );

		try {
			$this->mock->sync_all();

			// get_sites() hands out blog ids as strings, the factory returns them as ints
			$synced_blog_ids = array_map( 'intval', wp_list_pluck( $this->mock->synced_sites, 'id' ) );

			$this->assertNotContains( $archived_blog, $synced_blog_ids );
			$this->assertNotContains( $spam_blog, $synced_blog_ids );

			// so a sync that skipped everything cannot satisfy the two assertions above
			$this->assertContains( $live_blog, $synced_blog_ids );
		} finally {
			wp_delete_site( $archived_blog );
			wp_delete_site( $spam_blog );
			wp_delete_site( $live_blog );
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_site_should_not_invalidate_the_tracking_code_of_other_blogs() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$renamed_blog = $this->create_blog_with_matomo();
		$other_blog   = $this->create_blog_with_matomo();

		// last_tracking_settings_update is a global setting, and global settings are only shared
		// between blogs when the plugin is network activated. without that every Settings object
		// below reads a copy of its own blog's option and the leak cannot happen at all
		$network_settings = $this->make_network_enabled_settings();

		// the track mode defaults to disabled, and a blog whose tracking code is never generated
		// is never invalidated either, so we set the track mode to default
		$network_settings->set_global_option( 'track_mode', TrackingSettings::TRACK_MODE_DEFAULT );
		$network_settings->set_global_option( Settings::OPTION_LAST_TRACKING_SETTINGS_CHANGE, time() - 100 );
		$network_settings->save();

		// the other blog generates its tracking code after that, so its copy is up to date
		switch_to_blog( $other_blog );
		try {
			$other_settings = $this->make_network_enabled_settings();
			$other_settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 50 );
			$other_settings->save();

			$this->assertTrue( $other_settings->is_current_tracking_code() );
		} finally {
			restore_current_blog();
		}

		// switch to the blog that will need syncing
		switch_to_blog( $renamed_blog );
		try {
			$renamed_settings = $this->make_network_enabled_settings();
			$renamed_settings->set_option( Settings::OPTION_LAST_TRACKING_CODE_UPDATE, time() - 50 );
			$renamed_settings->save();

			// make sure the current blog's tracking code is up to date, and thus will be invalidated
			// by the sync
			$this->assertTrue( $renamed_settings->is_tracking_code_autogenerated() );
			$this->assertTrue( $renamed_settings->is_current_tracking_code() );

			update_option( 'blogname', 'Renamed Blog For Sync' );
			( new Sync( $renamed_settings ) )->sync_current_site();
		} finally {
			restore_current_blog();
		}

		// check that the other_blog did not have it's tracking code invalidated
		switch_to_blog( $other_blog );
		try {
			$still_current = $this->make_network_enabled_settings()->is_current_tracking_code();
		} finally {
			restore_current_blog();
			wp_delete_site( $renamed_blog );
			wp_delete_site( $other_blog );
		}

		$this->assertTrue(
			$still_current,
			'syncing one blog must not force every other blog to regenerate its tracking code'
		);
	}

	/**
	 * @group ms-required
	 */
	public function test_sync_all_should_return_false_when_a_blog_cannot_be_installed() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$blog_id = self::factory()->blog->create();

		// get_sites() iterates by ascending blog id, so a healthy blog created after the broken
		// one is the only way to tell that the loop carried on past the blog it skipped
		$later_blog = self::factory()->blog->create();

		// the uploads directory of that one blog cannot be written to nor created, which is the
		// only thing Installer::can_be_installed() checks. a path below a regular file can never
		// be created, which works to fail the install even when the tests run as root.
		$unwritable_parent = tempnam( sys_get_temp_dir(), 'matomo-not-a-dir' );
		$this->assertNotFalse( $unwritable_parent, 'could not create the file standing in for an uncreatable uploads dir' );

		$break_uploads = function ( $dirs ) use ( $blog_id, $unwritable_parent ) {
			if ( get_current_blog_id() === $blog_id ) {
				$dirs['basedir'] = $unwritable_parent . '/uploads';
				$dirs['baseurl'] = 'http://example.org/uploads';
			}

			return $dirs;
		};

		try {
			add_filter( 'upload_dir', $break_uploads );

			$this->assertFalse(
				$this->sync->sync_all(),
				'a blog that could not be installed must make the whole sync report a failure'
			);

			// check that the broken blog did not actually get installed as we intended, while the others
			// did
			$this->assertEmpty( Site::get_matomo_site_id( $blog_id ) );
			$this->assertNotEmpty( Site::get_matomo_site_id( get_current_blog_id() ) );
			$this->assertNotEmpty( Site::get_matomo_site_id( $later_blog ) );
		} finally {
			remove_filter( 'upload_dir', $break_uploads );
			wp_delete_site( $blog_id );
			wp_delete_site( $later_blog );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $unwritable_parent );
		}
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

		restore_current_blog();

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

		// renamed while Matomo is still inactive, so the plugin's own update_option_blogname hook
		// cannot sync it for us and the only thing left that could is the restore below
		switch_to_blog( $blog_id );
		update_option( 'blogname', 'Renamed While Deleted' );
		restore_current_blog();

		// restore handlers require matomo to be active on the blog
		$this->activate_matomo_plugin();

		// sync_all() skips blogs flagged deleted, so the Matomo site stays stale
		$this->sync->sync_all();
		$this->assertNotSame( 'Renamed While Deleted', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

		$this->sync->register_hooks();

		update_blog_status( $blog_id, 'deleted', '0' );

		$this->assertSame( 'Renamed While Deleted', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

		wp_delete_site( $blog_id );
	}

	public function get_test_data_for_the_other_flags_a_blog_returns_to_service_from() {
		return [
			[ 'archived' ],
			[ 'spam' ],
		];
	}

	/**
	 * @group ms-required
	 * @dataProvider get_test_data_for_the_other_flags_a_blog_returns_to_service_from
	 */
	public function test_register_hooks_should_sync_a_blog_that_is_no_longer_archived_or_flagged_as_spam( $flag ) {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		// taking a blog out of service and back only happens in the network admin, and the
		// sync is skipped on front end requests
		$this->assume_admin_page();

		$blog_id = $this->create_blog_with_matomo();
		$idsite  = Site::get_matomo_site_id( $blog_id );

		update_blog_status( $blog_id, $flag, '1' );

		// renamed while the blog is out of service, so the plugin's own update_option_blogname
		// hook cannot sync it for us and the only thing left that could is the return below
		switch_to_blog( $blog_id );
		update_option( 'blogname', 'Renamed While Out Of Service' );
		restore_current_blog();

		// the return handlers require matomo to be active on the blog
		$this->activate_matomo_plugin();

		// sync_all() skips a blog that is out of service, so the Matomo site stays stale
		$this->sync->sync_all();
		$this->assertNotSame( 'Renamed While Out Of Service', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

		$this->sync->register_hooks();

		try {
			update_blog_status( $blog_id, $flag, '0' ); // triggers sync via hook

			$this->assertSame( 'Renamed While Out Of Service', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );
		} finally {
			$this->sync->remove_hooks();
			wp_delete_site( $blog_id );
		}
	}

	/**
	 * @group ms-required
	 */
	public function test_register_hooks_should_not_sync_a_blog_that_is_still_out_of_service_by_another_flag() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Not multisite.' );
			return;
		}

		$this->assume_admin_page();

		$blog_id = $this->create_blog_with_matomo();
		$idsite  = Site::get_matomo_site_id( $blog_id );

		update_blog_status( $blog_id, 'archived', '1' );
		update_blog_status( $blog_id, 'spam', '1' );

		switch_to_blog( $blog_id );
		update_option( 'blogname', 'Renamed While Out Of Service' );
		restore_current_blog();

		$this->activate_matomo_plugin();

		$this->sync->register_hooks();

		try {
			// one flag cleared, the other still set, so the blog is not back in service yet
			update_blog_status( $blog_id, 'archived', '0' );

			$this->assertNotSame( 'Renamed While Out Of Service', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );

			update_blog_status( $blog_id, 'spam', '0' );

			$this->assertSame( 'Renamed While Out Of Service', $this->get_matomo_site_name_for_blog( $blog_id, $idsite ) );
		} finally {
			$this->sync->remove_hooks();
			wp_delete_site( $blog_id );
		}
	}

	/**
	 * @return Settings
	 */
	private function make_network_enabled_settings() {
		$settings = new Settings();
		$settings->set_assume_is_network_enabled_in_tests();
		$settings->init_settings();

		return $settings;
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
