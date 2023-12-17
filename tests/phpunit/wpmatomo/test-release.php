<?php
/**
 * Test release.
 *
 * @package matomo
 * @group only
 *
 */
class ReleaseTest extends MatomoAnalytics_TestCase {

	const MAX_RELEASE_SIZE = 26214400; // 25mb

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
		$this->assertStringContainsString( 'Stable tag: ' . $version, $txt );
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
		if ( getenv( 'WP_MULTISITE' ) ) { // only need to run this test once
			return;
		}

		$generated_asset = 'assets/js/asset_manager_core_js.js';
		$contents        = file_get_contents( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $generated_asset );
		$contents        = substr( $contents, strpos( $contents, "\n" ) );
		$current_hash    = md5( $contents );

		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		[$return_code, $output] = $this->execute_command( 'wordpress:generate-core-assets' );
		$this->assertEquals( 0, $return_code, 'Generate command failed: ' . $output );

		$contents            = file_get_contents( plugin_dir_path( MATOMO_ANALYTICS_FILE ) . $generated_asset );
		$contents            = substr( $contents, strpos( $contents, "\n" ) );
		$hash_after_generate = md5( $contents );

		// phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		$this->assertEquals( $current_hash, $hash_after_generate, 'Core assets need to be regenerated, run "npm run compose run console wordpress:generate-core-assets".' );
	}

	public function test_built_release_has_all_needed_matomo_contents_and_is_not_too_big() {
		$ignored_core_files = [
			'matomo/CHANGELOG.md',
			'matomo/lang/README.md',
			'matomo/config/manifest.inc.php',
			'matomo/js/piwik.js',
			'matomo/libs/jqplot/excanvas.min.js',
			'matomo/libs/jqplot/build_minified_script.sh',
			'matomo/plugins/Morpheus/fonts/selection.json',
			'matomo/plugins/Morpheus/stylesheets/base/font.css',
			'matomo/node_modules/angular/angular.min.js',
			'matomo/node_modules/angular/index.js',
			'matomo/node_modules/visibilityjs/lib/visibility.js',
			'matomo/node_modules/visibilityjs/lib/visibility.fallback.js',
			'matomo/node_modules/visibilityjs/lib/visibility.core.js',
			'matomo/node_modules/visibilityjs/lib/visibility.timers.js',
			'matomo/node_modules/jquery.browser/dist/jquery.browser.min.js',
			'matomo/node_modules/mousetrap/mousetrap.min.js',
			'matomo/node_modules/jquery.dotdotdot/dist/jquery.dotdotdot.js',
			'matomo/node_modules/vue/dist/vue.global.prod.js',
			'matomo/node_modules/vue/dist/vue.global.js',
			'matomo/node_modules/qrcodejs2/qrcode.min.js',
			'matomo/node_modules/jquery.scrollto/jquery.scrollTo.min.js',
			'matomo/plugins/Morpheus/icons/README.md',
		];

		$ignored_mwp_files = [
			'app/bootstrap.php',
			'app/favicon.ico',
			'app/.htaccess',
			'app/core/.htaccess',
			'app/js/.htaccess',
			'app/lang/.htaccess',
			'app/libs/.htaccess',
			'app/node_modules/.htaccess',
			'app/plugins/.htaccess',
			'app/vendor/.htaccess',
		];

		try {
			$application = new \Piwik\Console();
			$application->setAutoExit( false );

			// generate release
			[$return_code, $output] = $this->execute_command( 'wordpress:build-release --name=test-release --zip' );
			$this->assertEquals( 0, $return_code, 'Generate command failed: ' . $output );

			$path_to_zip = dirname( PIWIK_INCLUDE_PATH ) . '/matomo-test-release.zip';
			$this->assertFileExists( $path_to_zip, 'release zip not created: ' . $output );

			// download core release
			$version          = \Piwik\Version::VERSION;
			$core_release_url = "http://builds.matomo.org/matomo-$version.zip";
			$core_release_zip = download_url( $core_release_url );

			// check release contents
			$mwp_release_contents = array_flip( $this->get_zip_file_contents( $path_to_zip ) );
			$matomo_core_contents = array_flip( $this->get_zip_file_contents( $core_release_zip ) );

			$missing_files = [];
			foreach ( $matomo_core_contents as $path => $ignore ) {
				if ( ! preg_match( '%^matomo/%', $path )
					|| strpos( $path, '/CONTRIBUTING.md' ) !== false
					|| strpos( $path, '/CHANGELOG' ) !== false
					|| preg_match( '%lang/.*?\.json$%', $path )
					|| preg_match( '%^matomo/misc/%', $path )
					|| preg_match( '%^matomo/plugins/.*?/(javascripts|angularjs)%', $path )
					|| preg_match( '%^matomo/tests/%', $path )
					|| preg_match( '%^matomo/libs/jqplot/.*?jqplot\..*?\.js$%', $path )
					|| preg_match( '%^matomo/core/Updates/[012]\..*?\.php$%', $path )
					|| preg_match( '%^matomo/node_modules/jquery/%', $path )
					|| preg_match( '%^matomo/plugins/.*?/vue/src%', $path )
					|| preg_match( '%^matomo/plugins/.*?/vue/dist/*.*?\.umd\.js$%', $path )
					|| preg_match( '%^matomo/plugins/.*?/vue/dist/umd.metadata.json$%', $path )
					|| in_array( $path, $ignored_core_files, true )
				) {
					continue;
				}

				$path_in_mwp_release = preg_replace( '%^matomo/%', 'app/', $path );
				if ( empty( $mwp_release_contents[ $path_in_mwp_release ] ) ) {
					$missing_files[] = $path;
				}
			}
			$this->assertEmpty( $missing_files, 'MWP release is missing possibly required files found in core release: ' . print_r( $missing_files, true ) );

			// check that mwp release does not contain any extraneous files not in core release
			$extraneous_files = [];
			foreach ( $mwp_release_contents as $path => $ignore ) {
				if ( ! preg_match( '%^app/%', $path )
					|| in_array( $path, $ignored_mwp_files, true )
				) {
					continue;
				}

				$path_in_core_release = preg_replace( '%^app/%', 'matomo/', $path );
				if ( empty( $matomo_core_contents[ $path_in_core_release ] ) ) {
					$extraneous_files[] = $path;
				}
			}
			$this->assertEmpty( $extraneous_files, 'MWP release has some app/ files that are not in the core release, check if they are necessary: ' . print_r( $extraneous_files, true ) );

			// check release size is not too large
			$release_size = filesize( $path_to_zip );
			$this->assertLessThan( self::MAX_RELEASE_SIZE, $release_size );

			// check that ignored file list is still relevant
			$irrelevant_ignored_files = [];
			foreach ( $ignored_core_files as $ignored_file ) {
				if ( empty( $matomo_core_contents[ $ignored_file ] ) ) {
					$irrelevant_ignored_files[] = $ignored_file;
				}
			}
			$this->assertEmpty( $irrelevant_ignored_files, 'The \$ignored_core_files variable has some out of date entries: ' . print_r( $irrelevant_ignored_files, true ) );

			$irrelevant_mwp_ignored_files = [];
			foreach ( $ignored_mwp_files as $ignored_file ) {
				if ( empty( $mwp_release_contents[ $ignored_file ] ) ) {
					$irrelevant_mwp_ignored_files[] = $ignored_file;
				}
			}
			$this->assertEmpty( $irrelevant_mwp_ignored_files, 'The \$ignored_mwp_files variable has some out of date entries: ' . print_r( $irrelevant_mwp_ignored_files, true ) );
		} finally {
			if ( isset( $core_release_zip ) && is_file( $core_release_zip ) ) {
				unlink( $core_release_zip );
			}

			if ( isset( $path_to_zip ) && is_file( $path_to_zip ) ) {
				unlink( $path_to_zip );
			}
		}
	}

	private function get_zip_file_contents( $path_to_zip ) {
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
		$output = shell_exec( 'unzip -l ' . $path_to_zip );
		$output = $output ? $output : '';

		$output = explode( "\n", $output );
		$output = array_map(
			function ( $line ) {
				$parts = preg_split( '/\s+/', $line, 5 );
				return count( $parts ) >= 5 ? $parts[4] : null;
			},
			$output
		);
		$output = array_filter(
			$output,
			function ( $line ) {
				return null !== $line && 'Name' !== $line && ! preg_match( '/-+/', $line ) && ! preg_match( '%/$%', $line );
			}
		);

		$this->assertGreaterThan( 0, count( $output ) );

		return $output;
	}

	private function execute_command( $command ) {
		// run in a separate process so phpunit's symfony console version isn't used
		$command = PIWIK_INCLUDE_PATH . '/console ' . $command;
		exec( $command, $output, $return_code );
		$output = implode( '\n', $output );
		return [ $return_code, $output ];
	}
}
