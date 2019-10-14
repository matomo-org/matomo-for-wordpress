<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use Piwik\Piwik;

/** @var array $report */
/** @var array $report_meta */
/** @var string $first_metric_name */
/** @var string $first_metric_display_name */
?>
<div class="table">
    <table class="widefat matomo-table">
        <thead>
        <tr>
            <th width="75%"><?php echo esc_html( $report_meta['dimension'] ) ?></th>
            <th class="right"><?php echo $first_metric_display_name ?></th>
        </tr>
        </thead>

        <tbody>
		<?php
		$report_metadata = $report['reportMetadata'];
		foreach ( $report['reportData']->getRows() as $reportId => $reportRow ) {
			if ( ! empty( $reportRow[ $first_metric_name ] ) ) {
				$logo_image = '';
				$meta_row   = $report_metadata->getRowFromId( $reportId );
				if ( ! empty( $meta_row ) ) {
					$logo = $meta_row->getColumn( 'logo' );
					if ( ! empty( $logo ) ) {
						$logo_image = '<img height="16" src="' . plugins_url( 'app/' . $logo, MATOMO_ANALYTICS_FILE ) . '"> ';
					}
				}

				echo '<tr><td width="75%">' . $logo_image . esc_html( $reportRow['label'] ) . '</td><td width="25%">' . esc_html( $reportRow[ $first_metric_name ] ) . '</td></tr>';
			}
		} ?>
        </tbody>
    </table>
</div>
