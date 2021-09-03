<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */
/**
 * phpcs considers all of our variables as global and want them prefixed with matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
use WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>

	<?php require 'info_shared.php'; ?>
	<?php
	$show_troubleshooting_link = true;
	require 'info_help.php';
	?>

	<h2><?php esc_html_e( 'Support the project', 'matomo' ); ?></h2>
	<p>
		<?php
		echo sprintf(
			esc_html__(
				'Matomo is a collaborative project brought to you by %1$sMatomo team%2$s members as well as many other contributors around the globe. If you like Matomo,
        %3$splease give us a review%4$s and spread the word about us.',
				'matomo'
			),
			'<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/team/">',
			'</a>',
			'<a target="_blank" rel="noreferrer noopener" href="https://wordpress.org/support/plugin/matomo/reviews/?rate=5#new-post">',
			'<span class="dashicons-before dashicons-star-filled" style="color:gold;"></span><span class="dashicons-before dashicons-star-filled" style="color:gold;"></span><span class="dashicons-before dashicons-star-filled" style="color:gold;"></span><span class="dashicons-before dashicons-star-filled" style="color:gold;"></span><span class="dashicons-before dashicons-star-filled" style="color:gold;"></span></a>'
		);
		?>
		<br/><br/>
		Matomo will always cost you nothing to use, but that doesn't mean it costs us nothing to make.
		Matomo needs your continued support to grow and thrive.
		<?php
		echo sprintf(
			esc_html__(
				'You can also help us by %1$sdonating%2$s or by %3$spurchasing premium plugins%4$s which fund the
        development of the free Matomo Analytics version.',
				'matomo'
			),
			'<a href="' . esc_url( Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_ADMIN ) ) . '">',
			'</a>',
			'<a href="https://plugins.matomo.org/premium" target="_blank" rel="noreferrer noopener">',
			'</a>'
		);
		?>
		Every penny will help.
	</p>

	<?php require 'info_newsletter.php'; ?>

	<h2><?php esc_html_e( 'High traffic websites', 'matomo' ); ?></h2>
	<?php require 'info_high_traffic.php'; ?>

	<?php require 'info_bug_report.php'; ?>

	<div class="matomo-footer">
		<ul>
			<li>
				<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/newsletter/"><span
							class="dashicons-before dashicons-email"></span></a>
				<a target="_blank" rel="noreferrer noopener"
				   href="https://matomo.org/newsletter/"><?php esc_html_e( 'Newsletter', 'matomo' ); ?></a>
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
