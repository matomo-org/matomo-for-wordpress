<?php
/**
 * @package matomo
 */

use \WpMatomo\Capabilities;

/**
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
class MatomoUnit_TestCase extends WP_UnitTestCase {

	/**
	 * @var MatomoUnit_WordPress_Fixture
	 */
	protected $wordpress_fixture;

	/**
	 * The ROLLBACK WP_UnitTestCase sometimes does not rollback to the correct
	 * state, which causes succeeding tests to fail. I am unable to find the reason
	 * why the rollback fails, but disabling transactions entirely seems to fix things.
	 */
	public function start_transaction() {
		// empty
	}

	public function setUp(): void {
		parent::setUp();

		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}

		$this->wordpress_fixture = new MatomoUnit_WordPress_Fixture();
		$this->wordpress_fixture->set_up();
	}

	public function tearDown(): void {
		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}

		$this->wordpress_fixture->tear_down();
		parent::tearDown();
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

	protected function create_set_super_admin() {
		$logger = new \WpMatomo\Logger();
		$logger->log( 'creating super admin' );
		$id = self::factory()->user->create();

		wp_set_current_user( $id );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			grant_super_admin( $id );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		} else {
			$user->add_role( 'administrator' );
			$user->add_role( \WpMatomo\Roles::ROLE_SUPERUSER );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		}

		return $id;
	}

	private function delete_extraneous_blogs() {
		global $wpdb;

		while ( ms_is_switched() ) {
			restore_current_blog();
		}

		$blogs = $wpdb->get_results( 'SELECT blog_id, deleted FROM ' . $wpdb->blogs . ' ORDER BY blog_id', ARRAY_A );
		foreach ( $blogs as $blog ) {
			if ( 1 === (int) $blog['deleted'] || 1 === (int) $blog['blog_id'] ) {
				continue;
			}

			wpmu_delete_blog( $blog['blog_id'] );
		}
	}

	/**
	 * @return string
	 */
	protected function get_type_attribute() {
		$type = '';
		if ( function_exists( 'wp_get_inline_script_tag' ) && ! is_admin() && ! current_theme_supports( 'html5', 'script' ) ) {
			$type = 'type="text/javascript"';
		}
		return $type;
	}
}
