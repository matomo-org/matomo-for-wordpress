<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

use \WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>

	<?php require 'info_shared.php'; ?>
	<?php require 'info_help.php'; ?>

	<h2><?php esc_html_e( 'Support the project', 'matomo' ); ?></h2>
	<p>
	<?php
	echo sprintf(
		__(
			'Matomo is a collaborative project brought to you by %1$sMatomo team%2$s members as well as many other contributors around the globe. If you\'re a fan of Matomo,
        %3$shere\'s how you can participate!%4$s',
			'matomo'
		),
		'<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/team/">',
		'</a>',
		'<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/get-involved/">',
		'</a>'
	);
	?>
		<br/><br/>
		<?php
		echo sprintf(
			__(
				'You can also help us by %1$sdonating%2$s or by %3$spurchasing premium plugins%4$s which fund the
        development of the free Matomo Analytics version.',
				'matomo'
			),
			'<a href="' . Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_ADMIN ) . '">',
			'</a>',
			'<a href="https://plugins.matomo.org/premium" target="_blank" rel="noreferrer noopener">',
			'</a>'
		);
		?>
	</p>

	<h2><?php esc_html_e( 'High traffic websites', 'matomo' ); ?></h2>
	<?php require 'info_high_traffic.php'; ?>

	<?php require 'info_bug_report.php'; ?>

	<div class="matomo-footer">
		<ul>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/newsletter/"><span
						class="dashicons-before dashicons-email"></span></a>
				<a target="_blank" rel="noreferrer noopener"
				   href="https://matomo.org/newsletter/"><?php _e( 'Newsletter', 'matomo' ); ?></a>
			</li>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://www.facebook.com/Matomo.org"><span
						class="dashicons-before dashicons-facebook"></span></a>
				<a target="_blank" rel="noreferrer noopener" href="https://www.facebook.com/Matomo.org">Facebook</a>
			</li>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://twitter.com/matomo_org"><span
						class="dashicons-before dashicons-twitter"></span></a>
				<a target="_blank" rel="noreferrer noopener" href="https://twitter.com/matomo_org">Twitter</a>
			</li>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://www.linkedin.com/groups/867857/">Linkedin</a>
			</li>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://github.com/matomo-org/matomo">GitHub</a>
			</li>
		</ul>
		<ul>
			<li><a target="_blank" rel="noreferrer noopener"
				   href="https://matomo.org/blog/"><?php esc_html_e( 'Blog', 'matomo' ); ?></a></li>
			<li><a target="_blank" rel="noreferrer noopener"
				   href="https://developer.matomo.org"><?php esc_html_e( 'Developers', 'matomo' ); ?></a></li>
			<li><a target="_blank" rel="noreferrer noopener"
				   href="https://plugins.matomo.org"><?php esc_html_e( 'Marketplace', 'matomo' ); ?></a></li>
			<li><a target="_blank" rel="noreferrer noopener"
				   href="https://matomo.org/thank-you-all/"><?php esc_html_e( 'Credits', 'matomo' ); ?></a></li>
		</ul>
	</div>
</div>
