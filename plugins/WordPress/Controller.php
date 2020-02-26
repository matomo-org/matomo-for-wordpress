<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

if (!defined( 'ABSPATH')) {
	exit; // if accessed directly
}

class Controller extends \Piwik\Plugin\Controller
{
    public function index()
    {
        if (!is_user_logged_in()) {
        	$redirect_url = WordPress::getWpLoginUrl();
	        wp_safe_redirect($redirect_url);
        }
    }

    public function logout()
    {
        if (is_user_logged_in()) {
	        wp_safe_redirect(wp_logout_url());
        }
        exit;
    }

    public function goToWordPress()
    {
        if (is_user_logged_in()) {
	        wp_safe_redirect(admin_url());
        }
        exit;
    }
}
