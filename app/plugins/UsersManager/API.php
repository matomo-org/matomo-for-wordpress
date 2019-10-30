<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\UsersManager;

use DeviceDetector\DeviceDetector;
use Exception;
use Piwik\Access;
use Piwik\Access\CapabilitiesProvider;
use Piwik\Access\RolesProvider;
use Piwik\Auth\Password;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\IP;
use Piwik\Mail;
use Piwik\Metrics\Formatter;
use Piwik\NoAccessException;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugin;
use Piwik\Plugins\Login\PasswordVerifier;
use Piwik\SettingsPiwik;
use Piwik\Site;
use Piwik\Tracker\Cache;
use Piwik\View;

/**
 * The UsersManager API lets you Manage Users and their permissions to access specific websites.
 *
 * You can create users via "addUser", update existing users via "updateUser" and delete users via "deleteUser".
 * There are many ways to list users based on their login "getUser" and "getUsers", their email "getUserByEmail",
 * or which users have permission (view or admin) to access the specified websites "getUsersWithSiteAccess".
 *
 * Existing Permissions are listed given a login via "getSitesAccessFromUser", or a website ID via "getUsersAccessFromSite",
 * or you can list all users and websites for a given permission via "getUsersSitesFromAccess". Permissions are set and updated
 * via the method "setUserAccess".
 * See also the documentation about <a href='http://matomo.org/docs/manage-users/' rel='noreferrer' target='_blank'>Managing Users</a> in Matomo.
 */
class API extends \Piwik\Plugin\API
{
    const OPTION_NAME_PREFERENCE_SEPARATOR = '_';

    public static $UPDATE_USER_REQUIRE_PASSWORD_CONFIRMATION = true;
    public static $SET_SUPERUSER_ACCESS_REQUIRE_PASSWORD_CONFIRMATION = true;

    /**
     * @var Model
     */
    private $model;

    /**
     * @var Password
     */
    private $password;

    /**
     * @var UserAccessFilter
     */
    private $userFilter;

    /**
     * @var Access
     */
    private $access;

    /**
     * @var Access\RolesProvider
     */
    private $roleProvider;

    /**
     * @var Access\CapabilitiesProvider
     */
    private $capabilityProvider;

    /**
     * @var PasswordVerifier
     */
    private $passwordVerifier;

    private $twoFaPluginActivated;

    const PREFERENCE_DEFAULT_REPORT = 'defaultReport';
    const PREFERENCE_DEFAULT_REPORT_DATE = 'defaultReportDate';

    private static $instance = null;

    public function __construct(Model $model, UserAccessFilter $filter, Password $password, Access $access = null, Access\RolesProvider $roleProvider = null, Access\CapabilitiesProvider $capabilityProvider = null, PasswordVerifier $passwordVerifier = null)
    {
        $this->model = $model;
        $this->userFilter = $filter;
        $this->password = $password;
        $this->access = $access ?: StaticContainer::get(Access::class);
        $this->roleProvider = $roleProvider ?: StaticContainer::get(RolesProvider::class);
        $this->capabilityProvider = $capabilityProvider ?: StaticContainer::get(CapabilitiesProvider::class);
        $this->passwordVerifier = $passwordVerifier ?: StaticContainer::get(PasswordVerifier::class);
    }

    /**
     * You can create your own Users Plugin to override this class.
     * Example of how you would overwrite the UsersManager_API with your own class:
     * Call the following in your plugin __construct() for example:
     *
     * StaticContainer::getContainer()->set('UsersManager_API', \Piwik\Plugins\MyCustomUsersManager\API::getInstance());
     *
     * @throws Exception
     * @return \Piwik\Plugins\UsersManager\API
     */
    public static function getInstance()
    {
        try {
            $instance = StaticContainer::get('UsersManager_API');
            if (!($instance instanceof API)) {
                // Exception is caught below and corrected
                throw new Exception('UsersManager_API must inherit API');
            }
            self::$instance = $instance;

        } catch (Exception $e) {
            self::$instance = StaticContainer::get('Piwik\Plugins\UsersManager\API');
            StaticContainer::getContainer()->set('UsersManager_API', self::$instance);
        }

        return self::$instance;
    }

    /**
     * Get the list of all available roles.
     * It does not return the super user role, and neither the "noaccess" role.
     * @return array[]  Returns an array containing information about each role
     */
    public function getAvailableRoles()
    {
        Piwik::checkUserHasSomeAdminAccess();

        $response = array();

        foreach ($this->roleProvider->getAllRoles() as $role) {
            $response[] = array(
                'id' => $role->getId(),
                'name' => $role->getName(),
                'description' => $role->getDescription(),
                'helpUrl' => $role->getHelpUrl(),
            );
        }

        return $response;
    }

    /**
     * Get the list of all available capabilities.
     * @return array[]  Returns an array containing information about each capability
     */
    public function getAvailableCapabilities()
    {
        Piwik::checkUserHasSomeAdminAccess();

        $response = array();

        foreach ($this->capabilityProvider->getAllCapabilities() as $capability) {
            $response[] = array(
                'id' => $capability->getId(),
                'name' => $capability->getName(),
                'description' => $capability->getDescription(),
                'helpUrl' => $capability->getHelpUrl(),
                'includedInRoles' => $capability->getIncludedInRoles(),
                'category' => $capability->getCategory(),
            );
        }

        return $response;
    }

