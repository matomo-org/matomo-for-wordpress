<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 *
 * Prints the marketplace plugins available to the configured license as JSON, for use by the
 * e2e tests. This asks the marketplace plugin itself rather than reimplementing any of it, so
 * the environment parameters, the compatibility filtering and the download URLs are exactly
 * the ones the plugin would use. Run via:
 *
 *   wp eval-file .../tests/e2e/resources/marketplace-available-plugins.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

call_user_func(
	function () {
		if ( ! class_exists( 'MatomoMarketplaceApi' ) ) {
			throw new Exception(
				'MatomoMarketplaceApi does not exist, the marketplace plugin is not active.'
			);
		}

		$api     = new MatomoMarketplaceApi();
		$plugins = $api->get_available_plugins( 'plugins', '' );

		// a marker so the JSON can be found even if WordPress emits notices before it. Keep in
		// sync with AVAILABLE_PLUGINS_JSON_MARKER in tests/e2e/premium-plugins-setup.ts
		echo 'MARKETPLACE_PLUGINS_JSON:' . wp_json_encode( $plugins );
	}
);
