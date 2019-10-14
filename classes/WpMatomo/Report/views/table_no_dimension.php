<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Piwik\Piwik;
use Piwik\SettingsServer;
use WpMatomo\Admin\Menu;
use WpMatomo\Capabilities;

/** @var array $report */
/** @var array $report_meta */
/** @var string $first_metric_name */
/** @var string $first_metric_display_name */
?>

<div class="table">
    <table class="widefat matomo-table">
        <tbody>
		<?php
		$columns = ! empty( $report['columns'] ) ? $report['columns'] : array();
		foreach ( $report['reportData']->getRows() as $metricName => $row ) {
			foreach ( $row as $metricName => $value ) {
				$display_name = ! empty( $columns[ $metricName ] ) ? $columns[ $metricName ] : $metricName;
				echo '<tr><td width="75%">' . esc_html( $display_name ) . '</td><td width="25%">' . esc_html( $value ) . '</td></tr>';
			}
		}
		?>
        </tbody>

    </table>
</div>
