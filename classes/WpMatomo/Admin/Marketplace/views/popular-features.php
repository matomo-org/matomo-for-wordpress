<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

/** @var array $matomo_popular_features */
?>

<?php foreach ( $matomo_popular_features as $matomo_feature_slug => $matomo_feature_info ) { ?>
<div class="matomo-popular-feature">
	<div class="description">
		<h3 class="matomo-feature-header matomo-primary-color-fg">
			<span><?php echo esc_html( $matomo_feature_info['name'] ); ?></span>
			<?php if ( ! empty( $matomo_feature_info['price'] ) ) { ?>
			<span class="matomo-price"><?php echo esc_html( $matomo_feature_info['price'] ); ?></span>
			<?php } ?>
		</h3>
		<p><?php echo esc_html( $matomo_feature_info['desc'] ); ?></p>
	</div>

	<?php if ( isset( $matomo_feature_info['img'] ) ) { ?>
	<div
		class="cover-image"
		style="background-image: url(<?php echo esc_attr( plugins_url( 'assets/img/suggestions/' . $matomo_feature_info['img'], MATOMO_ANALYTICS_FILE ) ); ?>)"
	>
	</div>
	<?php } ?>

	<a class="learn-more" href="<?php echo esc_attr( 'https://plugins.matomo.org/' . $matomo_feature_slug . '?wp=1' ); ?>" target="_blank">
		<button class="button-primary"><?php esc_html_e( 'Learn more', 'matomo' ); ?></button>
	</a>
</div>
<?php } ?>
