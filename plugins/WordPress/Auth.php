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
        }

        $login = 'anonymous';
        return new AuthResult(AuthResult::FAILURE, $login, $this->token_auth);
    }

}
