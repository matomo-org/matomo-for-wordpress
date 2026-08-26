<?php
/**
 * Used in UI test WordPress environment.
 *
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// do not load wp-mail-smtp wizard during tests
add_filter( 'wp_mail_smtp_admin_setup_wizard_load_wizard', '__return_false' );

// handle switch_to_locale (used by mwp-language.e2e.ts)
if ( ! empty( $_GET['mwp_switch_to_locale'] ) ) {
	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	switch_to_locale( wp_unslash( $_GET['mwp_switch_to_locale'] ) );
}

// disable woocommerce's reactified settings page
add_filter(
	'woocommerce_admin_get_feature_config',
	function ( $config ) {
		$config['reactify-classic-payments-settings'] = false;
		return $config;
	}
);

// if a PHP error is detected from within Matomo for WordPress, throw an exception
// so we notice during tests and get a backtrace
add_action(
	'wp_trigger_error_run',
	function ( $function_name, $message, $error_level ) {
		if (
			E_NOTICE !== $error_level
			|| false !== strpos( $message, '_load_textdomain_just_in_time' )
			|| false !== strpos( $message, 'print_inline_script' )
		) {
			return;
		}

		$ex    = new \Exception( "Matomo: $function_name: $message" );
		$trace = $ex->getTraceAsString();

		if (
			false === stripos( $trace, 'matomo' )
			&& false === stripos( $trace, 'piwik' )
		) {
			return;
		}

		throw $ex;
	},
	10,
	3
);

// see manual-archiving.e2e.ts for more info
add_action(
	'wp_ajax_nopriv_test_remove_archive_table',
	function () {
		\WpMatomo\Bootstrap::do_bootstrap();
		if ( empty( $_REQUEST['date'] ) ) {
			wp_send_json( 'nodate' );
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$date  = \Piwik\Date::factory( wp_unslash( $_REQUEST['date'] ) );
		$table = \Piwik\DataAccess\ArchiveTableCreator::getNumericTable( $date );
		\Piwik\Db::exec( "DROP TABLE `$table`" );

		wp_send_json( 'ok' );
	}
);

add_action(
	'wp_ajax_nopriv_matomo_test_disable_block_headless',
	function () {
		\WpMatomo\Bootstrap::do_bootstrap();

		\Piwik\Access::doAsSuperUser(
			function () {
				$settings_provider = \Piwik\Container\StaticContainer::get( \Piwik\Plugin\SettingsProvider::class );
				$settings_metadata = \Piwik\Container\StaticContainer::get( \Piwik\Plugins\CorePluginsAdmin\SettingsMetadata::class );

				$plugins_settings = $settings_provider->getAllSystemSettings();
				$settings_metadata->setPluginSettings(
					$plugins_settings,
					[
						'TrackingSpamPrevention' => [
							[
								'name'  => 'block_headless',
								'value' => '0',
							],
						],
					]
				);

				foreach ( $plugins_settings as $plugin_setting ) {
					if ( 'TrackingSpamPrevention' === $plugin_setting->getPluginName() ) {
						$plugin_setting->save();
					}
				}
			}
		);

		wp_send_json( 'ok' );
	}
);

// overwrite user agent to be used via appendToTrackingUrl() (webdriverio does not allow
// changing the user agent sent with AJAX requests)
add_action(
	'wp_head',
	function () {
		$use_different_user_agent = get_option( 'matomo_test_user_agent' );
		if ( $use_different_user_agent ) {
			$user_agent_str = wp_json_encode( 'ua=' . rawurlencode( $use_different_user_agent ) . '&unsetch=1' );
			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "<script>window._paq = window._paq || []; _paq.push(['appendToTrackingUrl', $user_agent_str])</script>";
		}
	}
);
add_action(
	'plugins_loaded',
	function () {
		// unset client hints so only custom user agent is used
		if ( ! empty( $_REQUEST['unsetch'] ) ) {
			foreach ( $_SERVER as $key => $value ) {
				if (
					0 === strpos( strtolower( $key ), strtolower( 'HTTP_SEC_CH_UA' ) )
					|| 'X_HTTP_REQUESTED_WITH' === strtoupper( $key )
				) {
					unset( $_SERVER[ $key ] );
				}
			}

			unset( $_GET['uadata'] );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			unset( $_POST['uadata'] );
			unset( $_REQUEST['uadata'] );
		}
	}
);
add_action(
	'wp_ajax_nopriv_matomo_test_set_custom_user_agent',
	function () {
		if ( isset( $_REQUEST['ua'] ) ) {
			$user_agent = sanitize_text_field( wp_unslash( $_REQUEST['ua'] ) );
			update_option( 'matomo_test_user_agent', $user_agent );
		} else {
			delete_option( 'matomo_test_user_agent' );
		}
		wp_send_json( 'ok' );
	}
);

// ajax method for making sure get started shows
add_action(
	'wp_ajax_nopriv_matomo_test_show_get_started',
	function () {
		$settings = new WpMatomo\Settings();
		$settings->set_global_option( 'track_mode', 'disabled' );
		$settings->set_global_option( \WpMatomo\Settings::SHOW_GET_STARTED_PAGE, 1 );
		$settings->save();
	}
);

// add filemtime of matomo.php to asset version cache buster so
// it will reload if modifed (eg, after matomo is updated during
// e2e tests)
add_filter(
	'matomo_asset_version',
	function ( $version ) {
		if ( getenv( 'MATOMO_IN_E2E' ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$mtime = @filemtime( MATOMO_ANALYTICS_FILE );
			if ( $mtime ) {
				$version .= $mtime;
			}
		}
		return $version;
	}
);

/**
 * Everything below is used by mwp-admin.update-block.e2e.ts to exercise the Matomo 6
 * minimum requirements update guard.
 */
