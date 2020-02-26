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
/** @var string $current_url */

?>

<?php
if ( $was_updated ) {
	include 'update_notice_clear_cache.php';
}
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
        <a href="https://matomo.org/faq/how-to/how-do-i-get-a-license-key-for-the-maxmind-geolocation-database/">Click here to learn how to get a MaxMind database license key</a>.

    </p>

	<table class="matomo-tracking-form widefat">
		<tbody>
		<tr>
			<th  scope="row"><label
					for="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>"><?php esc_html_e( 'MaxMind License Key', 'matomo' ); ?></label>:
			</th>
			<td width="40%">
				<input style="width: 100%" type="text" maxlength="20"
                       name="maxmindlicensekey"
                      onpaste="jQuery('#matomogeoformid').val('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key='+encodeURIComponent(this.value)+'&suffix=tar.gz')"
                      onchange="jQuery('#matomogeoformid').val('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key='+encodeURIComponent(this.value)+'&suffix=tar.gz')">
			</td>
            <td width="40%">
                <?php esc_html_e('Either enter your MaxMind license key here to configure the URL below automatically or enter the URL below directly.', 'matomo') ?>
            </td>
		</tr>
		<tr>
			<th  scope="row"><label
					for="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>"><?php esc_html_e( 'Geolocation database URL', 'matomo' ); ?></label>:
			</th>
			<td width="40%">
				<input id="matomogeoformid" style="width: 100%" type="text" maxlength="200" name="<?php echo esc_attr( GeolocationSettings::FORM_NAME ) ?>" value="<?php echo esc_attr($current_url) ?>">
			</td>
            <td width="40%">
                <?php esc_html_e('Leave the field empty and click on Save Changes to configure the default database.', 'matomo') ?>
            </td>
		</tr>
		<tr>
			<td colspan="3">
				<p class="submit"><input name="Submit" type="submit" class="button-primary"
										 value="<?php echo esc_attr__( 'Save Changes', 'matomo' ); ?>"/></p>
                <p>Note: Your WordPress will send an HTTP request to that location to download the database and store it in your "wp-content/uploads/matomo" directory.</p>
			</td>
		</tr>

		</tbody>
	</table>
</form>
