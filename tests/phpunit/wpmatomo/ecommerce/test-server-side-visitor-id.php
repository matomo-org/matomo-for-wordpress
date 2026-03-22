<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Ecommerce\ServerSideVisitorId;
use WpMatomo\Settings;

/**
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 */
class ServerSideVisitorIdTest extends MatomoUnit_TestCase {

	use MatomoWooCommerceAwareTest;

	/**
	 * @var ServerSideVisitorId
	 */
	private $instance;

	public function setUp(): void {
		parent::setUp();

		$_COOKIE = [];

		$this->manually_load_woocommerce();
		$this->disable_woocommerce_cookies();

		$settings       = new Settings();
		$this->instance = new ServerSideVisitorId( $settings, new \WpMatomo\Logger() );
	}

	public function tearDown(): void {
		$this->unset_wc_session();
		$_COOKIE = [];
		parent::tearDown();
	}

	protected function assert_post_conditions() {
		// do nothing instead of checking for deprecated function usage
		// (woocommerce has many deprecated function uses)
	}

	public function test_server_side_visitor_id_is_not_generated_on_admin_pages() {
		$this->instance->register_hooks();
		$this->assume_admin_page();

		do_action( 'woocommerce_init' );

		$output = $this->invoke_wp_head();
		$this->assertStringNotContainsString( 'setVisitorId', $output );

		$has_session = ! empty( WC()->session ) && WC()->session->has_session();
		$this->assertFalse( $has_session );
	}

	public function test_server_side_visitor_id_is_not_generated_if_visitor_id_cookie_is_present() {
		$this->instance->register_hooks();

		$_COOKIE['_pk_id.12345'] = 'myvisitorid';

		do_action( 'woocommerce_init' );

		$output = $this->invoke_wp_head();
		$this->assertStringNotContainsString( 'setVisitorId', $output );

		$has_session = ! empty( WC()->session ) && WC()->session->has_session();
		$this->assertFalse( $has_session );
	}

	public function test_server_side_visitor_id_is_generated_if_active_and_no_visitor_id_exists_in_session() {
		$this->instance->register_hooks();

		$_COOKIE['someothercookie'] = 'someothervalue';
		$_COOKIE['_pk_ses.asldkfj'] = 'whatever';

		do_action( 'woocommerce_init' );

		$output = $this->invoke_wp_head();

		$this->assertTrue( WC()->session->has_session() );

		$visitor_id_in_session = WC()->session->get( ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME );
		$this->assertNotEmpty( $visitor_id_in_session );

		$this->assertStringContainsString( 'window._paq.push(["setVisitorId", "' . $visitor_id_in_session . '"]);', $output );
	}

	public function test_server_side_visitor_id_uses_visitor_id_in_session_if_exists() {
		$this->instance->register_hooks();

		$this->initialize_wc_session();
		WC()->session->set( ServerSideVisitorId::VISITOR_ID_SESSION_VAR_NAME, 'testvisitorid' );

		do_action( 'woocommerce_init' );

		$output = $this->invoke_wp_head();

		$this->assertStringContainsString( 'window._paq.push(["setVisitorId", "testvisitorid"]);', $output );
	}

	private function invoke_wp_head() {
		ob_start();
		do_action( 'wp_head' );
		$output = ob_get_clean();
		return $output;
	}
}
