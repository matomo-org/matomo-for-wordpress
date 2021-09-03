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
<h1><?php esc_html_e( 'About', 'matomo' ); ?><?php matomo_header_icon( true ); ?> </h1>

<p>
	<?php
	echo sprintf(
		esc_html__(
			'%1$sMatomo Analytics%2$s is the most powerful
    analytics platform for WordPress, designed for your success. It is our mission to help you grow
    your business while giving you %3$sfull control over your data%4$s. All
    data is stored in your WordPress. You own the data, nobody else.',
			'matomo'
		),
		'<a target="_blank" rel="noreferrer noopener" href="https://matomo.org">',
		'</a>',
		'<strong>',
		'</strong>'
	);
	?>
</p>
<ul class="matomo-list">
	<li><?php esc_html_e( '100% data ownership, no one else can see your data', 'matomo' ); ?></li>
	<li><?php esc_html_e( 'Powerful web analytics for WordPress', 'matomo' ); ?></li>
	<li><?php esc_html_e( 'Superb user privacy protection', 'matomo' ); ?></li>
	<li><?php esc_html_e( 'No data limits or sampling whatsoever', 'matomo' ); ?></li>
	<li><?php esc_html_e( 'Easy installation and configuration', 'matomo' ); ?></li>
</ul>
