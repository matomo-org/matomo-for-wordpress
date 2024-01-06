<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Piwik\AuthResult;
use Piwik\Plugins\UsersManager\Model;
use Piwik\SettingsServer;
use Piwik\Tracker\TrackerConfig;
use WpMatomo\User;

if (!defined( 'ABSPATH')) {
    exit; // if accessed directly
}

class Auth extends \Piwik\Plugins\Login\Auth
{
    public function getName()
    {
        return 'WordPress';
    }

    public function authenticate()
    {
        // tracking request authentication. only executes if a WordPress application password
        // is supplied and if a token_auth is supplied (though the token_auth is ignored).
        $isTrackerApiRequest = SettingsServer::isTrackerApiRequest();
        if ($isTrackerApiRequest) {
            $result = $this->authTrackerWithAppPassword();
            if (!empty($result)) {
                return $result;
            }
        }

        // UI request authentication
        $isUserLoggedIn = function_exists('is_user_logged_in') && is_user_logged_in();
        if ($isUserLoggedIn) {
	        if (is_null($this->login) && empty($this->hashedPassword)) {
	            // api authentication using token
		        return parent::authenticate();
	        }
        }

        $login = 'anonymous';
        return new AuthResult(AuthResult::FAILURE, $login, $this->token_auth);
    }

    private function authTrackerWithAppPassword()
    {
        if (!function_exists('wp_validate_application_password')) {
            return null;
        }

        if (TrackerConfig::getConfigValue('allow_wp_app_password_auth') != 1) {
            return null;
        }

        $callback = function () { return true; };

        add_filter('application_password_is_api_request', $callback);
        try {
            $loggedInUserId = wp_validate_application_password(false);
            $isUserLoggedIn = $loggedInUserId !== false;
        } finally {
            remove_filter('application_password_is_api_request', $callback);
        }

        if (!$isUserLoggedIn) {
            return null;
        }

        $login = User::get_matomo_user_login($loggedInUserId);

        $userModel = new Model();
        $matomoUser = $userModel->getUser($login);
        if (empty($matomoUser)) {
            return null;
        }

        $code = ((int) $matomoUser['superuser_access']) ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;
        return new AuthResult($code, $login, $this->token_auth);
    }
}
