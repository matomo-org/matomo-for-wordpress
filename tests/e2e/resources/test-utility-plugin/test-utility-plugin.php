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
