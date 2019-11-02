<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

use WpMatomo\Admin\AdminSettings;
use WpMatomo\Admin\AdminSettingsInterface;
use WpMatomo\Admin\Menu;
use WpMatomo\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var AdminSettingsInterface[] $setting_tabs */
/** @var AdminSettingsInterface $content_tab */
/** @var string $active_tab */
?>
<div class="wrap">
    <div id="icon-plugins" class="icon32"></div>
    <h2 class="nav-tab-wrapper">
		<?php foreach ( $setting_tabs as $setting_slug => $setting_tab ) { ?>
            <a href="<?php echo AdminSettings::make_url( $setting_slug ) ?>"
               class="nav-tab <?php echo $active_tab === $setting_slug ? 'nav-tab-active' : ''; ?>"
            ><?php echo $setting_tab->get_title() ?></a>
		<?php } ?>

		<?php if ( current_user_can( Capabilities::KEY_SUPERUSER )
		           && ! is_network_admin() ) { ?>
            <a href="<?php echo Menu::get_matomo_goto_url( Menu::REPORTING_GOTO_ADMIN ); ?>" class="nav-tab"
            ><?php _e( 'Matomo Admin', 'matomo' ); ?> <span class="dashicons-before dashicons-external"></span></a>

		<?php } ?>
    </h2>

	<?php echo $content_tab->show_settings() ?>
</div>
