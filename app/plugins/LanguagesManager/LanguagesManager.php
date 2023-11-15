<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 *
 */
namespace Piwik\Plugins\LanguagesManager;

use Exception;
use Piwik\API\Request;
use Piwik\AssetManager\UIAssetFetcher\PluginUmdAssetFetcher;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Cookie;
use Piwik\Intl\Locale;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\ProxyHttp;
use Piwik\Translation\Translator;
use Piwik\View;

/**
 *
 */
class LanguagesManager extends \Piwik\Plugin
{
    const LANGUAGE_SELECTION_NONCE = 'LanguagesManager.selection';

    /**
     * @see \Piwik\Plugin::registerEvents
     */
    public function registerEvents()
    {
        return array(
            'Translate.getClientSideTranslationKeys' => 'getClientSideTranslationKeys',
            'Config.NoConfigurationFile'                 => 'initLanguage',
            'Request.dispatchCoreAndPluginUpdatesScreen' => 'initLanguage',
            'Request.dispatch'                           => 'initLanguage',
            'Platform.initialized'                       => 'initLanguage',
            'UsersManager.deleteUser'                    => 'deleteUserLanguage',
            'Template.topBar'                            => 'addLanguagesManagerToOtherTopBar',
            'Template.jsGlobalVariables'                 => 'jsGlobalVariables',
            'Db.getTablesInstalled'                      => 'getTablesInstalled'
        );
    }

    public function getClientSideTranslationKeys(&$translations)
    {
        $translations[] = 'LanguagesManager_TranslationSearch';
        $translations[] = 'LanguagesManager_AboutPiwikTranslations';
    }

    /**
     * Register the new tables, so Matomo knows about them.
     *
     * @param array $allTablesInstalled
     */
    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled[] = Common::prefixTable('user_language');
    }

    /**
     * Adds the languages drop-down list to topbars other than the main one rendered
     * in CoreHome/templates/top_bar.twig. The 'other' topbars are on the Installation
     * and CoreUpdater screens.
     */
    public function addLanguagesManagerToOtherTopBar(&$str)
    {
        // piwik object & scripts aren't loaded in 'other' topbars
        $str .= "<script type='text/javascript'>if (!window.piwik) window.piwik={};</script>";
        $file = PluginUmdAssetFetcher::getUmdFileToUseForPlugin('LanguagesManager');
        $str .= "<script type='text/javascript' src='$file' defer></script>";
        $str .= $this->getLanguagesSelector();
    }

    /**
     * Adds the languages drop-down list to topbars other than the main one rendered
     * in CoreHome/templates/top_bar.twig. The 'other' topbars are on the Installation
     * and CoreUpdater screens.
     */
    public function jsGlobalVariables(&$str)
    {
        // piwik object & scripts aren't loaded in 'other' topbars
        $str .= "piwik.languageName = '" .  self::getLanguageNameForCurrentUser() . "';";
    }

    /**
     * Renders and returns the language selector HTML.
     *
     * @return string
     */
    public function getLanguagesSelector()
    {
        $view = new View("@LanguagesManager/getLanguagesSelector");
        $view->languages = API::getInstance()->getAvailableLanguageNames();
        $view->currentLanguageCode = self::getLanguageCodeForCurrentUser();
        $view->currentLanguageName = self::getLanguageNameForCurrentUser();
        $view->nonce = Nonce::getNonce(self::LANGUAGE_SELECTION_NONCE);
        return $view->render();
    }

    public function initLanguage()
    {
        /** @var Translator $translator */
        $translator = StaticContainer::get('Piwik\Translation\Translator');

        $language = Common::getRequestVar('language', '', 'string');
        if (empty($language)) {
            $userLanguage = self::getLanguageCodeForCurrentUser();
            if (API::getInstance()->isLanguageAvailable($userLanguage)) {
                $language = $userLanguage;
            }
        }
        if (!empty($language) && API::getInstance()->isLanguageAvailable($language)) {
            $translator->setCurrentLanguage($language);
        }

        $locale = $translator->translate('General_Locale');
        Locale::setLocale($locale);
    }

    public function deleteUserLanguage($userLogin)
    {
        $model = new Model();
        $model->deleteUserLanguage($userLogin);
    }

    /**
     * @throws Exception if non-recoverable error
     */
    public function install()
    {
        Model::install();
    }

    /**
     * @throws Exception if non-recoverable error
     */
    public function uninstall()
    {
        Model::uninstall();
    }

    /**
     * @return boolean
     */
    public static function uses12HourClockForCurrentUser()
    {
        try {
            $currentUser = Piwik::getCurrentUserLogin();
            return Request::processRequest('LanguagesManager.uses12HourClockForUser', array('login' => $currentUser));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return string Two letters language code, eg. "fr"
     */
    public static function getLanguageCodeForCurrentUser()
    {
        $languageCode = self::getLanguageFromPreferences();
        if (!API::getInstance()->isLanguageAvailable($languageCode)) {
            $languageCode = Common::extractLanguageAndRegionCodeFromBrowserLanguage(Common::getBrowserLanguage(), API::getInstance()->getAvailableLanguages());
        }
        if (!API::getInstance()->isLanguageAvailable($languageCode)) {
            $languageCode = StaticContainer::get('Piwik\Translation\Translator')->getDefaultLanguage();
        }
        return $languageCode;
    }

    /**
     * @return string Full english language string, eg. "French"
     */
    public static function getLanguageNameForCurrentUser()
    {
        $languageCode = self::getLanguageCodeForCurrentUser();
        $languages = API::getInstance()->getAvailableLanguageNames();
        foreach ($languages as $language) {
            if ($language['code'] === $languageCode) {
                return $language['name'];
            }
        }
        return false;
    }

    /**
     * @return string|false if language preference could not be loaded
     */
    protected static function getLanguageFromPreferences()
    {
        if (($language = self::getLanguageForSession()) != null) {
            return $language;
        }

        try {
            $currentUser = Piwik::getCurrentUserLogin();
            return API::getInstance()->getLanguageForUser($currentUser);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the language for the session
     *
     * @return string|null
     */
    public static function getLanguageForSession()
    {
        $cookieName = Config::getInstance()->General['language_cookie_name'];
        $cookie = new Cookie($cookieName);
        if ($cookie->isCookieFound()) {
            return $cookie->get('language');
        }
        return null;
    }

    /**
     * Set the language for the session
     *
     * @param string $languageCode ISO language code
     * @return bool
     */
    public static function setLanguageForSession($languageCode)
    {
        if (!API::getInstance()->isLanguageAvailable($languageCode)) {
            return false;
        }

        $cookieName = Config::getInstance()->General['language_cookie_name'];
        $cookie = new Cookie($cookieName, 0);
        $cookie->set('language', $languageCode);
        $cookie->setSecure(ProxyHttp::isHttps());
        $cookie->setHttpOnly(true);
        $cookie->save('Lax');
        return true;
    }
}
