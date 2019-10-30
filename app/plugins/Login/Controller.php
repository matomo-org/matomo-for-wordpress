<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\Login;

use Exception;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Log;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugins\Login\Security\BruteForceDetection;
use Piwik\Plugins\UsersManager\Model AS UsersModel;
use Piwik\QuickForm2;
use Piwik\Session;
use Piwik\Url;
use Piwik\UrlHelper;
use Piwik\View;

/**
 * Login controller
 * @api
 */
class Controller extends \Piwik\Plugin\ControllerAdmin
{
    /**
     * @var PasswordResetter
     */
    protected $passwordResetter;

    /**
     * @var Auth
     */
    protected $auth;

    /**
     * @var \Piwik\Session\SessionInitializer
     */
    protected $sessionInitializer;

    /**
     * @var BruteForceDetection
     */
    protected $bruteForceDetection;

    /**
     * @var SystemSettings
     */
    protected $systemSettings;

    /*
     * @var PasswordVerifier
     */
    protected $passwordVerify;

    /**
     * Constructor.
     *
     * @param PasswordResetter $passwordResetter
     * @param AuthInterface $auth
     * @param SessionInitializer $sessionInitializer
     * @param PasswordVerifier $passwordVerify
     * @param BruteForceDetection $bruteForceDetection
     * @param SystemSettings $systemSettings
     */
    public function __construct($passwordResetter = null, $auth = null, $sessionInitializer = null, $passwordVerify = null, $bruteForceDetection = null, $systemSettings = null)
    {
        parent::__construct();

        if (empty($passwordResetter)) {
            $passwordResetter = new PasswordResetter();
        }
        $this->passwordResetter = $passwordResetter;

        if (empty($auth)) {
            $auth = StaticContainer::get('Piwik\Auth');
        }
        $this->auth = $auth;

        if (empty($passwordVerify)) {
            $passwordVerify = StaticContainer::get('Piwik\Plugins\Login\PasswordVerifier');
        }
        $this->passwordVerify = $passwordVerify;

        if (empty($sessionInitializer)) {
            $sessionInitializer = new \Piwik\Session\SessionInitializer();
        }
        $this->sessionInitializer = $sessionInitializer;

        if (empty($bruteForceDetection)) {
            $bruteForceDetection = StaticContainer::get('Piwik\Plugins\Login\Security\BruteForceDetection');
        }
        $this->bruteForceDetection = $bruteForceDetection;

        if (empty($systemSettings)) {
            $systemSettings = StaticContainer::get('Piwik\Plugins\Login\SystemSettings');
        }
        $this->systemSettings = $systemSettings;
    }

    /**
     * Default action
     *
     * @param none
     * @return string
     */
    function index()
    {
        return $this->login();
    }

    /**
     * Login form
     *
     * @param string $messageNoAccess Access error message
     * @param bool $infoMessage
     * @internal param string $currentUrl Current URL
     * @return string
     */
    function login($messageNoAccess = null, $infoMessage = false)
    {
        $form = new FormLogin();
        if ($form->validate()) {
            $nonce = $form->getSubmitValue('form_nonce');
            if (Nonce::verifyNonce('Login.login', $nonce)) {
                $loginOrEmail = $form->getSubmitValue('form_login');
                $login = $this->getLoginFromLoginOrEmail($loginOrEmail);

                $password = $form->getSubmitValue('form_password');
                try {
                    $this->authenticateAndRedirect($login, $password);
                } catch (Exception $e) {
                    $messageNoAccess = $e->getMessage();
                }
            } else {
                $messageNoAccess = $this->getMessageExceptionNoAccess();
            }
        }
        
        if ($messageNoAccess) {
            http_response_code(403);
        }

        $view = new View('@Login/login');
        $view->AccessErrorString = $messageNoAccess;
        $view->infoMessage = nl2br($infoMessage);
        $view->addForm($form);
        $this->configureView($view);
        self::setHostValidationVariablesView($view);

        return $view->render();
    }

    private function getLoginFromLoginOrEmail($loginOrEmail)
    {
        $model = new UsersModel();
        if (!$model->userExists($loginOrEmail)) {
            $user = $model->getUserByEmail($loginOrEmail);
            if (!empty($user)) {
                return $user['login'];
            }
        }

        return $loginOrEmail;
    }

    /**
     * Configure common view properties
     *
     * @param View $view
     */
    protected function configureView($view)
    {
        $this->setBasicVariablesNoneAdminView($view);

        $view->linkTitle = Piwik::getRandomTitle();

        // crsf token: don't trust the submitted value; generate/fetch it from session data
        $view->nonce = Nonce::getNonce('Login.login');
    }

