<?php
/**
 * @package matomo
 */

use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\TrackingCode;

/**
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
 */
class TrackingCodeTest extends MatomoUnit_TestCase {

	/**
	 * @var TrackingCode
	 */
	private $tracking_code;

	/**
	 * @var Settings
	 */
	private $settings;

	/**
	 * @var Capabilities
	 */
	private $capabilities;

	public function setUp(): void {
		parent::setUp();

		$this->settings      = new Settings();
		$this->tracking_code = new WpMatomo\TrackingCode( $this->settings );
		$this->capabilities  = new Capabilities( $this->settings );
		$this->capabilities->register_hooks(); // needed so caps get reset when changing it

		Site::map_matomo_site_id( get_current_blog_id(), 23 );
	}

	public function tearDown(): void {
		$this->capabilities->remove_hooks();
		parent::tearDown();
	}

	public function test_is_hidden_user_by_default_is_not_hidden() {
		$this->assertFalse( $this->tracking_code->is_hidden_user() );
	}

	public function test_is_hidden_user_hidden_when_role_is_hidden() {
		global $wp_roles;
		$this->settings->apply_tracking_related_changes( array( Settings::OPTION_KEY_STEALTH => array( 'editor' => true ) ) );

		$wp_roles->init_roles(); // usually done on the next page view

		$id1 = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $id1 );

		// this editor role is now hidden
		$this->assertTrue( $this->tracking_code->is_hidden_user() );

		// different role still not hidden
		$id1 = self::factory()->user->create( array( 'role' => 'author' ) );
		wp_set_current_user( $id1 );
		$this->assertFalse( $this->tracking_code->is_hidden_user() );
	}

	public function test_does_not_print_tracking_code_when_not_enabled() {
		$this->tracking_code->register_hooks();
		ob_start();

		do_action( 'wp_head' );
		do_action( 'wp_footer' );

		$contents = ob_get_clean();
		$this->assertNotContains( 'idsite', $contents );
	}


	public function test_tracking_enabled_adds_by_default_to_footer() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode' => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
			)
		);
		$this->tracking_code->register_hooks();
		ob_start();
		do_action( 'wp_head' );
		$header = ob_get_clean();

		ob_start();
		do_action( 'wp_footer' );
		$footer = ob_get_clean();

		$this->assertNotContains( 'idsite', $header );
		$this->assertContains( '<!-- Matomo --><script ' . $this->get_type_attribute() . ">\nvar _paq = window._paq = window._paq || [];", $footer );
		$this->assertContains( '_paq.push([\'setSiteId\', \'23\'])', $footer );
	}


	public function test_tracking_enabled_can_be_added_to_header() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'         => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
				'track_codeposition' => 'head',
			)
		);
		$this->tracking_code->register_hooks();
		ob_start();
		do_action( 'wp_head' );
		$header = ob_get_clean();

		ob_start();
		do_action( 'wp_footer' );
		$footer = ob_get_clean();

		$this->assertNotContains( 'idsite', $footer );
		$this->assertContains( '<!-- Matomo --><script ' . $this->get_type_attribute() . ">\nvar _paq = window._paq = window._paq || [];", $header );
		$this->assertContains( '_paq.push([\'setSiteId\', \'23\'])', $header );
	}

	public function test_tracking_noscriptenabled_default() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
				'track_noscript' => true,
			)
		);
		$this->tracking_code->register_hooks();

		ob_start();
		do_action( 'wp_footer' );
		$footer = ob_get_clean();

		$this->assertContains( '<noscript><p><img referrerpolicy="no-referrer-when-downgrade"', $footer );
		$this->assertContains( '</noscript>', $footer );
		$this->assertNotContains( '<noscript><noscript>', $footer );// make sure noscript not present twice
	}

	public function test_tracking_noscriptenabled_manually_adds_noscript_when_needed() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'     => WpMatomo\Admin\TrackingSettings::TRACK_MODE_MANUALLY,
				'track_noscript' => true,
				'noscript_code'  => '<p>test</p>',
			)
		);
		$this->tracking_code->register_hooks();

		ob_start();
		do_action( 'wp_footer' );
		$footer = ob_get_clean();

		$this->assertContains( '<noscript><p>test</p></noscript>', $footer );
		$this->assertNotContains( '<noscript><noscript>', $footer );// make sure noscript not present twice
	}

	public function test_forward_cross_domain_visitor_id() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'                => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
				'track_crossdomain_linking' => true,
			)
		);
		$this->tracking_code->register_hooks();

		$id             = md5( '1' );
		$_GET['pk_vid'] = $id;
		$url            = apply_filters( 'wp_redirect', 'https://www.example.org?foo=test' );
		unset( $_GET['pk_vid'] );
		$this->assertSame( 'https://www.example.org?foo=test&pk_vid=c4ca4238a0b923820dcc509a6f75849b', $url );
	}

	public function test_add_feed_campaign() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'             => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
				'track_feed_addcampaign' => true,
			)
		);
		$this->tracking_code->register_hooks();
		$this->set_is_feed();

		$url = apply_filters( 'post_link', 'https://www.example.org?foo=test' );
		$this->assertSame( 'https://www.example.org?foo=test&pk_campaign=feed&pk_kwd=hello-world', $url );
	}

	public function test_add_feed_tracking() {
		$this->settings->apply_tracking_related_changes(
			array(
				'track_mode'         => WpMatomo\Admin\TrackingSettings::TRACK_MODE_DEFAULT,
				'track_feed'         => true,
				'track_api_endpoint' => 'restapi',
			)
		);
		$this->tracking_code->register_hooks();
		$this->set_is_feed();

		$url = apply_filters( 'the_excerpt_rss', '<p>foobarbaz</p>' );

		$this->assertStringStartsWith( '<p>foobarbaz</p><img src="//example.org/index.php?rest_route=/matomo/v1/hit/?idsite=23&amp;rec=1&amp;url=http%3A%2F%2Fexample.org%2F%3Fp%3D7&amp;action_name=hello-world&amp;urlref=http%3A%2F%2Fexample.org', $url );
		$this->assertStringEndsWith( '" style="border:0;width:0;height:0" width="0" height="0" alt="" />', $url );
	}

	private function set_is_feed() {
		$post_id = self::factory()->post->create( array( 'post_title' => 'hello-world' ) );
		self::factory()->comment->create_post_comments( $post_id, 2 );
		$this->go_to( get_post_comments_feed_link( $post_id ) );
		$this->assertQueryTrue( 'is_feed', 'is_single', 'is_singular', 'is_comment_feed' );
	}


}
