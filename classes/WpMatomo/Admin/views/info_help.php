<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
?>
<h2><?php esc_html_e( 'How can we help?', 'matomo' ); ?></h2>

<form method="get" action="https://matomo.org" target="_blank">
	<input type="text" name="s" style="width:300px;"><input type="submit" class="button-secondary"
															value="Search on matomo.org">
</form>
<ul class="matomo-list">
	<li><a target="_blank" rel="noreferrer noopener"
		   href="https://matomo.org/docs/"><?php esc_html_e( 'User guides', 'matomo' ); ?></a>
		- <?php esc_html_e( 'Learn how to configure Matomo and how to effectively analyse your data', 'matomo' ); ?>
	</li>
	<li><a target="_blank" rel="noreferrer noopener" href="https://matomo.org/faq/"><?php esc_html_e( 'FAQs', 'matomo' ); ?></a>
		- <?php esc_html_e( 'Get answers to frequently asked questions', 'matomo' ); ?>
	</li>
	<li><a target="_blank" rel="noreferrer noopener"
		   href="https://forum.matomo.org/"><?php esc_html_e( 'Forums', 'matomo' ); ?></a>
		- <?php esc_html_e( 'Get help directly from the community of Matomo users', 'matomo' ); ?>
	</li>
	<li><a target="_blank" rel="noreferrer noopener"
		   href="https://glossary.matomo.org"><?php esc_html_e( 'Glossary', 'matomo' ); ?> </a>
		- <?php esc_html_e( 'Learn about commonly used terms to make the most of Matomo Analytics', 'matomo' ); ?>
	</li>
	<li><a target="_blank" rel="noreferrer noopener"
		   href="https://matomo.org/support-plans/"><?php esc_html_e( 'Support Plans', 'matomo' ); ?></a>
		- <?php esc_html_e( 'Let our experienced team assist you online on how to best utilise Matomo', 'matomo' ); ?>
	</li>
</ul>
