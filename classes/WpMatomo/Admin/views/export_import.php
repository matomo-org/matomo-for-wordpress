<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WpMatomo\Access;
use WpMatomo\Admin\AccessSettings;

/** @var string $export */
?>

<p><?php _e( 'Export or import some settings.', 'matomo' ) ?></p>

<h2>Export</h2>
<textarea><?php echo esc_html( $export ); ?></textarea>

<form method="post">
	<?php wp_nonce_field( \WpMatomo\Admin\ExportImportSettings::NONCE_NAME ); ?>

	<textarea name="<?php \WpMatomo\Admin\ExportImportSettings::FORM_NAME ?>"
	></textarea>
	<input name="Submit" type="submit" class="button-primary"
	       value="<?php echo esc_attr__( 'Save Changes', 'matomo' ) ?>"/>
</form>

