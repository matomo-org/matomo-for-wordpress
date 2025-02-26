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
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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

add_action(
    'wp_ajax_nopriv_test_get_archive_entries',
    function () {
		\WpMatomo\Bootstrap::do_bootstrap();
		$data = \Piwik\Db::fetchAll('SELECT * FROM ' . \Piwik\Common::prefixTable('archive_numeric_2023_12'));
        wp_send_json($data);
    }
);
