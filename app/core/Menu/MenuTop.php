<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Menu;

/**
 * Contains menu entries for the Top menu (the menu at the very top of the page).
 * Plugins can implement the `configureTopMenu()` method of the `Menu` plugin class to add, rename of remove
 * items. If your plugin does not have a `Menu` class yet you can create one using `./console generate:menu`.
 *
 * @method static \Piwik\Menu\MenuTop getInstance()
 */
class MenuTop extends \Piwik\Menu\MenuAbstract
{
    /**
     * Directly adds a menu entry containing html.
     *
     * @param string $menuName
     * @param string $data
     * @param boolean $displayedForCurrentUser
     * @param int $order
     * @param string $tooltip Tooltip to display.
     * @api
     */
    public function addHtml($menuName, $data, $displayedForCurrentUser, $order, $tooltip)
    {
        if ($displayedForCurrentUser) {
            if (!isset($this->menu[$menuName])) {
                $this->menu[$menuName]['_name'] = $menuName;
                $this->menu[$menuName]['_html'] = $data;
                $this->menu[$menuName]['_order'] = $order;
                $this->menu[$menuName]['_url'] = null;
                $this->menu[$menuName]['_icon'] = '';
                $this->menu[$menuName]['_hasSubmenu'] = false;
                $this->menu[$menuName]['_tooltip'] = $tooltip;
            }
        }
    }
    /**
     * Triggers the Menu.Top.addItems hook and returns the menu.
     *
     * @return array
     */
    public function getMenu()
    {
        if (!$this->menu) {
            foreach ($this->getAllMenus() as $menu) {
                $menu->configureTopMenu($this);
            }
        }
        return parent::getMenu();
    }
}
