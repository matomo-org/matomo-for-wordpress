<?php
/**
 * Used in UI test WordPress environment.
 *
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

// handle switch_to_locale (used by mwp-language.e2e.ts)
if ( ! empty( $_GET['mwp_switch_to_locale'] ) ) {
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	switch_to_locale( wp_unslash( $_GET['mwp_switch_to_locale'] ) );
}
