<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Piwik\Common;
use Piwik\Piwik;

class Controller extends \Piwik\Plugin\Controller
{
    public function index()
    {
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url(\WpMatomo\Admin\Menu::get_reporting_url()));
        }
    }

    public function logout()
    {
        if (is_user_logged_in()) {
	        wp_redirect(wp_logout_url());
        }
        exit;
    }

    public function goToWordPress()
    {
        if (is_user_logged_in()) {
            wp_redirect(admin_url());
        }
        exit;
    }
}
