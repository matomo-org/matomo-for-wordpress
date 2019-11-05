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
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\SystemReport;

/** @var Access $access */
/** @var array $tables */
/** @var string $active_tab */
/** @var \WpMatomo\Settings $settings */

if (!function_exists('anonymize_matomo_value')) {
	function anonymize_matomo_value($value)
	{
		if ( is_string($value) && !empty($value) ) {
			$values_to_anonymize = array(
				ABSPATH => '$ABSPATH/',
				str_replace('/', '\/', ABSPATH) => '$ABSPATH\/',
				WP_CONTENT_DIR => '$WP_CONTENT_DIR/',
				home_url() => '$home_url',
				site_url() => '$site_url'
			);
			foreach ($values_to_anonymize as $search => $replace) {
				$value = str_replace($search, $replace, $value);
			}
		}

		return $value;
	}
}

?>

<div class="wrap matomo-systemreport">

	<?php if ( $settings->is_network_enabled() && ! is_network_admin() && is_super_admin() ) { ?>
        <div class="updated notice">
            <p><?php _e( 'Only you are seeing this page as you are the super admin', 'matomo' ); ?></p>
        </div>
	<?php } ?>
    <div id="icon-plugins" class="icon32"></div>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo Menu::SLUG_SYSTEM_REPORT; ?>"
           class="nav-tab <?php echo empty( $active_tab ) ? 'nav-tab-active' : ''; ?>"> System report</a>
        <a href="?page=<?php echo Menu::SLUG_SYSTEM_REPORT; ?>&tab=troubleshooting"
           class="nav-tab <?php echo $active_tab == 'troubleshooting' ? 'nav-tab-active' : ''; ?>">Troubleshooting</a>
    </h2>

	<?php if ( empty( $active_tab ) ) { ?>

        <p><?php _e( 'Copy the below info in case our support team asks you for this information:', 'matomo' ); ?>
            <br/> <br/>
            <a href="javascript:void(0);"
               onclick="var textarea = document.getElementById('matomo_system_report_info');textarea.select();document.execCommand('copy');"
               class='button-primary'><?php _e( 'Copy system report', 'matomo' ); ?></a>

        </p>
        <textarea style="width:100%;height: 200px;" readonly
                  id="matomo_system_report_info"><?php foreach ( $tables as $table ) {
				echo "# " . esc_html($table['title']) . "\n";
				foreach ( $table['rows'] as $index => $row ) {
					if ( ! empty( $row['section'] ) ) {
						echo "\n\n## " . esc_html( $row['section'] ) . "\n";
						continue;
					}
					$value = $row['value'];
					if ( $value === true ) {
						$value = 'Yes';
					} elseif ( $value === false ) {
						$value = 'No';
					}
					$class = '';
					if ( ! empty( $row['is_error'] ) ) {
						$class = 'Error ';
					} elseif ( ! empty( $row['is_warning'] ) ) {
						$class = 'Warning ';
					}
					echo "\n* " . $class . esc_html( $row['name'] ) . ': ' . esc_html( anonymize_matomo_value( $value ) );
					if ( isset( $row['comment'] ) && $row['comment'] !== '' ) {
						echo " (" . esc_html( anonymize_matomo_value ($row['comment'] ) ) . ")";
					}

				}
				echo "\n\n";
			} ?>
    </textarea>

		<?php foreach ( $tables as $table ) {
			echo "<h2>" . esc_html($table['title']) . "</h2><table class='widefat'><thead></thead><tbody>";
			foreach ( $table['rows'] as $row ) {
				if ( ! empty( $row['section'] ) ) {
					echo '</tbody><thead><tr><th colspan="3" class="section">' . esc_html( $row['section'] ) . '</th></tr></thead><tbody>';
					continue;
				}
				$value = $row['value'];
				if ( $value === true ) {
					$value = __('Yes');
				} elseif ( $value === false ) {
					$value = __('No');
				}
				$class = '';
				if ( ! empty( $row['is_error'] ) ) {
					$class = 'error';
				} elseif ( ! empty( $row['is_warning'] ) ) {
					$class = 'warning';
				}
				echo "<tr class='$class'>";
				echo "<td width='30%'>" . esc_html( $row['name'] ) . "</td>";
				echo "<td width='" . ( ! empty( $table['has_comments'] ) ? 20 : 70 ) . "%'>" . esc_html( $value ) . "</td>";
				if ( ! empty( $table['has_comments'] ) ) {
					$replacedElements = array(
						'<code>'                                             => '__#CODEBACKUP#__',
						'</code>'                                            => '__##CODEBACKUP##__',
						'<pre style="overflow-x: scroll;max-width: 600px;">' => '__#PREBACKUP#__',
						'</pre>'                                             => '__##PREBACKUP##__',
						'<br/>'                                              => '__#BRBACKUP#__',
						'<br />'                                             => '__#BRBACKUP#__',
						'<br>'                                               => '__#BRBACKUP#__'
					);
					$comment          = isset($row['comment']) ? $row['comment'] : '';
					$replaced         = str_replace( array_keys( $replacedElements ), array_values( $replacedElements ), $comment );
					$escaped          = esc_html( $replaced );
					echo "<td width='50%'>" . str_replace( array_values( $replacedElements ), array_keys( $replacedElements ), $escaped ) . "</td>";
				}

				echo "</tr>";
			}
			echo "</tbody></table>";
		} ?>

	<?php } else { ?>
        <h1><?php _e( 'Troubleshooting', 'matomo' ); ?></h1>

        <form method="post">
			<?php wp_nonce_field( SystemReport::NONCE_NAME ); ?>

			<?php if ( ! $settings->is_network_enabled() || ! is_network_admin() ) { ?>
                <input name="<?php echo SystemReport::TROUBLESHOOT_SYNC_USERS; ?>" type="submit" class='button-primary'
                       value="<?php _e( 'Sync users', 'matomo' ); ?>">
                <br/><br/>
                <input name="<?php echo SystemReport::TROUBLESHOOT_SYNC_SITE; ?>" type="submit" class='button-primary'
                       value="<?php _e( 'Sync site', 'matomo' ); ?>">
			<?php } ?>
			<?php if ( $settings->is_network_enabled() ) { ?>
                <input name="<?php echo SystemReport::TROUBLESHOOT_SYNC_ALL_SITES; ?>" type="submit"
                       class='button-primary'
                       value="<?php _e( 'Sync all sites', 'matomo' ); ?>">
                <br/><br/>
                <input name="<?php echo SystemReport::TROUBLESHOOT_SYNC_ALL_USERS; ?>" type="submit"
                       class='button-primary'
                       value="<?php _e( 'Sync all users across sites', 'matomo' ); ?>">
			<?php } ?>
            <br/><br/>
            <input name="<?php echo SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE; ?>" type="submit"
                   class='button-primary'
                   value="<?php _e( 'Clear Matomo Cache', 'matomo' ); ?>">
            <br/><br/>
            <input name="<?php echo SystemReport::TROUBLESHOOT_ARCHIVE_NOW; ?>" type="submit"
                   class='button-primary'
                   value="<?php _e( 'Archive reports', 'matomo' ); ?>">
        </form>

		<?php include 'info_help.php'; ?>
		<?php include 'info_bug_report.php' ?>
        <h4><?php _e( 'Before you create an issue', 'matomo' ); ?></h4>
        <p><?php _e( 'If you experience any issue in Matomo, it is always a good idea to first check your webserver logs (if possible) for any errors.', 'matomo' ); ?>
     <br />
	        <?php echo sprintf( __( 'You may also want to enable %1$s.', 'matomo' ), '<a href="https://codex.wordpress.org/WP_DEBUG" target="_blank" rel="noreferrer noopener"><code>WP_DEBUG</code></a>' ); ?>
	        <?php echo sprintf( __( 'To debug issues that happen in the background, for example report generation during a cronjob, you might also want to enable %1$s.', 'matomo' ), '<code>WP_DEBUG_LOG</code>' ); ?>

        </p>
        <h3><?php _e( 'Having performance issues?', 'matomo' ); ?></h3>
        <p><?php echo sprintf( __( 'You may want to disable %1$s in your %2$s and set up an actual cronjob and %3$scheck out our recommended server sizing%4$s.', 'matomo' ),
                '<code>DISABLE_WP_CRON</code>',
                '<code>wp-config.php</code>',
                    '<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/docs/requirements/#recommended-servers-sizing-cpu-ram-disks">',
                    '</a>'
            ); ?>
        </p>
        <?php include 'info_high_traffic.php' ?>
	<?php } ?>
</div>
