<?php

use WpMatomo\Admin\PrivacySettings;

/**
 * @package Matomo_Analytics
 */
class OptOutTest extends MatomoUnit_TestCase {

	public function setUp() {
		parent::setUp();
	}

	public function test_matomo_opt_out_no_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_MINIMAL );
		$this->assertSame( '<iframe style="border: 0; width:600px;height:200px;" src="http://example.org/wp-content/plugins/matomo/app/index.php?module=CoreAdminHome&action=optOut"></iframe>', $result );
	}

	public function test_matomo_opt_out_all_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_FULL );
		$this->assertSame( '<iframe style="border: 0; width:500px;height:100px;" src="http://example.org/wp-content/plugins/matomo/app/index.php?module=CoreAdminHome&action=optOut&language=de&background_color=red&font_color=fff&font_size=34&font_family=Arial"></iframe>', $result );
	}

	public function test_matomo_opt_out_size_percent_px_values() {
		$result = do_shortcode( '[matomo_opt_out width=60px height=100%]' );
		$this->assertContains( 'width:60px;height:100%', $result );
	}

}
