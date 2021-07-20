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
use Piwik\Common;
use Piwik\FrontController;
use Piwik\Piwik;
use Piwik\Plugins\Login\PasswordVerifier;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Session;
use Piwik\SettingsPiwik;
use Piwik\Url;
use WpMatomo\Capabilities;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\User;

if (!defined( 'ABSPATH')) {
    exit; // if accessed directly
}

class WpPasswordVerifier extends PasswordVerifier
{
	public function isPasswordCorrect($userLogin, $password)
	{
		/**
		 * @ignore
		 * @internal
		 */
		Piwik::postEvent('Login.beforeLoginCheckAllowed');

		if (Url::isValidHost()
		    && (!Url::getReferrer() || Url::isValidHost(Url::getHostFromUrl(Url::getReferrer())))
			&& function_exists('is_user_logged_in')
			&& is_user_logged_in()
		) {
			$user = wp_get_current_user();
			// check if this password is the login's password
			$authenticatedUser = wp_authenticate($user->user_login, $password);
			if ($authenticatedUser
			       && $authenticatedUser instanceof \WP_User
			       && $authenticatedUser->ID === $user->ID) {
				return true;
			};
		}

		/**
		 * @ignore
		 * @internal
		 */
		Piwik::postEvent('Login.recordFailedLoginAttempt');

		return false;
	}
}