    /**
     * Sets a user preference
     * @param string $userLogin
     * @param string $preferenceName
     * @param string $preferenceValue
     * @return void
     */
    public function setUserPreference($userLogin, $preferenceName, $preferenceValue)
    {
        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);
        Option::set($this->getPreferenceId($userLogin, $preferenceName), $preferenceValue);
    }

    /**
     * Gets a user preference
     * @param string $userLogin
     * @param string $preferenceName
     * @return bool|string
     */
    public function getUserPreference($userLogin, $preferenceName)
    {
        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);

        $optionValue = $this->getPreferenceValue($userLogin, $preferenceName);

        if ($optionValue !== false) {
            return $optionValue;
        }

        return $this->getDefaultUserPreference($preferenceName, $userLogin);
    }

    /**
     * Sets a user preference in the DB using the preference's default value.
     * @param string $userLogin
     * @param string $preferenceName
     * @ignore
     */
    public function initUserPreferenceWithDefault($userLogin, $preferenceName)
    {
        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);

        $optionValue = $this->getPreferenceValue($userLogin, $preferenceName);

        if ($optionValue === false) {
            $defaultValue = $this->getDefaultUserPreference($preferenceName, $userLogin);

            if ($defaultValue !== false) {
                $this->setUserPreference($userLogin, $preferenceName, $defaultValue);
            }
        }
    }

    /**
     * Returns an array of Preferences
     * @param $preferenceNames array of preference names
     * @return array
     * @ignore
     */
    public function getAllUsersPreferences(array $preferenceNames)
    {
        Piwik::checkUserHasSuperUserAccess();

        $userPreferences = array();
        foreach($preferenceNames as $preferenceName) {
            $optionNameMatchAllUsers = $this->getPreferenceId('%', $preferenceName);
            $preferences = Option::getLike($optionNameMatchAllUsers);

            foreach($preferences as $optionName => $optionValue) {
                $lastUnderscore = strrpos($optionName, self::OPTION_NAME_PREFERENCE_SEPARATOR);
                $userName = substr($optionName, 0, $lastUnderscore);
                $preference = substr($optionName, $lastUnderscore + 1);
                $userPreferences[$userName][$preference] = $optionValue;
            }
        }
        return $userPreferences;
    }

    private function getPreferenceId($login, $preference)
    {
        if(false !== strpos($preference, self::OPTION_NAME_PREFERENCE_SEPARATOR)) {
            throw new Exception("Preference name cannot contain underscores.");
        }
        return $login . self::OPTION_NAME_PREFERENCE_SEPARATOR . $preference;
    }

    private function getPreferenceValue($userLogin, $preferenceName)
    {
        return Option::get($this->getPreferenceId($userLogin, $preferenceName));
    }

    private function getDefaultUserPreference($preferenceName, $login)
    {
        switch ($preferenceName) {
            case self::PREFERENCE_DEFAULT_REPORT:
                $viewableSiteIds = \Piwik\Plugins\SitesManager\API::getInstance()->getSitesIdWithAtLeastViewAccess($login);
                if (!empty($viewableSiteIds)) {
                    return reset($viewableSiteIds);
                }
                return false;
            case self::PREFERENCE_DEFAULT_REPORT_DATE:
                return Config::getInstance()->General['default_day'];
            default:
                return false;
        }
    }

    /**
     * Returns all users with their role for $idSite.
     *
     * @param int $idSite
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $filter_search text to search for in the user's login, email and alias (if any)
     * @param string|null $filter_access only select users with this access to $idSite. can be 'noaccess', 'some', 'view', 'admin', 'superuser'
     *                                   Filtering by 'superuser' is only allowed for other superusers.
     * @return array
     */
    public function getUsersPlusRole($idSite, $limit = null, $offset = 0, $filter_search = null, $filter_access = null)
    {
        if (!$this->isUserHasAdminAccessTo($idSite)) {
            // if the user is not an admin to $idSite, they can only see their own user
            if ($offset > 1) {
                Common::sendHeader('X-Matomo-Total-Results: 1');
                return [];
            }

            $user = $this->model->getUser($this->access->getLogin());
            $user['role'] = $this->access->getRoleForSite($idSite);
            $user['capabilities'] = $this->access->getCapabilitiesForSite($idSite);
            $users = [$user];
            $totalResults = 1;
        } else {
            // if the current user is not the superuser, only select users that have access to a site this user
            // has admin access to
            $loginsToLimit = null;
            if (!Piwik::hasUserSuperUserAccess()) {
                $adminIdSites = Access::getInstance()->getSitesIdWithAdminAccess();
                if (empty($adminIdSites)) { // sanity check
                    throw new \Exception("The current admin user does not have access to any sites.");
                }

                $loginsToLimit = $this->model->getUsersWithAccessToSites($adminIdSites);
            }

            if ($loginsToLimit !== null && empty($loginsToLimit)) {
                // if the current user is not the superuser, and getUsersWithAccessToSites() returned an empty result,
                // access is managed by another plugin, and the current user cannot manage any user with UsersManager
                Common::sendHeader('X-Matomo-Total-Results: 0');
                return [];

            } else {
                list($users, $totalResults) = $this->model->getUsersWithRole($idSite, $limit, $offset, $filter_search, $filter_access, $loginsToLimit);

                foreach ($users as &$user) {
                    $user['superuser_access'] = $user['superuser_access'] == 1;
                    if ($user['superuser_access']) {
                        $user['role'] = 'superuser';
                        $user['capabilities'] = [];
                    } else {
                        list($user['role'], $user['capabilities']) = $this->getRoleAndCapabilitiesFromAccess($user['access']);
                        $user['role'] = empty($user['role']) ? 'noaccess' : reset($user['role']);
                    }

                    unset($user['access']);
                }
            }
        }

        $users = $this->enrichUsers($users);
        $users = $this->enrichUsersWithLastSeen($users);

        foreach ($users as &$user) {
            unset($user['password']);
        }

        Common::sendHeader('X-Matomo-Total-Results: ' . $totalResults);
        return $users;
    }

    /**
     * Returns the list of all the users
     *
     * @param string $userLogins Comma separated list of users to select. If not specified, will return all users
     * @return array the list of all the users
     */
    public function getUsers($userLogins = '')
    {
        Piwik::checkUserHasSomeAdminAccess();

        if (!is_string($userLogins)) {
            throw new \Exception('Parameter userLogins needs to be a string containing a comma separated list of users');
        }

        $logins = array();

        if (!empty($userLogins)) {
            $logins = explode(',', $userLogins);
        }

        $users = $this->model->getUsers($logins);
        $users = $this->userFilter->filterUsers($users);
        $users = $this->enrichUsers($users);

        return $users;
    }

    /**
     * Returns the list of all the users login
     *
     * @return array the list of all the users login
     */
    public function getUsersLogin()
    {
        Piwik::checkUserHasSomeAdminAccess();

        $logins = $this->model->getUsersLogin();
        $logins = $this->userFilter->filterLogins($logins);

        return $logins;
    }

    /**
     * For each user, returns the list of website IDs where the user has the supplied $access level.
     * If a user doesn't have the given $access to any website IDs,
     * the user will not be in the returned array.
     *
     * @param string Access can have the following values : 'view' or 'admin'
     *
     * @return array    The returned array has the format
     *                    array(
     *                        login1 => array ( idsite1,idsite2),
     *                        login2 => array(idsite2),
     *                        ...
     *                    )
     */
    public function getUsersSitesFromAccess($access)
    {
        Piwik::checkUserHasSuperUserAccess();

        $this->checkAccessType($access);

        $userSites = $this->model->getUsersSitesFromAccess($access);
        $userSites = $this->userFilter->filterLoginIndexedArray($userSites);

        return $userSites;
    }

    private function checkAccessType($access)
    {
        $access = (array) $access;

        $roles = $this->roleProvider->getAllRoleIds();
        $capabilities = $this->capabilityProvider->getAllCapabilityIds();
        $list = array_merge($roles, $capabilities);

        foreach ($access as $entry) {
            if (!in_array($entry, $list, true)) {
                throw new Exception(Piwik::translate("UsersManager_ExceptionAccessValues", implode(", ", $list), $entry));
            }
        }
    }

    /**
     * For each user, returns their access level for the given $idSite.
     * If a user doesn't have any access to the $idSite ('noaccess'),
     * the user will not be in the returned array.
     *
     * @param int $idSite website ID
     *
     * @return array    The returned array has the format
     *                    array(
     *                        login1 => 'view',
     *                        login2 => 'admin',
     *                        login3 => 'view',
     *                        ...
     *                    )
     */
    public function getUsersAccessFromSite($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $usersAccess = $this->model->getUsersAccessFromSite($idSite);
        $usersAccess = $this->userFilter->filterLoginIndexedArray($usersAccess);

        return $usersAccess;
    }

    public function getUsersWithSiteAccess($idSite, $access)
    {
        Piwik::checkUserHasAdminAccess($idSite);
        $this->checkAccessType($access);

        $logins = $this->model->getUsersLoginWithSiteAccess($idSite, $access);

        if (empty($logins)) {
            return array();
        }

        $logins = $this->userFilter->filterLogins($logins);
        $logins = implode(',', $logins);

        return $this->getUsers($logins);
    }

    /**
     * For each website ID, returns the access level of the given $userLogin.
     * If the user doesn't have any access to a website ('noaccess'),
     * this website will not be in the returned array.
     * If the user doesn't have any access, the returned array will be an empty array.
     *
     * @param string $userLogin User that has to be valid
     *
     * @return array    The returned array has the format
     *                    array(
     *                        idsite1 => 'view',
     *                        idsite2 => 'admin',
     *                        idsite3 => 'view',
     *                        ...
     *                    )
     */
    public function getSitesAccessFromUser($userLogin)
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkUserExists($userLogin);
        // Super users have 'admin' access for every site
        if (Piwik::hasTheUserSuperUserAccess($userLogin)) {
            $return = array();
            $siteManagerModel = new \Piwik\Plugins\SitesManager\Model();
            $sites = $siteManagerModel->getAllSites();
            foreach ($sites as $site) {
                $return[] = array(
                    'site' => $site['idsite'],
                    'access' => 'admin'
                );
            }
            return $return;
        }
        return $this->model->getSitesAccessFromUser($userLogin);
    }

    /**
     * For each website ID, returns the access level of the given $userLogin (if the user is not a superuser).
     * If the user doesn't have any access to a website ('noaccess'),
     * this website will not be in the returned array.
     * If the user doesn't have any access, the returned array will be an empty array.
     *
     * @param string $userLogin User that has to be valid
     *
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $filter_search text to search for in site name, URLs, or group.
     * @param string|null $filter_access access level to select for, can be 'some', 'view' or 'admin' (by default 'some')
     * @return array    The returned array has the format
     *                    array(
     *                        ['idsite' => 1, 'site_name' => 'the site', 'access' => 'admin'],
     *                        ['idsite' => 2, 'site_name' => 'the other site', 'access' => 'view'],
     *                        ...
     *                    )
     * @throws Exception
     */
    public function getSitesAccessForUser($userLogin, $limit = null, $offset = 0, $filter_search = null, $filter_access = null)
    {
        Piwik::checkUserHasSomeAdminAccess();
        $this->checkUserExists($userLogin);

        if (Piwik::hasTheUserSuperUserAccess($userLogin)) {
            throw new \Exception("This method should not be used with superusers.");
        }

        $idSites = null;
        if (!Piwik::hasUserSuperUserAccess()) {
            $idSites = $this->access->getSitesIdWithAdminAccess();
            if (empty($idSites)) { // sanity check
                throw new \Exception("The current admin user does not have access to any sites.");
            }
        }

        list($sites, $totalResults) = $this->model->getSitesAccessFromUserWithFilters($userLogin, $limit, $offset, $filter_search, $filter_access, $idSites);
        foreach ($sites as &$siteAccess) {
            list($siteAccess['role'], $siteAccess['capabilities']) = $this->getRoleAndCapabilitiesFromAccess($siteAccess['access']);
            $siteAccess['role'] = empty($siteAccess['role']) ? 'noaccess' : reset($siteAccess['role']);
            unset($siteAccess['access']);
        }

        $hasAccessToAny = $this->model->getSiteAccessCount($userLogin) > 0;

        Common::sendHeader('X-Matomo-Total-Results: ' . $totalResults);
        if ($hasAccessToAny) {
            Common::sendHeader('X-Matomo-Has-Some: 1');
        }
        return $sites;
    }

    /**
     * Returns the user information (login, password hash, alias, email, date_registered, etc.)
     *
     * @param string $userLogin the user login
     *
     * @return array the user information
     */
    public function getUser($userLogin)
    {
        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);
        $this->checkUserExists($userLogin);

        $user = $this->model->getUser($userLogin);

        $user = $this->userFilter->filterUser($user);
        $user = $this->enrichUser($user);

        return $user;
    }

    /**
     * Returns the user information (login, password hash, alias, email, date_registered, etc.)
     *
     * @param string $userEmail the user email
     *
     * @return array the user information
     */
    public function getUserByEmail($userEmail)
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkUserEmailExists($userEmail);

        $user = $this->model->getUserByEmail($userEmail);

        $user = $this->userFilter->filterUser($user);
        $user = $this->enrichUser($user);

        return $user;
    }

    private function checkLogin($userLogin)
    {
        if ($this->userExists($userLogin)) {
            throw new Exception(Piwik::translate('UsersManager_ExceptionLoginExists', $userLogin));
        }

        Piwik::checkValidLoginString($userLogin);
    }

    private function checkEmail($email)
    {
        if ($this->userEmailExists($email)) {
            throw new Exception(Piwik::translate('UsersManager_ExceptionEmailExists', $email));
        }

        if (!Piwik::isValidEmailString($email)) {
            throw new Exception(Piwik::translate('UsersManager_ExceptionInvalidEmail'));
        }
    }

    private function getCleanAlias($alias, $userLogin)
    {
        if (empty($alias)) {
            $alias = $userLogin;
        }

        return $alias;
    }

    /**
     * Add a user in the database.
     * A user is defined by
     * - a login that has to be unique and valid
     * - a password that has to be valid
     * - an alias
     * - an email that has to be in a correct format
     *
     * @see userExists()
     * @see isValidLoginString()
     * @see isValidPasswordString()
     * @see isValidEmailString()
     *
     * @exception in case of an invalid parameter
     */
    public function addUser($userLogin, $password, $email, $alias = false, $_isPasswordHashed = false, $initialIdSite = null)
    {
        Piwik::checkUserHasSomeAdminAccess();
        UsersManager::dieIfUsersAdminIsDisabled();

        if (!Piwik::hasUserSuperUserAccess()) {
            if (empty($initialIdSite)) {
                throw new \Exception(Piwik::translate("UsersManager_AddUserNoInitialAccessError"));
            }

            Piwik::checkUserHasAdminAccess($initialIdSite);
        }

        $this->checkLogin($userLogin);
        $this->checkEmail($email);

        $password = Common::unsanitizeInputValue($password);

        if (!$_isPasswordHashed) {
            UsersManager::checkPassword($password);

            $passwordTransformed = UsersManager::getPasswordHash($password);
        } else {
            $passwordTransformed = $password;
        }

        $alias               = $this->getCleanAlias($alias, $userLogin);
        $passwordTransformed = $this->password->hash($passwordTransformed);
        $token_auth          = $this->createTokenAuth($userLogin);

        $this->model->addUser($userLogin, $passwordTransformed, $email, $alias, $token_auth, Date::now()->getDatetime());

        // we reload the access list which doesn't yet take in consideration this new user
        Access::getInstance()->reloadAccess();
        Cache::deleteTrackerCache();

        /**
         * Triggered after a new user is created.
         *
         * @param string $userLogin The new user's login handle.
         */
        Piwik::postEvent('UsersManager.addUser.end', array($userLogin, $email, $password, $alias));

        if ($initialIdSite) {
            $this->setUserAccess($userLogin, 'view', $initialIdSite);
        }
    }

    /**
     * Enable or disable Super user access to the given user login. Note: When granting Super User access all previous
     * permissions of the user will be removed as the user gains access to everything.
     *
     * @param string   $userLogin          the user login.
     * @param bool|int $hasSuperUserAccess true or '1' to grant Super User access, false or '0' to remove Super User
     *                                     access.
     * @param string $passwordConfirmation the current user's password.
     * @throws \Exception
     */
    public function setSuperUserAccess($userLogin, $hasSuperUserAccess, $passwordConfirmation = null)
    {
        Piwik::checkUserHasSuperUserAccess();
        $this->checkUserIsNotAnonymous($userLogin);
        UsersManager::dieIfUsersAdminIsDisabled();

        $requirePasswordConfirmation = self::$SET_SUPERUSER_ACCESS_REQUIRE_PASSWORD_CONFIRMATION;
        self::$SET_SUPERUSER_ACCESS_REQUIRE_PASSWORD_CONFIRMATION = true;

        $isCliMode = Common::isPhpCliMode() && !(defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE);
        if (!$isCliMode
            && $requirePasswordConfirmation
        ) {
            $this->confirmCurrentUserPassword($passwordConfirmation);
        }
        $this->checkUserExists($userLogin);

        if (!$hasSuperUserAccess && $this->isUserTheOnlyUserHavingSuperUserAccess($userLogin)) {
            $message = Piwik::translate("UsersManager_ExceptionRemoveSuperUserAccessOnlySuperUser", $userLogin)
                        . " "
                        . Piwik::translate("UsersManager_ExceptionYouMustGrantSuperUserAccessFirst");
            throw new Exception($message);
        }

        $this->model->deleteUserAccess($userLogin);
        $this->model->setSuperUserAccess($userLogin, $hasSuperUserAccess);

        Cache::deleteTrackerCache();
    }

    /**
     * Detect whether the current user has super user access or not.
     *
     * @return bool
     */
    public function hasSuperUserAccess()
    {
        return Piwik::hasUserSuperUserAccess();
    }

    /**
     * Returns a list of all Super Users containing there userLogin and email address.
     *
     * @return array
     */
    public function getUsersHavingSuperUserAccess()
    {
        Piwik::checkUserIsNotAnonymous();

        $users = $this->model->getUsersHavingSuperUserAccess();
        $users = $this->enrichUsers($users);

        // we do not filter these users by access and return them all since we need to print this information in the
        // UI and they are allowed to see this.

        return $users;
    }

    private function enrichUsersWithLastSeen($users)
    {
        $formatter = new Formatter();

        $lastSeenTimes = LastSeenTimeLogger::getLastSeenTimesForAllUsers();
        foreach ($users as &$user) {
            $login = $user['login'];
            if (isset($lastSeenTimes[$login])) {
                $user['last_seen'] = $formatter->getPrettyTimeFromSeconds(time() - $lastSeenTimes[$login]);
            }
        }
        return $users;
    }

    private function enrichUsers($users)
    {
        if (!empty($users)) {
            foreach ($users as $index => $user) {
                $users[$index] = $this->enrichUser($user);
            }
        }
        return $users;
    }

    private function isTwoFactorAuthPluginEnabled()
    {
        if (!isset($this->twoFaPluginActivated)) {
            $this->twoFaPluginActivated = Plugin\Manager::getInstance()->isPluginActivated('TwoFactorAuth');
        }
        return $this->twoFaPluginActivated;
    }

    private function enrichUser($user)
    {
        if (empty($user)) {
            return $user;
        }

        unset($user['token_auth']);
        unset($user['password']);
        unset($user['ts_password_modified']);

        if (Piwik::hasUserSuperUserAccess()) {
            $user['uses_2fa'] = !empty($user['twofactor_secret']) && $this->isTwoFactorAuthPluginEnabled();
            unset($user['twofactor_secret']);
            return $user;
        }

        $newUser = array('login' => $user['login']);
        if (isset($user['alias'])) {
            $newUser['alias'] = $user['alias'];
        }

        if ($user['login'] === Piwik::getCurrentUserLogin() || !empty($user['superuser_access'])) {
            $newUser['email'] = $user['email'];
        }

        if (isset($user['role'])) {
            $newUser['role'] = $user['role'] == 'superuser' ? 'admin' : $user['role'];
        }
        if (isset($user['capabilities'])) {
            $newUser['capabilities'] = $user['capabilities'];
        }

        if (isset($user['superuser_access'])) {
            $newUser['superuser_access'] = $user['superuser_access'];
        }

        return $newUser;
    }

    /**
     * Regenerate the token_auth associated with a user.
     *
     * If the user currently logged in regenerates his own token, he will be logged out.
     * His previous token will be rendered invalid.
     *
     * @param   string  $userLogin
     * @throws  Exception
     */
    public function regenerateTokenAuth($userLogin)
    {
        $this->checkUserIsNotAnonymous($userLogin);

        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);

        $this->model->updateUserTokenAuth(
            $userLogin,
            $this->createTokenAuth($userLogin)
        );

        Cache::deleteTrackerCache();
    }

    /**
     * Updates a user in the database.
     * Only login and password are required (case when we update the password).
     *
     * If password or email changes, it is required to also specify the password of the current user needs to be specified
     * to confirm this change.
     *
     * @see addUser() for all the parameters
     */
    public function updateUser($userLogin, $password = false, $email = false, $alias = false,
                               $_isPasswordHashed = false, $passwordConfirmation = false)
    {
        $requirePasswordConfirmation = self::$UPDATE_USER_REQUIRE_PASSWORD_CONFIRMATION;
        self::$UPDATE_USER_REQUIRE_PASSWORD_CONFIRMATION = true;

        $isEmailNotificationOnInConfig = Config::getInstance()->General['enable_update_users_email'];

        Piwik::checkUserHasSuperUserAccessOrIsTheUser($userLogin);
        UsersManager::dieIfUsersAdminIsDisabled();
        $this->checkUserIsNotAnonymous($userLogin);
        $this->checkUserExists($userLogin);

        $userInfo   = $this->model->getUser($userLogin);
        $token_auth = $userInfo['token_auth'];
        $changeShouldRequirePasswordConfirmation = false;

        $passwordHasBeenUpdated = false;

        if (empty($password)) {
            $password = false;
        } else {
            $changeShouldRequirePasswordConfirmation = true;
            $password = Common::unsanitizeInputValue($password);

            if (!$_isPasswordHashed) {
                UsersManager::checkPassword($password);
                $password = UsersManager::getPasswordHash($password);
            }

            $passwordInfo = $this->password->info($password);

            if (!isset($passwordInfo['algo']) || 0 >= $passwordInfo['algo']) {
                // password may have already been fully hashed
                $password = $this->password->hash($password);
            }

            $passwordHasBeenUpdated = true;
        }

        if (empty($alias)) {
            $alias = $userInfo['alias'];
        }

        if (empty($email)) {
            $email = $userInfo['email'];
        }

        $hasEmailChanged = Common::mb_strtolower($email) !== Common::mb_strtolower($userInfo['email']);

        if ($hasEmailChanged) {
            $this->checkEmail($email);
            $changeShouldRequirePasswordConfirmation = true;
        }

        if ($changeShouldRequirePasswordConfirmation && $requirePasswordConfirmation) {
            $this->confirmCurrentUserPassword($passwordConfirmation);
        }

        $alias = $this->getCleanAlias($alias, $userLogin);

        $this->model->updateUser($userLogin, $password, $email, $alias, $token_auth);

        Cache::deleteTrackerCache();

        if ($hasEmailChanged && $isEmailNotificationOnInConfig) {
            $this->sendEmailChangedEmail($userInfo, $email);
        }

        if ($passwordHasBeenUpdated && $requirePasswordConfirmation && $isEmailNotificationOnInConfig) {
            $this->sendPasswordChangedEmail($userInfo);
        }

        /**
         * Triggered after an existing user has been updated.
         * Event notify about password change.
         *
         * @param string $userLogin The user's login handle.
         * @param boolean $passwordHasBeenUpdated Flag containing information about password change.
         */
        Piwik::postEvent('UsersManager.updateUser.end', array($userLogin, $passwordHasBeenUpdated, $email, $password, $alias));
    }

    /**
     * Delete one or more users and all its access, given its login.
     *
     * @param string $userLogin the user login(s).
     *
     * @throws Exception if the user doesn't exist or if deleting the users would leave no superusers.
     *
     * @return bool true on success
     */
    public function deleteUser($userLogin)
    {
        Piwik::checkUserHasSuperUserAccess();
        UsersManager::dieIfUsersAdminIsDisabled();
        $this->checkUserIsNotAnonymous($userLogin);

        $this->checkUserExist($userLogin);

        if ($this->isUserTheOnlyUserHavingSuperUserAccess($userLogin)) {
            $message = Piwik::translate("UsersManager_ExceptionDeleteOnlyUserWithSuperUserAccess", $userLogin)
                        . " "
                        . Piwik::translate("UsersManager_ExceptionYouMustGrantSuperUserAccessFirst");
            throw new Exception($message);
        }

        $this->model->deleteUserOnly($userLogin);
        $this->model->deleteUserOptions($userLogin);
        $this->model->deleteUserAccess($userLogin);

        Cache::deleteTrackerCache();
    }

    /**
     * Returns true if the given userLogin is known in the database
     *
     * @param string $userLogin
     * @return bool true if the user is known
     */
    public function userExists($userLogin)
    {
        if ($userLogin == 'anonymous') {
            return true;
        }

        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasSomeViewAccess();

        if ($userLogin == Piwik::getCurrentUserLogin()) {
            return true;
        }

        return $this->model->userExists($userLogin);
    }

    /**
     * Returns true if user with given email (userEmail) is known in the database, or the Super User
     *
     * @param string $userEmail
     * @return bool true if the user is known
     */
    public function userEmailExists($userEmail)
    {
        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasSomeViewAccess();

        return $this->model->userEmailExists($userEmail);
    }

    /**
     * Returns the first login name of an existing user that has the given email address. If no user can be found for
     * this user an error will be returned.
     *
     * @param string $userEmail
     * @return bool true if the user is known
     */
    public function getUserLoginFromUserEmail($userEmail)
    {
        Piwik::checkUserIsNotAnonymous();
        Piwik::checkUserHasSomeAdminAccess();

        $this->checkUserEmailExists($userEmail);

        $user = $this->model->getUserByEmail($userEmail);

        // any user with some admin access is allowed to find any user by email, no need to filter by access here

        return $user['login'];
    }

    /**
     * Set an access level to a given user for a list of websites ID.
     *
     * If access = 'noaccess' the current access (if any) will be deleted.
     * If access = 'view' or 'admin' the current access level is deleted and updated with the new value.
     *
     * @param string $userLogin The user login
     * @param string|array $access Access to grant. Must have one of the following value : noaccess, view, write, admin.
     *                              May also be an array to sent additional capabilities
     * @param int|array $idSites The array of idSites on which to apply the access level for the user.
     *       If the value is "all" then we apply the access level to all the websites ID for which the current authentificated user has an 'admin' access.
     * @throws Exception if the user doesn't exist
     * @throws Exception if the access parameter doesn't have a correct value
     * @throws Exception if any of the given website ID doesn't exist
     */
    public function setUserAccess($userLogin, $access, $idSites)
    {
        UsersManager::dieIfUsersAdminIsDisabled();

        if ($access != 'noaccess') {
            $this->checkAccessType($access);
        }

        $idSites = $this->getIdSitesCheckAdminAccess($idSites);

        if ($userLogin === 'anonymous' &&
            (is_array($access) || !in_array($access, array('view', 'noaccess'), true))
        ) {
            throw new Exception(Piwik::translate("UsersManager_ExceptionAnonymousAccessNotPossible", array('noaccess', 'view')));
        }

        $roles = array();
        $capabilities = array();

        if (is_array($access)) {
            // we require one role, and optionally multiple capabilties
            list($roles, $capabilities) = $this->getRoleAndCapabilitiesFromAccess($access);

            if (count($roles) < 1) {
                $ids = implode(', ', $this->roleProvider->getAllRoleIds());
                throw new Exception(Piwik::translate('UsersManager_ExceptionNoRoleSet', $ids));
            }

            if (count($roles) > 1) {
                $ids = implode(', ', $this->roleProvider->getAllRoleIds());
                throw new Exception(Piwik::translate('UsersManager_ExceptionMultipleRoleSet', $ids));
            }

        } else {
            // as only one access is set, we require it to be a role or "noaccess"...
            if ($access !== 'noaccess') {
                $this->roleProvider->checkValidRole($access);
                $roles[] = $access;
            }
        }

        $this->checkUserExist($userLogin);
        $this->checkUsersHasNotSuperUserAccess($userLogin);

        $this->model->deleteUserAccess($userLogin, $idSites);

        if ($access === 'noaccess') {
            // if the access is noaccess then we don't save it as this is the default value
            // when no access are specified
            Piwik::postEvent('UsersManager.removeSiteAccess', array($userLogin, $idSites));
        } else {
            $role = array_shift($roles);
            $this->model->addUserAccess($userLogin, $role, $idSites);
        }

        if (!empty($capabilities)) {
            $this->addCapabilities($userLogin, $capabilities, $idSites);
        }

        // we reload the access list which doesn't yet take in consideration this new user access
        $this->reloadPermissions();
    }

    /**
     * Adds the given capabilities to the given user for the given sites.
     * The capability will be added only when the user also has access to a site, for example View, Write, or Admin.
     * Note: You can neither add any capability to a super user, nor to the anonymous user.
     * Note: If the user has assigned a role which already grants the given capability, the capability will not be added in
     * the backend.
     *
     * @param string $userLogin The user login
     * @param string|string[] $capabilities  To fetch a list of available capabilities call "UsersManager.getAvailableCapabilities".
     * @param int|int[] $idSites
     * @throws Exception
     */
    public function addCapabilities($userLogin, $capabilities, $idSites)
    {
        $idSites = $this->getIdSitesCheckAdminAccess($idSites);

        if ($userLogin == 'anonymous') {
            throw new Exception(Piwik::translate("UsersManager_ExceptionAnonymousNoCapabilities"));
        }

        $this->checkUserExists($userLogin);
        $this->checkUsersHasNotSuperUserAccess([$userLogin]);

        if (!is_array($capabilities)){
            $capabilities = array($capabilities);
        }

        foreach ($capabilities as $entry) {
            $this->capabilityProvider->checkValidCapability($entry);
        }

        list($sitesIdWithRole, $sitesIdWithCapability) = $this->getRolesAndCapabilitiesForLogin($userLogin);

        foreach ($capabilities as $entry) {
            $cap = $this->capabilityProvider->getCapability($entry);

            foreach ($idSites as $idSite) {
                $hasRole = array_key_exists($idSite, $sitesIdWithRole);
                $hasCapabilityAlready = array_key_exists($idSite, $sitesIdWithCapability) && in_array($entry, $sitesIdWithCapability[$idSite], true);

                // so far we are adding the capability only to people that also have a role...
                // to be defined how to handle this... eg we are not throwing an exception currently
                // as it might be used as part of bulk action etc.
                if ($hasRole && !$hasCapabilityAlready) {
                    $theRole = $sitesIdWithRole[$idSite];
                    if ($cap->hasRoleCapability($theRole)) {
                        // todo this behaviour needs to be defined...
                        // when the role already supports this capability we do not add it again
                        continue;
                    }

                    $this->model->addUserAccess($userLogin, $entry, array($idSite));
                }
            }

        }

        // we reload the access list which doesn't yet take in consideration this new user access
        $this->reloadPermissions();
    }

    private function getRolesAndCapabilitiesForLogin($userLogin)
    {
        $sites = $this->model->getSitesAccessFromUser($userLogin);
        $roleIds = $this->roleProvider->getAllRoleIds();

        $sitesIdWithRole = array();
        $sitesIdWithCapability = array();
        foreach ($sites as $site) {
            if (in_array($site['access'], $roleIds, true)) {
                $sitesIdWithRole[(int) $site['site']] = $site['access'];
            } else {
                if (!isset($sitesIdWithCapability[(int) $site['site']])) {
                    $sitesIdWithCapability[(int) $site['site']] = array();
                }
                $sitesIdWithCapability[(int) $site['site']][] = $site['access'];
            }
        }
        return [$sitesIdWithRole, $sitesIdWithCapability];
    }

    /**
     * Removes the given capabilities from the given user for the given sites.
     * The capability will be only removed if it is actually granted as a separate capability. If the user has a role
     * that includes a specific capability, for example "Admin", then the capability will not be removed because the
     * assigned role will always include this capability.
     *
     * @param string $userLogin The user login
     * @param string|string[] $capabilities  To fetch a list of available capabilities call "UsersManager.getAvailableCapabilities".
     * @param int|int[] $idSites
     * @throws Exception
     */
    public function removeCapabilities($userLogin, $capabilities, $idSites)
    {
        $idSites = $this->getIdSitesCheckAdminAccess($idSites);

        $this->checkUserExists($userLogin);

        if (!is_array($capabilities)){
            $capabilities = array($capabilities);
        }

        foreach ($capabilities as $capability) {
            $this->capabilityProvider->checkValidCapability($capability);
        }

        foreach ($capabilities as $capability) {
            $this->model->removeUserAccess($userLogin, $capability, $idSites);
        }

        // we reload the access list which doesn't yet take in consideration this removed capability
        $this->reloadPermissions();
    }

    private function reloadPermissions()
    {
        Access::getInstance()->reloadAccess();
        Cache::deleteTrackerCache();
    }

    private function getIdSitesCheckAdminAccess($idSites)
    {
        // in case idSites is all we grant access to all the websites on which the current connected user has an 'admin' access
        if ($idSites === 'all') {
            $idSites = \Piwik\Plugins\SitesManager\API::getInstance()->getSitesIdWithAdminAccess();
        } // in case the idSites is an integer we build an array
        else {
            $idSites = Site::getIdSitesFromIdSitesString($idSites);
        }

        if (empty($idSites)) {
            throw new Exception('Specify at least one website ID in &idSites=');
        }

        // it is possible to set user access on websites only for the websites admin
        // basically an admin can give the view or the admin access to any user for the websites they manage
        Piwik::checkUserHasAdminAccess($idSites);

        if (!is_array($idSites)) {
            $idSites = array($idSites);
        }

        return $idSites;
    }

    /**
     * Throws an exception is the user login doesn't exist
     *
     * @param string $userLogin user login
     * @throws Exception if the user doesn't exist
     */
    private function checkUserExists($userLogin)
    {
        if (!$this->userExists($userLogin)) {
            throw new Exception(Piwik::translate("UsersManager_ExceptionUserDoesNotExist", $userLogin));
        }
    }

    /**
     * Throws an exception is the user email cannot be found
     *
     * @param string $userEmail user email
     * @throws Exception if the user doesn't exist
     */
    private function checkUserEmailExists($userEmail)
    {
        if (!$this->userEmailExists($userEmail)) {
            throw new Exception(Piwik::translate("UsersManager_ExceptionUserDoesNotExist", $userEmail));
        }
    }

    private function checkUserIsNotAnonymous($userLogin)
    {
        if ($userLogin == 'anonymous') {
            throw new Exception(Piwik::translate("UsersManager_ExceptionEditAnonymous"));
        }
    }

    private function checkUsersHasNotSuperUserAccess($userLogins)
    {
        $userLogins = (array) $userLogins;
        $superusers = $this->getUsersHavingSuperUserAccess();
        $superusers = array_column($superusers, null, 'login');

        foreach ($userLogins as $userLogin) {
            if (isset($superusers[$userLogin])) {
                throw new Exception(Piwik::translate("UsersManager_ExceptionUserHasSuperUserAccess", $userLogin));
            }
        }
    }

    /**
     * @param string|string[] $userLogin
     * @return bool
     */
    private function isUserTheOnlyUserHavingSuperUserAccess($userLogin)
    {
        if (!is_array($userLogin)) {
            $userLogin = [$userLogin];
        }

        $superusers = $this->getUsersHavingSuperUserAccess();
        $superusersByLogin = array_column($superusers, null, 'login');

        foreach ($userLogin as $login) {
            unset($superusersByLogin[$login]);
        }

        return empty($superusersByLogin);
    }

    /**
     * Generates a new random authentication token.
     *
     * @param string $userLogin Login
     * @return string
     */
    public function createTokenAuth($userLogin)
    {
        return md5($userLogin . microtime(true) . Common::generateUniqId() . SettingsPiwik::getSalt());
    }

    /**
     * Returns the user's API token.
     *
     * If the username/password combination is incorrect an invalid token will be returned.
     *
     * @param string $userLogin Login
     * @param string $md5Password hashed string of the password (using current hash function; MD5-named for historical reasons)
     * @return string
     */
    public function getTokenAuth($userLogin, $md5Password)
    {
        UsersManager::checkPasswordHash($md5Password, Piwik::translate('UsersManager_ExceptionPasswordMD5HashExpected'));

        $user = $this->model->getUser($userLogin);

        if (empty($user) || !$this->password->verify($md5Password, $user['password'])) {
            /**
             * @ignore
             * @internal
             */
            Piwik::postEvent('Login.authenticate.failed', array($userLogin));

            return md5($userLogin . microtime(true) . Common::generateUniqId());
        }

        if ($this->password->needsRehash($user['password'])) {
            $userUpdater = new UserUpdater();
            $userUpdater->updateUserWithoutCurrentPassword($userLogin, $this->password->hash($md5Password));
        }

        return $user['token_auth'];
    }

    public function newsletterSignup()
    {
        Piwik::checkUserIsNotAnonymous();

        $userLogin = Piwik::getCurrentUserLogin();
        $email = Piwik::getCurrentUserEmail();

        $success = NewsletterSignup::signupForNewsletter($userLogin, $email, true);
        $result = $success ? array('success' => true) : array('error' => true);
        return $result;
    }

    private function isUserHasAdminAccessTo($idSite)
    {
        try {
            Piwik::checkUserHasAdminAccess([$idSite]);
            return true;
        } catch (NoAccessException $ex) {
            return false;
        }
    }

    private function checkUserExist($userLogin)
    {
        $userExists = $this->model->userExists($userLogin);
        if (!$userExists) {
            throw new Exception(Piwik::translate("UsersManager_ExceptionUserDoesNotExist", $userLogin));
        }
    }

    private function getRoleAndCapabilitiesFromAccess($access)
    {
        $roles = [];
        $capabilities = [];

        foreach ($access as $entry) {
            if (empty($entry)) {
                continue;
            }

            if ($this->roleProvider->isValidRole($entry)) {
                $roles[] = $entry;
            } else {
                $this->checkAccessType($entry);
                $capabilities[] = $entry;
            }
        }
        return [$roles, $capabilities];
    }

    private function confirmCurrentUserPassword($passwordConfirmation)
    {
        if (empty($passwordConfirmation)) {
            throw new Exception(Piwik::translate('UsersManager_ConfirmWithPassword'));
        }

        $passwordConfirmation = Common::unsanitizeInputValue($passwordConfirmation);

        $loginCurrentUser = Piwik::getCurrentUserLogin();
        if (!$this->passwordVerifier->isPasswordCorrect($loginCurrentUser, $passwordConfirmation)) {
            throw new Exception(Piwik::translate('UsersManager_CurrentPasswordNotCorrect'));
        }
    }

    private function sendEmailChangedEmail($user, $newEmail)
    {
        // send the mail to both the old email and the new email
        foreach ([$newEmail, $user['email']] as $emailTo) {
            $this->sendUserInfoChangedEmail('email', $user, $newEmail, $emailTo, 'UsersManager_EmailChangeNotificationSubject');
        }
    }

    private function sendUserInfoChangedEmail($type, $user, $newValue, $emailTo, $subject)
    {
        $deviceDescription = $this->getDeviceDescription();

        $view = new View('@UsersManager/_userInfoChangedEmail.twig');
        $view->type = $type;
        $view->accountName = Common::sanitizeInputValue($user['login']);
        $view->newEmail = Common::sanitizeInputValue($newValue);
        $view->ipAddress = IP::getIpFromHeader();
        $view->deviceDescription = $deviceDescription;

        $mail = new Mail();

        $mail->addTo($emailTo, $user['login']);
        $mail->setSubject(Piwik::translate($subject));
        $mail->setDefaultFromPiwik();
        $mail->setWrappedHtmlBody($view);

        $replytoEmailName = Config::getInstance()->General['login_password_recovery_replyto_email_name'];
        $replytoEmailAddress = Config::getInstance()->General['login_password_recovery_replyto_email_address'];
        $mail->setReplyTo($replytoEmailAddress, $replytoEmailName);

        $mail->send();
    }

    private function sendPasswordChangedEmail($user)
    {
        $this->sendUserInfoChangedEmail('password', $user, null, $user['email'], 'UsersManager_PasswordChangeNotificationSubject');
    }

    private function getDeviceDescription()
    {
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $uaParser = new DeviceDetector($userAgent);
        $uaParser->parse();

        $deviceName = ucfirst($uaParser->getDeviceName());
        if (!empty($deviceName)) {
            $description = $deviceName;
        } else {
            $description = Piwik::translate('General_Unknown');
        }

        $deviceBrand = $uaParser->getBrandName();
        $deviceModel = $uaParser->getModel();
        if (!empty($deviceBrand)
            || !empty($deviceModel)
        ) {
            $parts = array_filter([$deviceBrand, $deviceModel]);
            $description .= ' (' . implode(' ', $parts) . ')';
        }

        return $description;
    }
}
