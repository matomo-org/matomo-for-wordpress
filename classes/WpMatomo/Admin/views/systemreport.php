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

use WpMatomo\Access;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\SystemReport;

/** @var Access $access */
/** @var array $matomo_tables */
/** @var string $matomo_active_tab */
/** @var \WpMatomo\Settings $settings */

if ( ! function_exists( 'matomo_anonymize_value' ) ) {
	function matomo_anonymize_value( $value ) {
		if ( is_string( $value ) && ! empty( $value ) ) {
			$values_to_anonymize = array(
				ABSPATH                           => '$ABSPATH/',
				str_replace( '/', '\/', ABSPATH ) => '$ABSPATH\/',
				WP_CONTENT_DIR                    => '$WP_CONTENT_DIR/',
				home_url()                        => '$home_url',
				site_url()                        => '$site_url',
			);
			foreach ( $values_to_anonymize as $search => $replace ) {
				$value = str_replace( $search, $replace, $value );
			}
		}

		return $value;
	}
}

?>

<div class="wrap matomo-systemreport">

	<?php if ( $settings->is_network_enabled() && ! is_network_admin() && is_super_admin() ) { ?>
		<div class="updated notice">
			<p><?php esc_html_e( 'Only you are seeing this page as you are the super admin', 'matomo' ); ?></p>
		</div>
	<?php } ?>
	<div id="icon-plugins" class="icon32"></div>
	<h2 class="nav-tab-wrapper">
		<a href="?page=<?php echo Menu::SLUG_SYSTEM_REPORT; ?>"
		   class="nav-tab <?php echo empty( $matomo_active_tab ) ? 'nav-tab-active' : ''; ?>"> System report</a>
		<a href="?page=<?php echo Menu::SLUG_SYSTEM_REPORT; ?>&tab=troubleshooting"
		   class="nav-tab <?php echo 'troubleshooting' === $matomo_active_tab ? 'nav-tab-active' : ''; ?>">Troubleshooting</a>
	</h2>

	<?php if ( empty( $matomo_active_tab ) ) { ?>

		<p><?php esc_html_e( 'Copy the below info in case our support team asks you for this information:', 'matomo' ); ?>
			<br/> <br/>
			<a href="javascript:void(0);"
			   onclick="var textarea = document.getElementById('matomo_system_report_info');textarea.select();document.execCommand('copy');"
			   class='button-primary'><?php esc_html_e( 'Copy system report', 'matomo' ); ?></a>

		</p>
		<textarea style="width:100%;height: 200px;" readonly
				  id="matomo_system_report_info"><?php
					foreach ( $matomo_tables as $matomo_table ) {
						echo '# ' . esc_html( $matomo_table['title'] ) . "\n";
						foreach ( $matomo_table['rows'] as $index => $matomo_row ) {
							if ( ! empty( $matomo_row['section'] ) ) {
								echo "\n\n## " . esc_html( $matomo_row['section'] ) . "\n";
								continue;
							}
							$matomo_value = $matomo_row['value'];
							if ( true === $matomo_value ) {
								$matomo_value = 'Yes';
							} elseif ( false === $matomo_value ) {
								$matomo_value = 'No';
							}
							$matomo_class = '';
							if ( ! empty( $matomo_row['is_error'] ) ) {
								$matomo_class = 'Error ';
							} elseif ( ! empty( $matomo_row['is_warning'] ) ) {
								$matomo_class = 'Warning ';
							}
							echo "\n* " . esc_html( $matomo_class ) . esc_html( $matomo_row['name'] ) . ': ' . esc_html( matomo_anonymize_value( $matomo_value ) );
							if ( isset( $matomo_row['comment'] ) && '' !== $matomo_row['comment'] ) {
								echo ' (' . esc_html( matomo_anonymize_value( $matomo_row['comment'] ) ) . ')';
							}
						}
						echo "\n\n";
					}
					?>
	</textarea>

		<?php
		foreach ( $matomo_tables as $matomo_table ) {
			echo '<h2>' . esc_html( $matomo_table['title'] ) . "</h2><table class='widefat'><thead></thead><tbody>";
			foreach ( $matomo_table['rows'] as $matomo_row ) {
				if ( ! empty( $matomo_row['section'] ) ) {
					echo '</tbody><thead><tr><th colspan="3" class="section">' . esc_html( $matomo_row['section'] ) . '</th></tr></thead><tbody>';
					continue;
				}
				$matomo_value = $matomo_row['value'];
				if ( true === $matomo_value ) {
					$matomo_value = esc_html__( 'Yes', 'matomo' );
				} elseif ( false === $matomo_value ) {
					$matomo_value = esc_html__( 'No', 'matomo' );
				}
				$matomo_class = '';
				if ( ! empty( $matomo_row['is_error'] ) ) {
					$matomo_class = 'error';
				} elseif ( ! empty( $matomo_row['is_warning'] ) ) {
					$matomo_class = 'warning';
				}
				echo "<tr class='" . esc_attr( $matomo_class ) . "'>";
				echo "<td width='30%'>" . esc_html( $matomo_row['name'] ) . '</td>';
				echo "<td width='" . ( ! empty( $matomo_table['has_comments'] ) ? 20 : 70 ) . "%'>" . esc_html( $matomo_value ) . '</td>';
				if ( ! empty( $matomo_table['has_comments'] ) ) {
					$matomo_replaced_elements = array(
						'<code>'  => '__#CODEBACKUP#__',
						'</code>' => '__##CODEBACKUP##__',
						'<pre style="overflow-x: scroll;max-width: 600px;">' => '__#PREBACKUP#__',
						'</pre>'  => '__##PREBACKUP##__',
						'<br/>'   => '__#BRBACKUP#__',
						'<br />'  => '__#BRBACKUP#__',
						'<br>'    => '__#BRBACKUP#__',
					);
					$matomo_comment           = isset( $matomo_row['comment'] ) ? $matomo_row['comment'] : '';
					$matomo_replaced          = str_replace( array_keys( $matomo_replaced_elements ), array_values( $matomo_replaced_elements ), $matomo_comment );
					$matomo_escaped           = esc_html( $matomo_replaced );
					echo "<td width='50%'>" . str_replace( array_values( $matomo_replaced_elements ), array_keys( $matomo_replaced_elements ), $matomo_escaped ) . '</td>';
				}

				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		?>

	<?php } else { ?>
		<h1><?php esc_html_e( 'Troubleshooting', 'matomo' ); ?></h1>

		<form method="post">
			<?php wp_nonce_field( SystemReport::NONCE_NAME ); ?>

			<?php if ( ! $settings->is_network_enabled() || ! is_network_admin() ) { ?>
				<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_SYNC_USERS ); ?>" type="submit" class='button-primary'
					   value="<?php esc_html_e( 'Sync users', 'matomo' ); ?>">
				<br/><br/>
				<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_SYNC_SITE ); ?>" type="submit" class='button-primary'
					   value="<?php esc_html_e( 'Sync site', 'matomo' ); ?>">
			<?php } ?>
			<?php if ( $settings->is_network_enabled() ) { ?>
				<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_SYNC_ALL_SITES ); ?>" type="submit"
					   class='button-primary'
					   value="<?php esc_html_e( 'Sync all sites', 'matomo' ); ?>">
				<br/><br/>
				<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_SYNC_ALL_USERS ); ?>" type="submit"
					   class='button-primary'
					   value="<?php esc_html_e( 'Sync all users across sites', 'matomo' ); ?>">
			<?php } ?>
			<br/><br/>
			<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ); ?>" type="submit"
				   class='button-primary'
				   value="<?php esc_html_e( 'Clear Matomo Cache', 'matomo' ); ?>">
			<br/><br/>
			<input name="<?php echo esc_attr( SystemReport::TROUBLESHOOT_ARCHIVE_NOW ); ?>" type="submit"
				   class='button-primary'
				   value="<?php esc_html_e( 'Archive reports', 'matomo' ); ?>">
		</form>

		<?php include 'info_help.php'; ?>
		<?php include 'info_bug_report.php'; ?>
		<h4><?php esc_html_e( 'Before you create an issue', 'matomo' ); ?></h4>
		<p><?php esc_html_e( 'If you experience any issue in Matomo, it is always a good idea to first check your webserver logs (if possible) for any errors.', 'matomo' ); ?>
			<br/>
			<?php echo sprintf( esc_html__( 'You may also want to enable %1$s.', 'matomo' ), '<a href="https://codex.wordpress.org/WP_DEBUG" target="_blank" rel="noreferrer noopener"><code>WP_DEBUG</code></a>' ); ?>
			<?php echo sprintf( esc_html__( 'To debug issues that happen in the background, for example report generation during a cronjob, you might also want to enable %1$s.', 'matomo' ), '<code>WP_DEBUG_LOG</code>' ); ?>

		</p>
		<h3><?php esc_html_e( 'Having performance issues?', 'matomo' ); ?></h3>
		<p>
		<?php
		echo sprintf(
			esc_html__( 'You may want to disable %1$s in your %2$s and set up an actual cronjob and %3$scheck out our recommended server sizing%4$s.', 'matomo' ),
			'<code>DISABLE_WP_CRON</code>',
			'<code>wp-config.php</code>',
			'<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/docs/requirements/#recommended-servers-sizing-cpu-ram-disks">',
			'</a>'
		);
		?>
		</p>
		<?php include 'info_high_traffic.php'; ?>
	<?php } ?>
</div>
