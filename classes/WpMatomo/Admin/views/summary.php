<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use WpMatomo\Admin\Menu;
use WpMatomo\Report\Dates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array $report_metadata */
/** @var array $report_dates */
/** @var array $reports_to_show */
/** @var string $report_date */
/** @var string $report_period_selected */
/** @var string $report_date_selected */
/** @var bool $is_tracking */
global $wp;
?>
<?php if ( ! $is_tracking ) { ?>
	<div class="notice notice-warning"><p><?php echo __( 'Matomo Tracking is not enabled.', 'matomo' ); ?></p></div>
<?php } ?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>
	<h1><?php esc_html_e( 'Summary', 'matomo' ); ?></h1>
	<?php
	if ( Dates::TODAY === $report_date ) {
		echo '<div class="notice notice-info" style="padding:8px;">' . __( 'Reports for today are only refreshed approximately every hour through the WordPress cronjob.', 'matomo' ) . '</div>';
	}
	?>
	<p><?php esc_html_e( 'Looking for all reports and advanced features like segmentation, real time reports, and more?', 'matomo' ); ?>
		<a href="<?php echo add_query_arg( array( 'report_date' => $report_date ), menu_page_url( Menu::SLUG_REPORTING, false ) ); ?>"
		><?php esc_html_e( 'View full reporting', 'matomo' ); ?></a>
		<br/><br/>
		<?php esc_html_e( 'Change date:', 'matomo' ); ?>
		<?php
		foreach ( $report_dates as $report_date_key => $report_name ) {
			$button_class = 'button';
			if ( $report_date === $report_date_key ) {
				$button_class = 'button-primary';
			}
			echo '<a href="' . esc_url( add_query_arg( array( 'report_date' => $report_date_key ), menu_page_url( Menu::SLUG_REPORT_SUMMARY, false ) ) ) . '" class="' . $button_class . '">' . esc_html( $report_name ) . '</a> ';
		}
		?>

	<div id="dashboard-widgets" class="metabox-holder columns-2 has-right-sidebar">
		<?php
		$columns = array( 1, 0 );
		foreach ( $columns as $column_index => $column_modulo ) {
			?>
			<div id="postbox-container-<?php echo( $column_index + 1 ); ?>" class="postbox-container">
				<div id="normal-sortables" class="meta-box-sortables ui-sortable">
					<?php
					foreach ( $reports_to_show as $index => $report_meta ) {
						if ( $index % 2 === $column_modulo ) {
							continue;
						}
						$shortcode = sprintf( '[matomo_report unique_id=%s report_date=%s limit=10]', $report_meta['uniqueId'], $report_date );
						?>
						<div class="postbox">

							<?php if ( ! empty( $report_meta['page'] ) ) { ?>
								<button type="button" class="handlediv" aria-expanded="true"
										title="<?php _e( 'Click to view the report in detail', 'matomo' ); ?>"><a
										href="
										<?php
										echo Menu::get_matomo_reporting_url(
											$report_meta['page']['category'],
											$report_meta['page']['subcategory'],
											array(
												'period' => $report_period_selected,
												'date'   => $report_date_selected,
											)
										);
										?>
												" style="color: inherit;" target="_blank" rel="noreferrer noopener"
										class="dashicons-before dashicons-external" aria-hidden="true"></a></button>
							<?php } ?>
							<h2 class="hndle ui-sortable-handle"
								style="cursor: help;"
								title="<?php echo ! empty( $report_meta['documentation'] ) ? ( wp_strip_all_tags( $report_meta['documentation'] ) . ' ' ) : null; ?><?php _e( 'You can embed this report on any page using the shortcode:', 'matomo' ); ?> <?php echo esc_attr( $shortcode ); ?>"
							><?php echo esc_html( $report_meta['name'] ); ?></h2>
							<div>
								<?php echo do_shortcode( $shortcode ); ?>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php } ?>
	</div>

	<p style="clear:both;">
		<?php esc_html_e( 'Did you know? You can embed any report into any page or post using a shortcode. Simply hover the title to find the correct shortcode.', 'matomo' ); ?>
		<?php esc_html_e( 'Only users with view access will be able to view the content of the report.', 'matomo' ); ?>
		<?php esc_html_e( 'Note: Embedding report data can be tricky if you are using caching plugins that cache the entire HTML of your page or post. In case you are using such a plugin, we recommend you disable the caching for these pages.', 'matomo' ); ?>
	</p>
</div>
