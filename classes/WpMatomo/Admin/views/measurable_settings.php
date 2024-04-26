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

<div
	id="plugin_measurable_settings"
	title="<?php echo esc_attr__( 'Plugin Settings for', 'matomo' ); ?> <?php echo esc_attr( $plugin_display_name ); ?>"
	style="width:100%;margin-top:1em;"
>
	<?php echo $matomo_html; ?>
</div>
