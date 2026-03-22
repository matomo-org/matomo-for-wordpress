<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<style>
	#matomo-adblocker-notice {
		display: none;
	}

	#matomo-adblocker-notice.adblocker-found {
		display: block;
	}
</style>
<script>
	window.addEventListener( 'DOMContentLoaded', function () {
		if ( window.matomoAdminJsLoaded ) {
			return;
		}

		var notice = document.querySelector( '#matomo-adblocker-notice' );
		if ( notice ) {
			notice.classList.add( 'adblocker-found' );
		}
	} );
</script>

<div id="matomo-adblocker-notice" class="notice notice-error">
	<p>
		<strong><?php esc_html_e( 'Error', 'matomo' ); ?>:</strong>
		<?php esc_html_e( 'In case you are using an ad blocker, please disable it for this site to make sure Matomo works without any issues.', 'matomo' ); ?>
	</p>
</div>