    public function confirmPassword()
    {
        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasSomeViewAccess();

        if (!$this->passwordVerify->hasPasswordVerifyBeenRequested()) {
            throw new Exception('Not available');
        }

        if (!Url::isValidHost()) {
            throw new Exception("Cannot confirm password with untrusted hostname!");
        }

        $nonceKey = 'confirmPassword';
        $messageNoAccess = '';

        if (!empty($_POST)) {
            $nonce = Common::getRequestVar('nonce', null, 'string', $_POST);
            $password = Common::getRequestVar('password', null, 'string', $_POST);
            if ($password) {
                $password = Common::unsanitizeInputValue($password);
            }
            if (!Nonce::verifyNonce($nonceKey, $nonce)) {
                $messageNoAccess = $this->getMessageExceptionNoAccess();
            } elseif ($this->passwordVerify->isPasswordCorrect(Piwik::getCurrentUserLogin(), $password)) {
                $this->passwordVerify->setPasswordVerifiedCorrectly();
                return;
            } else {
                $messageNoAccess = Piwik::translate('Login_WrongPasswordEntered');
            }
        }

        return $this->renderTemplate('confirmPassword', array(
            'nonce' => Nonce::getNonce($nonceKey),
            'AccessErrorString' => $messageNoAccess
        ));
    }

    /**
     * Form-less login
     * @see how to use it on http://piwik.org/faq/how-to/#faq_30
     * @throws Exception
     * @return void
     */
    function logme()
    {
        $password = Common::getRequestVar('password', null, 'string');

        $login = Common::getRequestVar('login', null, 'string');
        if (Piwik::hasTheUserSuperUserAccess($login)) {
            throw new Exception(Piwik::translate('Login_ExceptionInvalidSuperUserAccessAuthenticationMethod', array("logme")));
        }

        $currentUrl = 'index.php';

        if ($this->idSite) {
            $currentUrl .= '?idSite=' . $this->idSite;
        }

        $urlToRedirect = Common::getRequestVar('url', $currentUrl, 'string');
        $urlToRedirect = Common::unsanitizeInputValue($urlToRedirect);

        $this->authenticateAndRedirect($login, $password, $urlToRedirect, $passwordHashed = true);
    }

    public function bruteForceLog()
    {
        Piwik::checkUserHasSuperUserAccess();

        return $this->renderTemplate('bruteForceLog', array(
            'blockedIps' => $this->bruteForceDetection->getCurrentlyBlockedIps(),
            'blacklistedIps' => $this->systemSettings->blacklistedBruteForceIps->getValue()
        ));
    }

    /**
     * Error message shown when an AJAX request has no access
     *
     * @param string $errorMessage
     * @return string
     */
    public function ajaxNoAccess($errorMessage)
    {
        return sprintf(
            '<div class="alert alert-danger">
                <p><strong>%s:</strong> %s</p>
                <p><a href="%s">%s</a></p>
            </div>',
            Piwik::translate('General_Error'),
            htmlentities($errorMessage, Common::HTML_ENCODING_QUOTE_STYLE, 'UTF-8', $doubleEncode = false),
            'index.php?module=' . Piwik::getLoginPluginName(),
            Piwik::translate('Login_LogIn')
        );
    }

    /**
     * Authenticate user and password.  Redirect if successful.
     *
     * @param string $login user name
     * @param string $password plain-text or hashed password
     * @param string $urlToRedirect URL to redirect to, if successfully authenticated
     * @param bool $passwordHashed indicates if $password is hashed
     * @return string failure message if unable to authenticate
     */
    protected function authenticateAndRedirect($login, $password, $urlToRedirect = false, $passwordHashed = false)
    {
        Nonce::discardNonce('Login.login');

        $this->auth->setLogin($login);
        if ($passwordHashed === false) {
            $this->auth->setPassword($password);
        } else {
            $this->auth->setPasswordHash($password);
        }

        $this->sessionInitializer->initSession($this->auth);

        // remove password reset entry if it exists
        $this->passwordResetter->removePasswordResetInfo($login);

        if (empty($urlToRedirect)) {
            $redirect = Common::unsanitizeInputValue(Common::getRequestVar('form_redirect', false));
            $redirectParams = UrlHelper::getArrayFromQueryString(UrlHelper::getQueryFromUrl($redirect));
            $module = Common::getRequestVar('module', '', 'string', $redirectParams);
            // when module is login, we redirect to home...
            if (!empty($module) && $module !== 'Login' && $module !== Piwik::getLoginPluginName() && $redirect) {
                $host = Url::getHostFromUrl($redirect);
                $currentHost = Url::getHost();
                $currentHost = explode(':', $currentHost, 2)[0];

                // we only redirect to a trusted host
                if (!empty($host) && !empty($currentHost) && $host == $currentHost && Url::isValidHost($host)
                ) {
                    $urlToRedirect = $redirect;
                }
            }
        }

        if (empty($urlToRedirect)) {
            $urlToRedirect = Url::getCurrentUrlWithoutQueryString();
        }

        Url::redirectToUrl($urlToRedirect);
    }

