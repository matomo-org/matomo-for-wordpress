<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\AjaxTracker;

class TestAjaxTracker extends AjaxTracker {

	public $captured_urls = [];

	public $captured_requests = [];

	protected function wp_remote_request( $url, $args ) {
		// remove random query params
		$url = preg_replace( '/&_id=[^&]+/', '&_id=REMOVED', $url );
		$url = preg_replace( '/&r=[^&]+/', '', $url );
		$url = preg_replace( '/&_idts=[^&]+/', '', $url );
		$url = preg_replace( '/&pv_id=[^&]+/', '', $url );
		$url = preg_replace( '/&ip_nonce=[^&]+/', '&ip_nonce=REMOVED', $url );

		$this->captured_urls[]     = $url;
		$this->captured_requests[] = [ $url, $args ];

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return base64_decode( 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==' );
	}
}
