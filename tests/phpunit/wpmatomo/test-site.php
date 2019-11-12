<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Site;

class SiteTest extends MatomoUnit_TestCase {

	/**
	 * @var Site
	 */
	private $site;

	public function setUp() {
		parent::setUp();

		$this->site = $this->make_site();
		$this->site->uninstall();
	}

	private function make_site() {
		return new Site();
	}

	public function test_get_current_matomo_site_id_when_not_mapped() {
		$this->assertFalse( $this->site->get_current_matomo_site_id() );
	}

	public function test_get_current_matomo_site_id_when_mapped() {
		Site::map_matomo_site_id( get_current_blog_id(), 84 );
		$this->assertSame( 84, $this->site->get_current_matomo_site_id() );
	}

	/**
	 * @group ms-required
	 */
	public function test_get_current_matomo_site_id_mapping_is_stored_per_blog() {
		$blogid = self::factory()->blog->create();
		Site::map_matomo_site_id( get_current_blog_id(), 42 );
		Site::map_matomo_site_id( $blogid, 89 );

		$this->assertSame( 42, $this->site->get_current_matomo_site_id() );

		switch_to_blog( $blogid );
		$this->assertSame( 89, $this->site->get_current_matomo_site_id() );

		// in original blog has different mapping again
		restore_current_blog();
		$this->assertSame( 42, $this->site->get_current_matomo_site_id() );

		wp_delete_site($blogid);
	}

	public function test_get_matomo_site_id_when_nothing_mapped() {
		$this->assertFalse( Site::get_matomo_site_id( 93 ) );
	}

	/**
	 * @group ms-required
	 */
	public function test_map_matomo_site_id_is_stored_across_blogs() {
		$blogid = self::factory()->blog->create();
		Site::map_matomo_site_id( $blogid, 89 );

		$this->assertSame( 89, Site::get_matomo_site_id( $blogid ) );

		switch_to_blog( $blogid );
		// still returns the same result
		$this->assertSame( 89, Site::get_matomo_site_id( $blogid ) );

		wp_delete_site($blogid);
	}

	public function test_map_matomo_site_id_get_matomo_site_id() {
		Site::map_matomo_site_id( 92, 5 );
		Site::map_matomo_site_id( 38, 81 );
		Site::map_matomo_site_id( 49, 81 ); // different blogs may have same idSite

		$this->assertSame( 5, Site::get_matomo_site_id( 92 ) );
		$this->assertSame( 81, Site::get_matomo_site_id( 38 ) );
		$this->assertSame( 81, Site::get_matomo_site_id( 49 ) );
	}

	public function test_map_matomo_site_id_mapping_can_change() {
		Site::map_matomo_site_id( 49, 81 );
		$this->assertSame( 81, Site::get_matomo_site_id( 49 ) );

		Site::map_matomo_site_id( 49, 84 );
		$this->assertSame( 84, Site::get_matomo_site_id( 49 ) );
	}

	public function test_map_matomo_site_id_mapping_can_be_deleted() {
		Site::map_matomo_site_id( 49, 81 );
		$this->assertSame( 81, Site::get_matomo_site_id( 49 ) );

		Site::map_matomo_site_id( 49, false );
		$this->assertFalse( Site::get_matomo_site_id( 49 ) );
	}

	public function test_uninstall_removes_all_mappings() {
		Site::map_matomo_site_id( 49, 81 );
		$this->assertNotEmpty( Site::get_matomo_site_id( 49 ) );

		$this->site->uninstall();

		$this->assertEmpty( Site::get_matomo_site_id( 49 ) );
	}

}
