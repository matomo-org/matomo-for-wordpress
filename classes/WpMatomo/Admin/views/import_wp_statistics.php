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
use WpMatomo\Admin\AdminSettings;
use WpMatomo\Admin\GetStarted;
use WpMatomo\Admin\Menu;
use WpMatomo\Admin\TrackingSettings;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="wrap">
	<div id="icon-plugins" class="icon32"></div>

	<h1><?php esc_html_e( 'How to import your wp-statistics data into matomo?', 'matomo' ); ?></h1>

		<h2>1. <?php esc_html_e( 'Download the wp cli', 'matomo' ); ?></h2>

		<?php echo sprintf( esc_html__( 'The wp cli is the official WordPress client command line tool. You can download it %s.', 'matomo' ), '<a href="https://wp-cli.org/" target="_blank">here</a>' ); ?>
		<br/>
		<?php esc_html_e( 'Move the wp-cli.phar file into your website root folder.', 'matomo' ); ?>
	<h2>2. <?php esc_html_e( 'Run the import', 'matomo' ); ?></h2>

	<?php echo sprintf( esc_html__( 'Run the command %1$s.', 'matomo' ), '<code>php wp-cli.phar matomo importWpStatistics</code>' ); ?>

	<h2>3. <?php esc_html_e( 'Done', 'matomo' ); ?></h2>
	<p>
		<br/>
	</p>

	<?php
	$show_troubleshooting_link = false;
	require 'info_help.php';
	?>
</div>
