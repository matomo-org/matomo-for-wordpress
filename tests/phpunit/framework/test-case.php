<?php
/**
 * @package matomo
 */

use \WpMatomo\Capabilities;

class MatomoUnit_TestCase extends WP_UnitTestCase {

	/**
	 * @var MatomoUnit_WordPress_Fixture
	 */
	protected $wordpress_fixture;

	public function setUp(): void {
		parent::setUp();

		$this->wordpress_fixture = new MatomoUnit_WordPress_Fixture();
		$this->wordpress_fixture->set_up();
	}

	public function tearDown(): void {
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
