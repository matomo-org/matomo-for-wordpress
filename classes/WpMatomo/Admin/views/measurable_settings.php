<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
?>

<script>
	window.addEventListener(
		'DOMContentLoaded',
		function () {
			// TODO: log if debug mode enabled or something
			window.iFrameResize( { log: false, bodyPadding: '0 0 16px 0' }, '#plugin_measurable_settings' );
		}
	);
</script>

<iframe
	id="plugin_measurable_settings"
	style="width:100%;"
	src="<?php echo esc_url( $home_url . '/wp-content/plugins/matomo/app/index.php?idSite=' . rawurlencode( $idsite ) . '&module=WordPress&action=showMeasurableSettings&pluginName=' . rawurlencode( $plugin_name ) ); ?>"
></iframe>
