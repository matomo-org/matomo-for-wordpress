<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Piwik\Menu\MenuAdmin;
use Piwik\Menu\MenuTop;
use Piwik\Piwik;

class Menu extends \Piwik\Plugin\Menu
{

    public function configureAdminMenu(MenuAdmin $menu)
    {
        $menu->remove('CoreAdminHome_MenuMeasurables', 'SitesManager_MenuManage');
        $menu->remove('SitesManager_Sites', 'SitesManager_MenuManage');
        $menu->remove('CoreAdminHome_MenuSystem', 'UsersManager_MenuUsers');
        $menu->remove('UsersManager_MenuPersonal', 'General_Settings');
        $menu->remove('CoreAdminHome_MenuMeasurables', 'CoreAdminHome_TrackingCode');
        $menu->remove('CoreAdminHome_MenuMeasurables', 'General_Settings');
        $menu->remove('CorePluginsAdmin_MenuPlatform', 'General_API');
        $menu->remove('CoreAdminHome_MenuSystem', Piwik::translate('General_Plugins'));
        $menu->remove('CoreAdminHome_MenuSystem', 'General_Plugins');
        $menu->remove('CoreAdminHome_MenuDiagnostic', 'Diagnostics_ConfigFileTitle');
        $menu->remove('CoreAdminHome_MenuDiagnostic', 'Installation_SystemCheck');
    }

    public function configureTopMenu(MenuTop $menu)
    {
    	$menu->addItem(__('WordPress Admin', 'matomo'), null, $this->urlForAction('goToWordPress'), '500', __('Go back to WordPress Admin Dashboard', 'matomo'), 'icon-close');
	    $menu->remove('General_Help');
    }
}