    protected function getMessageExceptionNoAccess()
    {
        $message = Piwik::translate('Login_InvalidNonceOrHeadersOrReferrer', array('<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/faq/how-to-install/#faq_98">', '</a>'));

        $message .= $this->getMessageExceptionNoAccessWhenInsecureConnectionMayBeUsed();

        return $message;
    }

    /**
     * The Session cookie is set to a secure cookie, when SSL is mis-configured, it can cause the PHP session cookie ID to change on each page view.
     * Indicate to user how to solve this particular use case by forcing secure connections.
     *
     * @return string
     */
    protected function getMessageExceptionNoAccessWhenInsecureConnectionMayBeUsed()
    {
        $message = '';
        if(Url::isSecureConnectionAssumedByPiwikButNotForcedYet()) {
            $message = '<br/><br/>' . Piwik::translate('Login_InvalidNonceSSLMisconfigured',
                    array(
                        '<a target="_blank" rel="noreferrer noopener" href="https://matomo.org/faq/how-to/faq_91/">',
                        '</a>',
                        'config/config.ini.php',
                        '<pre>force_ssl=1</pre>',
                        '<pre>[General]</pre>',
                    )
                );
        }
        return $message;
    }

    /**
     * Reset password action. Stores new password as hash and sends email
     * to confirm use.
     *
     */
    function resetPassword()
    {
        $infoMessage = null;
        $formErrors = null;

        $form = new FormResetPassword();
        if ($form->validate()) {
            $nonce = $form->getSubmitValue('form_nonce');
            if (Nonce::verifyNonce('Login.login', $nonce)) {
                $formErrors = $this->resetPasswordFirstStep($form);
                if (empty($formErrors)) {
                    $infoMessage = Piwik::translate('Login_ConfirmationLinkSent');
                }
            } else {
                $formErrors = array($this->getMessageExceptionNoAccess());
            }
        } else {
            // if invalid, display error
            $formData = $form->getFormData();
            $formErrors = $formData['errors'];
        }

        $view = new View('@Login/resetPassword');
        $view->infoMessage = $infoMessage;
        $view->formErrors = $formErrors;

        return $view->render();
    }

    /**
     * Saves password reset info and sends confirmation email.
     *
     * @param QuickForm2 $form
     * @return array Error message(s) if an error occurs.
     */
    protected function resetPasswordFirstStep($form)
    {
        $loginMail = $form->getSubmitValue('form_login');
        $password  = $form->getSubmitValue('form_password');

        try {
            $this->passwordResetter->initiatePasswordResetProcess($loginMail, $password);
        } catch (Exception $ex) {
            Log::debug($ex);

            return array($ex->getMessage());
        }

        return null;
    }

    /**
     * Password reset confirmation action. Finishes the password reset process.
     * Users visit this action from a link supplied in an email.
     */
    public function confirmResetPassword()
    {
        $errorMessage = null;

        $login = Common::getRequestVar('login', '');
        $resetToken = Common::getRequestVar('resetToken', '');

        try {
            $this->passwordResetter->confirmNewPassword($login, $resetToken);
        } catch (Exception $ex) {
            Log::debug($ex);

            $errorMessage = $ex->getMessage();
        }

        if (is_null($errorMessage)) { // if success, show login w/ success message
            return $this->resetPasswordSuccess();
        } else {
            // show login page w/ error. this will keep the token in the URL
            return $this->login($errorMessage);
        }
    }

    /**
     * The action used after a password is successfully reset. Displays the login
     * screen with an extra message. A separate action is used instead of returning
     * the HTML in confirmResetPassword so the resetToken won't be in the URL.
     */
    public function resetPasswordSuccess()
    {
        return $this->login($errorMessage = null, $infoMessage = Piwik::translate('Login_PasswordChanged'));
    }

    /**
     * Clear session information
     *
     * @return void
     */
    public static function clearSession()
    {
        $sessionFingerprint = new Session\SessionFingerprint();
        $sessionFingerprint->clear();

        Session::expireSessionCookie();
    }

    /**
     * Logout current user
     *
     * @param none
     * @return void
     */
    public function logout()
    {
        Piwik::postEvent('Login.logout', array(Piwik::getCurrentUserLogin()));

        self::clearSession();

        $logoutUrl = @Config::getInstance()->General['login_logout_url'];
        if (empty($logoutUrl)) {
            Piwik::redirectToModule('CoreHome');
        } else {
            Url::redirectToUrl($logoutUrl);
        }
    }
}
