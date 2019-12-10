<?php
/**
 * Test release.
 *
 * @package matomo
 */

class ReleaseTest extends MatomoUnit_TestCase {

	/**
	 * @dataProvider get_needed_files
	 */
	public function test_assert_needed_files_exist( $file ) {
		$this->assertFileExists( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $file );
	}

	public function test_stabletag_and_matomo_version_matches( ) {

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$version = $plugin_data['Version'];
		echo $version;

		$txt = file_get_contents(plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'readme.txt');
		$this->assertContains('Stable tag: ' . $version, $txt);
	}

	public function get_needed_files() {
		return array(
			array( 'app/bootstrap.php' ),
			array( 'app/plugins/CoreAdminHome/javascripts/optOut.js' ),
			array( 'app/plugins/Overlay/javascripts/Piwik_Overlay.js' ),
			array( 'app/plugins/TagManager/javascripts/previewmode.js' ),
			array( 'app/plugins/TagManager/javascripts/previewmodedetection.js' ),
			array( 'app/plugins/TagManager/javascripts/tagmanager.js' ),
			array( 'app/plugins/TagManager/javascripts/tagmanager.min.js' ),
			array( 'app/.htaccess' ),
			array( 'app/core/.htaccess' ),
			array( 'app/js/.htaccess' ),
			array( 'app/lang/.htaccess' ),
			array( 'app/plugins/.htaccess' ),
			array( 'app/libs/.htaccess' ),
			array( 'app/vendor/.htaccess' ),
			array( 'app/robots.txt' ),
			array( '.htaccess' ),
			array( 'readme.txt' ),
		);
	}

}
