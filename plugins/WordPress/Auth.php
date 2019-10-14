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
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site;
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
        if (function_exists('is_user_logged_in') && is_user_logged_in()) {

	        if (is_null($this->login) && empty($this->hashedPassword)) {
	            // api authentication using token
		        return parent::authenticate();
	        }

            $user = wp_get_current_user();

            if ($user && current_user_can(Capabilities::KEY_SUPERUSER)) {
                $user = $this->findMatomoUser($user->ID);
                return new AuthResult(AuthResult::SUCCESS_SUPERUSER_AUTH_CODE, $user['login'], $user['token_auth']);
            }

            if ($user && current_user_can(Capabilities::KEY_VIEW)) {
                $user = $this->findMatomoUser($user->ID);
                return new AuthResult(AuthResult::SUCCESS, $user['login'], $user['token_auth']);
            }
        }

        $login = 'anonymous';
        return new AuthResult(AuthResult::FAILURE, $login, $this->token_auth);
    }

    private function findMatomoUser($userId, $syncIfNotFound = true)
    {
	    $login = User::get_matomo_user_login($userId);

	    if ($login) {
			// user is already synced
		    $userModel = new Model();
		    $user      = $userModel->getUser($login);
	    }

        if (empty($user['token_auth'])) {
            if ($syncIfNotFound) {
            	$site = new Site\Sync(new Settings());
            	$site->sync_current_site();

                // user should be synced...
                $sync = new User\Sync();
                $sync->sync_current_users();

                return $this->findMatomoUser($userId, $syncIfNotFound = false);
            }
            throw new \Exception('User is not syncronized yet, please try again later');
        }
        return $user;
    }
}
