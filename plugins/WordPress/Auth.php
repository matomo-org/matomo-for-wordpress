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
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Log\LoggerInterface;
use Piwik\Plugins\UsersManager\Model;
use Piwik\SettingsServer;
use Piwik\Tracker\TrackerConfig;
use WpMatomo\Capabilities;
use WpMatomo\User;
use WpMatomo\User\Sync;

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
        // authenticate app password provided via Authorization header. for tracking,
        // a dummy token_auth is still required.
        $result = $this->authWithAppPassword();
        if (!empty($result)) {
            return $result;
        }

        // matomo token_auths are never allowed to authenticate on their own. the matomo UI
        // authenticates through WordPress\SessionAuth (which uses a valid WP session plus the
        // matomo-ui nonce). programmatic access is only allowed via WP application passwords.
        $isUserLoggedIn = function_exists('is_user_logged_in') && is_user_logged_in();
        if (!$isUserLoggedIn && $this->isAppPasswordInTokenAuthAllowed()) {
            $result = $this->authApiWithTokenAuthAppPassword();
            if (!empty($result)) {
                return $result;
            }
        }

        $login = 'anonymous';
        return new AuthResult(AuthResult::FAILURE, $login, $this->token_auth);
    }

    private function authWithAppPassword()
    {
        if (!function_exists('wp_validate_application_password')) {
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

        return $this->makeAuthResultForWpUser($loggedInUserId);
    }

    /**
     * @param int $wpUserId
     * @return AuthResult|null null when the user must not be authenticated
     */
    private function makeAuthResultForWpUser($wpUserId)
    {
        $code = null;

        if (Capabilities::is_capability_check_available()) {
            if (user_can($wpUserId, Capabilities::KEY_SUPERUSER)) {
                $code = AuthResult::SUCCESS_SUPERUSER_AUTH_CODE;
            } elseif (user_can($wpUserId, Capabilities::KEY_VIEW)) {
                $code = AuthResult::SUCCESS;
            }

            if ($code === null) {
                return null;
            }
        }

        $login = User::get_matomo_user_login($wpUserId);

        $userModel = new Model();
        $matomoUser = $userModel->getUser($login);
        if (empty($matomoUser)) {
            return null;
        }

        if ($code === null) {
            // safe mode only, see Capabilities::is_capability_check_available(). the hooks that
            // synthesise most matomo capabilities are not registered, so user_can() would reject
            // administrators and anyone covered by the role mapping. fall back to the persisted
            // access.
            $code = ((int) $matomoUser['superuser_access']) ? AuthResult::SUCCESS_SUPERUSER_AUTH_CODE : AuthResult::SUCCESS;
        } elseif ((new Sync())->sync_user_if_access_exceeds_capabilities($wpUserId, $matomoUser)) {
            // matomo's authorization layer trusts the persisted per site role verbatim, so the call
            // above corrects it when it grants more than the user's live WordPress capabilities do.
            // syncing can remove a user from Matomo, so re-read the one we authenticate as.
            $login = User::get_matomo_user_login($wpUserId);
            if (empty($login)) {
                return null;
            }
        }

        return new AuthResult($code, $login, $this->token_auth);
    }

    private function isAppPasswordInTokenAuthAllowed()
    {
        $wordPressConfig = Config::getInstance()->WordPress;
        $allowed = !empty( $wordPressConfig['allow_app_password_as_token_auth'] ) && strval( $wordPressConfig['allow_app_password_as_token_auth'] ) === '1';
        return $allowed;
    }

    private function authApiWithTokenAuthAppPassword()
    {
        $tokenAuth = $this->token_auth;
        if (empty($tokenAuth)) {
            return null;
        }

        $logger = StaticContainer::get(LoggerInterface::class);

        if (!function_exists('wp_validate_application_password')) {
            $logger->debug('WordPress\\Auth: wp_validate_application_password does not exist');
            return null;
        }

        $parts = explode(':', $tokenAuth);
        if (count($parts) !== 2) {
            $logger->debug('WordPress\\Auth: app password provided in token_auth has incorrect format, expected "username:apppassword".');
            return null;
        }

        if (
            empty($_SERVER['REQUEST_METHOD'])
            || strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST'
        ) {
            throw new \Exception('Invalid token auth or token auth was not provided as a POST parameter.');
        }

        [$user, $pass] = $parts;

        $callback = function () { return true; };

        add_filter('application_password_is_api_request', $callback);
        try {
            $authenticated = wp_authenticate_application_password(null, $user, $pass);
            if (!($authenticated instanceof \WP_User)) {
                return null;
            }
            $loggedInUserId = $authenticated->ID;
        } finally {
            remove_filter('application_password_is_api_request', $callback);
        }

        return $this->makeAuthResultForWpUser($loggedInUserId);
    }
}
