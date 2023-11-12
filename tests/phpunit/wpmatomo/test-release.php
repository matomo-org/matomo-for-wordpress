<?php
/**
 * Test release.
 *
 * @package matomo
 */
class ReleaseTest extends MatomoAnalytics_TestCase {

	/**
	 * @dataProvider get_needed_files
	 */
	public function test_assert_needed_files_exist( $file ) {
		$this->assertFileExists( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $file );
	}

	public function test_stabletag_and_matomo_version_matches() {
		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );
		$version     = $plugin_data['Version'];
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $version;

		$txt = file_get_contents( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . 'readme.txt' );
		$this->assertContains( 'Stable tag: ' . $version, $txt );
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

	public function test_latest_release_is_not_too_old() {
		$url = 'https://api.wordpress.org/plugins/info/1.0/matomo.json';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$api_response = file_get_contents( $url );
		$api_response = json_decode( $api_response, true );

		$last_updated   = strtotime( $api_response['last_updated'] );
		$six_months_ago = ( new DateTime( '-6 months ago' ) )->getTimestamp();

		$this->assertLessThan( $six_months_ago, $last_updated, 'The last release of this plugin was over 6 months ago, another release is needed to show the plugin is not abandoned.' );
	}

	public function test_generated_assets_are_up_to_date() {
		$generated_asset = 'assets/js/asset_manager_core_js.js';
		$contents        = file_get_contents( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $generated_asset );
		$contents        = substr( $contents, strpos( $contents, "\n" ) );
		$current_hash    = md5( $contents );

		$application = new \Piwik\Console();
		$application->setAutoExit( false );

		$input  = new \Symfony\Component\Console\Input\ArrayInput(
			[
				'command' => 'wordpress:generate-core-assets',
			]
		);
		$output = new \Symfony\Component\Console\Output\BufferedOutput();

		$return_code = $application->run( $input, $output );
		$this->assertEquals( 0, $return_code, 'Generate command failed: ' . $output->fetch() );

		$contents            = file_get_contents( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $generated_asset );
		$contents            = substr( $contents, strpos( $contents, "\n" ) );
		$hash_after_generate = md5( $contents );

		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		$this->assertEquals( $current_hash, $hash_after_generate, 'Core assets need to be regenerated, run "npm run compose run console wordpress:generate-core-assets".' );
	}
}
