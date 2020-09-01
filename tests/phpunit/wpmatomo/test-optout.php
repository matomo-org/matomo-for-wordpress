<?php

use WpMatomo\Admin\PrivacySettings;

/**
 * @package matomo
 */
class OptOutTest extends MatomoAnalytics_TestCase {

	public function setUp() {
		parent::setUp();
	}

	public function test_matomo_opt_out_no_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_MINIMAL );
		$this->assertSame( '<iframe style="border: 0; width:600px;height:200px;" src="http://example.org/wp-content/plugins/matomo/app/index.php?module=CoreAdminHome&action=optOut"></iframe>', $result );
	}

	public function test_matomo_opt_out_all_options() {
		$result = do_shortcode( PrivacySettings::EXAMPLE_FULL );
		$this->assertSame( '<iframe style="border: 0; width:500px;height:100px;" src="http://example.org/wp-content/plugins/matomo/app/index.php?module=CoreAdminHome&action=optOut&language=de&backgroundColor=red&fontColor=fff&fontSize=34&fontFamily=Arial"></iframe>', $result );
	}

	public function test_optOutJs_exists() {
		// see https://github.com/matomo-org/wp-matomo/issues/46
		$this->assertFileExists( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'app/plugins/CoreAdminHome/javascripts/optOut.js' );
	}

}
