<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 * https://github.com/braekling/matomo
 *
 */

use WpMatomo\Admin\Menu;
use WpMatomo\Admin\PrivacySettings;
use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var Settings $matomo_settings */

?>

<h2><?php esc_html_e( 'Ithemes security configuration', 'matomo' ); ?></h2>
<p>
	<?php esc_html_e( "You use the Ithemes Security plugin. One option is incompatible with Matomo usage. Please make sure to uncheck the checkbox 'Disable PHP in plugins' available from the menu Security > Settings > Advanced > System tweaks", 'matomo' ); ?>
	<img src="<?php echo esc_url( plugin_dir_url( MATOMO_ANALYTICS_FILE ) . 'assets/img/isec-option.png' ); ?>"/>
</p>