const MATOMO_TEST_FAKE_UPDATE_OPTION  = 'matomo_test_fake_plugin_update';
const MATOMO_TEST_FAKE_UPDATE_VERSION = '6.0.0';
const MATOMO_TEST_FAKE_UPDATE_ZIP     = 'matomo.6.0.0.zip';
const MATOMO_TEST_BLOCKED_OPTION      = 'matomo_test_fake_blocked_version';

function matomo_test_fake_update_zip_path() {
	return WP_PLUGIN_DIR . '/matomo/' . MATOMO_TEST_FAKE_UPDATE_ZIP;
}

function matomo_test_build_fake_update_zip() {
	require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';

	$path = matomo_test_fake_update_zip_path();

	// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
	@unlink( $path );

	$tmp_dir    = rtrim( get_temp_dir(), '/' ) . '/matomo-fake-update';
	$plugin_dir = $tmp_dir . '/matomo';

	matomo_test_delete_directory( $tmp_dir );
	wp_mkdir_p( $plugin_dir );

	// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents(
		$plugin_dir . '/matomo.php',
		"<?php\n/**\n * Plugin Name: Matomo Analytics\n * Description: dummy archive used by mwp-admin.update-block.e2e.ts, never meant to be installed.\n * Version: " . MATOMO_TEST_FAKE_UPDATE_VERSION . "\n */\n"
	);
	file_put_contents( $plugin_dir . '/readme.txt', "=== Matomo Analytics ===\nStable tag: " . MATOMO_TEST_FAKE_UPDATE_VERSION . "\n" );
	// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

	$archive = new PclZip( $path );
	$created = $archive->create( $plugin_dir, PCLZIP_OPT_REMOVE_PATH, $tmp_dir );

	matomo_test_delete_directory( $tmp_dir );

	if ( empty( $created ) ) {
		throw new \Exception( 'could not create fake update archive: ' . $archive->errorInfo( true ) );
	}

	return plugins_url( 'matomo/' . MATOMO_TEST_FAKE_UPDATE_ZIP );
}

