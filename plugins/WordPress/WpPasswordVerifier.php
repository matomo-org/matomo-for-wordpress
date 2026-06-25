<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress;

use Piwik\Piwik;
use Piwik\Plugins\Login\PasswordVerifier;
use Piwik\Plugins\UsersManager\Model;
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

		// find the WP user for the given matomo user
		$wpUser = $this->findWordPressUser($userLogin);
		if ($wpUser instanceof \WP_User && $wpUser->ID) {
			// wp_authenticate makes sure lockout/brute force features by security plugins are applied etc
			$authenticatedUser = wp_authenticate($wpUser->user_login, $password);
			if ($authenticatedUser instanceof \WP_User
				&& (int) $authenticatedUser->ID === (int) $wpUser->ID) {
				return true;
			}
		}

		/**
		 * @ignore
		 * @internal
		 */
		Piwik::postEvent('Login.recordFailedLoginAttempt');

		return false;
	}

	private function findWordPressUser($matomoLogin)
	{
		if (empty($matomoLogin) || !function_exists('get_user_by')) {
			return null;
		}

		// common case: the Matomo login is identical to the WordPress user_login.
		$wpUser = get_user_by('login', $matomoLogin);
		if (
            $wpUser instanceof \WP_User
			&& $matomoLogin === User::get_matomo_user_login($wpUser->ID)
        ) {
			return $wpUser;
		}

		// the Matomo login may have been sanitized/truncated/prefixed and differ from the WP
		// user_login, so fall back to resolving it via the (shared) email of the Matomo user.
		$matomoUser = (new Model())->getUser($matomoLogin);
		if (!empty($matomoUser['email'])) {
			$wpUser = get_user_by('email', $matomoUser['email']);
			if (
                $wpUser instanceof \WP_User
				&& $matomoLogin === User::get_matomo_user_login($wpUser->ID)
            ) {
				return $wpUser;
			}
		}

		return null;
	}
}
