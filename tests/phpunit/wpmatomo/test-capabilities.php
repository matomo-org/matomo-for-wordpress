<?php
/**
 * @package Matomo_Analytics
 */

use WpMatomo\Access;
use WpMatomo\Capabilities;
use WpMatomo\Settings;

class TestMatomoCapabilities extends Capabilities {
	public function has_any_higher_permission( $cap_to_find, $allcaps ) {
		return parent::has_any_higher_permission( $cap_to_find, $allcaps );
	}
}

class CapabilitiesTest extends MatomoAnalytics_TestCase {

	/**
	 * @var TestMatomoCapabilities
	 */
	private $caps;

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->settings = new Settings();
		$this->caps     = $this->make_capabilities();
		$this->caps->register_hooks();
	}

	public function tearDown() {
		$this->caps->remove_hooks();
		parent::tearDown();
	}

	private function make_capabilities() {
		return new TestMatomoCapabilities( $this->settings );
	}

	public function test_get_all_capabilities_sorted_by_highest_permission() {
		$this->assertCount( 4, $this->caps->get_all_capabilities_sorted_by_highest_permission() );
	}

	/**
	 * @dataProvider get_any_higher_permission_provider
	 */
	public function test_has_any_higher_permission( $expectedResult, $capToFind, $caps ) {
		$this->assertSame( $expectedResult, $this->caps->has_any_higher_permission( $capToFind, $caps ) );
	}

	public function get_any_higher_permission_provider() {
		return array(
			array( true, Capabilities::KEY_VIEW, $this->make_all_caps( array( Capabilities::KEY_VIEW ) ) ),
			array( true, Capabilities::KEY_VIEW, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
			array( true, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
			array( true, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_SUPERUSER ) ) ),
			array( true, Capabilities::KEY_ADMIN, $this->make_all_caps( array( Capabilities::KEY_SUPERUSER ) ) ),
			array( false, Capabilities::KEY_SUPERUSER, $this->make_all_caps( array( Capabilities::KEY_ADMIN ) ) ),
			array( false, Capabilities::KEY_WRITE, $this->make_all_caps( array( Capabilities::KEY_VIEW ) ) ),
			array( false, Capabilities::KEY_ADMIN, $this->make_all_caps( array( Capabilities::KEY_WRITE ) ) ),
		);
	}

	public function test_add_capabilities_to_user_and_add_capabilities_to_roles() {
		$id1 = self::factory()->user->create( array( 'role' => 'editor' ) );
		$id2 = self::factory()->user->create( array( 'role' => 'author' ) );
		$id3 = self::factory()->user->create( array( 'role' => 'contributor' ) );

		foreach ( array( $id1, $id2, $id3 ) as $userId ) {
			$this->assertFalse( user_can( $userId, Capabilities::KEY_ADMIN ) );
			$this->assertFalse( user_can( $userId, Capabilities::KEY_WRITE ) );
			$this->assertFalse( user_can( $userId, Capabilities::KEY_VIEW ) );
		}

		$access = new Access( $this->settings );
		$access->save( array(
			'editor'      => Capabilities::KEY_ADMIN,
			'author'      => Capabilities::KEY_WRITE,
			'contributor' => Capabilities::KEY_VIEW,
		) );

		$this->assertTrue( get_role( 'editor' )->has_cap( Capabilities::KEY_ADMIN ) );
		$this->assertTrue( get_role( 'author' )->has_cap( Capabilities::KEY_WRITE ) );
		$this->assertTrue( get_role( 'contributor' )->has_cap( Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id1, Capabilities::KEY_SUPERUSER ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_ADMIN ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id1, Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id2, Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( user_can( $id2, Capabilities::KEY_ADMIN ) );
		$this->assertTrue( user_can( $id2, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id2, Capabilities::KEY_VIEW ) );

		$this->assertFalse( user_can( $id3, Capabilities::KEY_SUPERUSER ) );
		$this->assertFalse( user_can( $id3, Capabilities::KEY_ADMIN ) );
		$this->assertFalse( user_can( $id3, Capabilities::KEY_WRITE ) );
		$this->assertTrue( user_can( $id3, Capabilities::KEY_VIEW ) );
	}

	private function make_all_caps( $caps_to_set ) {
		$caps = array();
		foreach ( $this->make_capabilities()->get_all_capabilities_sorted_by_highest_permission() as $cap ) {
			$caps[ $cap ] = in_array( $cap, $caps_to_set );
		}

		return $caps;
	}
}