function matomo_test_delete_directory( $dir ) {
	if ( ! is_dir( $dir ) ) {
		return;
	}

	foreach ( (array) glob( $dir . '/*' ) as $entry ) {
		if ( is_dir( $entry ) ) {
			matomo_test_delete_directory( $entry );
		} else {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $entry );
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	rmdir( $dir );
}

add_action(
	'wp_ajax_nopriv_matomo_test_set_fake_plugin_update',
	function () {
		if ( empty( $_REQUEST['enable'] ) ) {
			delete_option( MATOMO_TEST_FAKE_UPDATE_OPTION );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink,WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( matomo_test_fake_update_zip_path() );
		} else {
			update_option(
				MATOMO_TEST_FAKE_UPDATE_OPTION,
				[
					'version' => MATOMO_TEST_FAKE_UPDATE_VERSION,
					'package' => matomo_test_build_fake_update_zip(),
				]
			);
		}

		wp_clean_plugins_cache();

		wp_send_json( 'ok' );
	}
);

// make WordPress believe a Matomo 6 update is available, pointing at the archive above.
add_filter(
	'site_transient_update_plugins',
	function ( $value ) {
		$fake = get_option( MATOMO_TEST_FAKE_UPDATE_OPTION );
		if ( empty( $fake['package'] ) ) {
			return $value;
		}

		if ( ! is_object( $value ) ) {
			$value = new stdClass();
		}
		if ( empty( $value->response ) || ! is_array( $value->response ) ) {
			$value->response = [];
		}

		$value->response['matomo/matomo.php'] = (object) [
			'id'           => 'w.org/plugins/matomo',
			'slug'         => 'matomo',
			'plugin'       => 'matomo/matomo.php',
			'new_version'  => $fake['version'],
			'url'          => 'https://wordpress.org/plugins/matomo/',
			'package'      => $fake['package'],
			// deliberately low: if this required PHP 8.1, WordPress' own gate would hide the
			// update link and print its own message, and Matomo's guard would never run.
			'requires_php' => '7.2.5',
		];

		if ( ! empty( $value->no_update['matomo/matomo.php'] ) ) {
			unset( $value->no_update['matomo/matomo.php'] );
		}

		return $value;
	}
);

add_action(
	'wp_ajax_nopriv_matomo_test_set_fake_blocked_version',
	function () {
		if ( empty( $_REQUEST['enable'] ) ) {
			delete_option( MATOMO_TEST_BLOCKED_OPTION );

			// these requests are not authenticated (see the nopriv hook), so the dismissal has
			// to be cleared for every user rather than the current one.
			delete_metadata(
				'user',
				0,
				\WpMatomo\MinimumRequirementsNotice::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED,
				'',
				true
			);
		} else {
			update_option( MATOMO_TEST_BLOCKED_OPTION, 1 );
		}

		wp_send_json( 'ok' );
	}
);

/**
 * The "Matomo Analytics has been disabled" notice only renders once the installed version
 * requires the new minimums, which blocking an update never produces. Swap the live feature
 * for a subclass that reports version 6 so the e2e test can see the real notice.
 */
add_action(
	'admin_notices',
	function () {
		if ( ! get_option( MATOMO_TEST_BLOCKED_OPTION ) ) {
			return;
		}

		$notice = WpMatomo::get_active_feature( \WpMatomo\MinimumRequirementsNotice::class );
		if ( empty( $notice ) ) {
			return;
		}

		remove_action( 'admin_notices', [ $notice, 'check_requirements' ] );

		$fake_notice = new class() extends \WpMatomo\MinimumRequirementsNotice {
			protected function get_plugin_version() {
				return MATOMO_TEST_FAKE_UPDATE_VERSION;
			}
		};
		$fake_notice->check_requirements();
	},
	1
);

function matomo_test_utility_plugin_request_overrides() {
	$override_path = ABSPATH . '/wp-content/plugins/matomo/.e2e-test-overrides.json';

	if ( ! is_file( $override_path ) ) {
		return;
	}

	$contents = file_get_contents( $override_path );
	$contents = json_decode( $contents, true );
	if ( ! empty( $contents['ipAddress'] ) ) {
		$_SERVER['REMOTE_ADDR'] = $contents['ipAddress'];
	}

	if ( ! empty( $contents['userAgent'] ) ) {
		$_SERVER['HTTP_USER_AGENT'] = $contents['userAgent'];
	}

	// wp-statistics specific query parameter override
	if ( ! empty( $contents['referrer'] ) ) {
		$encoded_referrer     = base64_encode( $contents['referrer'] );
		$_GET['referred']     = $encoded_referrer;
		$_POST['referred']    = $encoded_referrer;
		$_REQUEST['referred'] = $encoded_referrer;
	}
}

// handle ip address and user agent overrides
matomo_test_utility_plugin_request_overrides();
