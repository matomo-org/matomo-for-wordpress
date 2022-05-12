<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * Code Based on
 * @author Andr&eacute; Br&auml;kling
 * https://github.com/braekling/WP-Matomo
 *
 */
/**
 * phpcs consider all our variables as global and want them prefixed with matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
use WpMatomo\Admin\AdvancedSettings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $was_updated */
/** @var string $matomo_detected_ip */
/** @var array $matomo_client_headers */
?>

<?php
if ( $was_updated ) {
	include 'update_notice_clear_cache.php';
}
?>
<form method="post">
	<?php wp_nonce_field( AdvancedSettings::NONCE_NAME ); ?>

	<p><?php esc_html_e( 'Advanced settings', 'matomo' ); ?></p>
	<table class="matomo-tracking-form widefat">
		<tbody>
		<tr>
			<th width="20%" scope="row"><label
						for="matomo[proxy_client_header]"><?php esc_html_e( 'Proxy IP headers', 'matomo' ); ?>:</label>
			</th>
			<td>
				<?php
				echo '<span style="white-space: nowrap;display: inline-block;"><input type="radio" ' . ( empty( $matomo_client_headers ) ? 'checked="checked" ' : '' ) . ' value="REMOTE_ADDR" name="matomo[proxy_client_header]" /> <code>REMOTE_ADDR</code> ' . ( ! empty( $_SERVER['REMOTE_ADDR'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) ) : esc_html__( 'No value found', 'matomo' ) ) . ' (' . esc_html__( 'Default', 'matomo' ) . ')</span>';
				foreach ( AdvancedSettings::$valid_host_headers as $host_header ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<span style="white-space: nowrap;display: inline-block;"><input type="radio" ' . ( in_array( $host_header, $matomo_client_headers, true ) ? 'checked="checked" ' : '' ) . 'value="' . esc_attr( $host_header ) . '" name="matomo[proxy_client_header]" /> <code>' . esc_html( $host_header ) . '</code> ' . ( ! empty( $_SERVER[ $host_header ] ) ? ( '<strong>' . esc_html( sanitize_text_field( wp_unslash( $_SERVER[ $host_header ] ) ) ) . '</strong>' ) : esc_html__( 'No value found', 'matomo' ) ) . ' &nbsp; </span>';
				}
				?>
			</td>
			<td width="50%">
				<?php esc_html_e( 'We detected you have the following IP address:', 'matomo' ); ?>
				<?php echo esc_html( $matomo_detected_ip ); ?> <br>
				<?php echo sprintf( esc_html__( 'To compare this value with your actual IP address %1$splease click here%2$s.', 'matomo' ), '<a rel="noreferrer noopener" target="_blank" href="https://matomo.org/ip.php">', '</a>' ); ?>
				<br><br>
				<?php esc_html_e( 'Should your IP address not match the above value, your WordPress might be behind a proxy and you may need to select a different HTTP header depending on which of the values on the left shows your correct IP address.', 'matomo' ); ?>
			</td>
		</tr>
		<?php if ( ! defined( 'MATOMO_REMOVE_ALL_DATA' ) ) { ?>
			<tr>
				<th width="20%" scope="row"><label
							for="matomo[delete_all_data]"><?php esc_html_e( 'Delete all data on uninstall', 'matomo' ); ?>
						:</label>
				</th>
				<td>
					<?php
					echo '<span style="white-space: nowrap;display: inline-block;"><input type="checkbox" ' . ( ! empty( $matomo_delete_all_data ) ? 'checked="checked" ' : '' ) . ' value="1" name="matomo[delete_all_data]" /> ' . esc_html__( 'Yes', 'matomo' ) . '</span>';
					?>
				</td>
				<td width="50%">
					<?php esc_html_e( 'By default, when you uninstall the Matomo plugin, all data is deleted and cannot be restored unless you have backups. When you disable this feature, the tracked data in the database will be kept. This can be useful to prevent accidental deletion of all your historical analytics data when you uninstall the plugin.', 'matomo' ); ?> <a
							href="https://matomo.org/faq/wordpress/how-do-i-delete-or-reset-the-matomo-for-wordpress-data-completely/"
							target="_blank" rel="noreferrer noopener"><?php esc_html_e( 'Learn more', 'matomo' ); ?></a>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td colspan="3"><p class="submit"><input name="Submit" type="submit" class="button-primary"
													 value="<?php esc_attr_e( 'Save Changes', 'matomo' ); ?>"/></p></td>
		</tr>
		</tbody>
	</table>
</form>
