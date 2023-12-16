<?php

class WordPressTest extends MatomoAnalytics_TestCase {

	public function test_on_useroptout_render() {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$content = file_get_contents( dirname( MATOMO_ANALYTICS_FILE ) . '/app/plugins/PrivacyManager/templates/usersOptOut.twig' );

		\WpMatomo\Bootstrap::do_bootstrap();

		$manager = \Piwik\Plugin\Manager::getInstance();
		$plugin  = $manager->getLoadedPlugin( 'WordPress' );

		$plugin->onUserOptOutRender( $content );
		$this->assertStringContainsString( 'Use the short code <code>[matomo_opt_out]</code> to embed', $content );
	}

}
