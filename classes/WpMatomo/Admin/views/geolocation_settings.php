<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * @package WP_Matomo
 * https://github.com/braekling/matomo
 *
 */

use WpMatomo\Admin\GeolocationSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $was_updated */
/** @var string $current_maxmind_license */

?>

<form method="post">
	<?php wp_nonce_field( GeolocationSettings::NONCE_NAME ); ?>

	<p>
        <?php esc_html_e( 'On this page you can configure how Matomo detects the locations of your visitors.', 'matomo' ); ?>
    </p>
    <p>
        To detect the location of a visitor, the IP address of a visitor is looked up in a so called geolocation database.
        This is automatically taken care of for you. However, the freely available database DB-IP we are using is sometimes less accurate than other freely available geolocation databases. This applies to the free and paid version of DB-IP.
        An alternative geolocation database is called MaxMind which has a free and a paid version as well. Because of GDPR we cannot configure this database automatically for you.
        <br><br>
        To use MaxMind <a href="https://matomo.org/faq/how-to/how-do-i-get-a-license-key-for-the-maxmind-geolocation-database/">get a license key</a> and then configure this key below.

    </p>

	<table class="matomo-tracking-form widefat">
		<tbody>
		<tr>
			<th  scope="row" style="vertical-align: top;">
                <label for="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>"><?php esc_html_e( 'MaxMind License Key', 'matomo' ); ?></label>:
			</th>
			<td width="20%">
				<input size="20" type="text" maxlength="20"
                       id="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>"
                       name="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>" value="<?php echo esc_attr($current_maxmind_license) ?>">
			</td>
            <td>
                <?php esc_html_e('Leave the field empty and click on "Save Changes" to configure the default DB-IP database.', 'matomo') ?>
                <?php esc_html_e('When configured, your WordPress will send an HTTP request to a MaxMind server to download an approx. 60MB database and store it in your "wp-content/uploads/matomo" directory.', 'matomo') ?>
            </td>
		</tr>
		<tr>
			<td colspan="3">
				<p class="submit"><input name="Submit" type="submit" class="button-primary"
										 value="<?php echo esc_attr__( 'Save Changes', 'matomo' ); ?>"/></p>
			</td>
		</tr>

		</tbody>
	</table>
</form>
