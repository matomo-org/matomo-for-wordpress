<?php
/**
 * @package matomo
 */

use WpMatomo\PrivacyBadge;

class PrivacyBadgeTest extends MatomoUnit_TestCase {

	public function setUp(): void {
		parent::setUp();

		$badge = new PrivacyBadge();
		$badge->register_hooks();
	}

	public function test_privacy_badge_shortcode_no_options() {
		$this->assertSame( '<img alt="Your privacy protected! This website uses Matomo." src="http://example.org/wp-content/plugins/matomo/assets/img/privacybadge.png"  width="120" height="120">', do_shortcode( '[matomo_privacy_badge]' ) );
	}

	public function test_privacy_badge_shortcode_size() {
		$this->assertStringContainsString( 'width="99" height="99"', do_shortcode( '[matomo_privacy_badge size=99]' ) );
	}

	public function test_privacy_badge_shortcode_size_percent() {
		$this->assertStringContainsString( 'width="99%" height="99%"', do_shortcode( '[matomo_privacy_badge size=99%]' ) );
	}

}
