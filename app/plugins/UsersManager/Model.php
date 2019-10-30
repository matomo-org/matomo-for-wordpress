<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\UsersManager;

use Piwik\Auth\Password;
use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\Option;
use Piwik\Piwik;
use Piwik\Plugins\SitesManager\SitesManager;
use Piwik\Plugins\UsersManager\Sql\SiteAccessFilter;
use Piwik\Plugins\UsersManager\Sql\UserTableFilter;

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
 * See also the documentation about <a href='http://piwik.org/docs/manage-users/' rel='noreferrer' target='_blank'>Managing Users</a> in Piwik.
 */
class Model
{
    private static $rawPrefix = 'user';
    private $table;

    /**
     * @var Password
     */
    private $passwordHelper;

    public function __construct()
    {
        $this->passwordHelper = new Password();
        $this->table = Common::prefixTable(self::$rawPrefix);
    }

    /**
     * Returns the list of all the users
     *
     * @param string[] $userLogins List of users to select. If empty, will return all users
     * @return array the list of all the users
     */
    public function getUsers(array $userLogins)
    {
        $where = '';
        $bind  = array();

        if (!empty($userLogins)) {
            $where = 'WHERE login IN (' . Common::getSqlStringFieldsArray($userLogins) . ')';
            $bind  = $userLogins;
        }

        $db = $this->getDb();
        $users = $db->fetchAll("SELECT * FROM " . $this->table . "
                                $where
                                ORDER BY login ASC", $bind);

        return $users;
    }

    /**
     * Returns the list of all the users login
     *
     * @return array the list of all the users login
     */
    public function getUsersLogin()
    {
        $db = $this->getDb();
        $users = $db->fetchAll("SELECT login FROM " . $this->table . " ORDER BY login ASC");

        $return = array();
        foreach ($users as $login) {
            $return[] = $login['login'];
        }

        return $return;
    }

    public function getUsersSitesFromAccess($access)
    {
        $db = $this->getDb();
        $users = $db->fetchAll("SELECT login,idsite FROM " . Common::prefixTable("access")
                                . " WHERE access = ?
                                    ORDER BY login, idsite", $access);

        $return = array();
        foreach ($users as $user) {
            $return[$user['login']][] = $user['idsite'];
        }

        return $return;
    }

    public function getUsersAccessFromSite($idSite)
    {
        $db = $this->getDb();
        $users = $db->fetchAll("SELECT login,access FROM " . Common::prefixTable("access")
                             . " WHERE idsite = ?", $idSite);

        $return = array();
        foreach ($users as $user) {
            $return[$user['login']] = $user['access'];
        }

        return $return;
    }

    public function getUsersLoginWithSiteAccess($idSite, $access)
    {
        $db = $this->getDb();
        $users = $db->fetchAll("SELECT login FROM " . Common::prefixTable("access")
                               . " WHERE idsite = ? AND access = ?", array($idSite, $access));

        $logins = array();
        foreach ($users as $user) {
            $logins[] = $user['login'];
        }

        return $logins;
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
        $accessTable = Common::prefixTable('access');
        $siteTable = Common::prefixTable('site');

        $sql = sprintf("SELECT access.idsite, access.access 
    FROM %s access 
    LEFT JOIN %s site 
    ON access.idsite=site.idsite
     WHERE access.login = ? and site.idsite is not null", $accessTable, $siteTable);
        $db = $this->getDb();
        $users = $db->fetchAll($sql, $userLogin);
        $return = array();
        foreach ($users as $user) {
            $return[] = array(
                'site'   => $user['idsite'],
                'access' => $user['access'],
            );
        }
        return $return;
    }

    public function getSitesAccessFromUserWithFilters($userLogin, $limit = null, $offset = 0, $pattern = null, $access = null, $idSites = null)
    {
        $siteAccessFilter = new SiteAccessFilter($userLogin, $pattern, $access, $idSites);

        list($joins, $bind) = $siteAccessFilter->getJoins('a');

        list($where, $whereBind) = $siteAccessFilter->getWhere();
        $bind = array_merge($bind, $whereBind);

        $limitSql = '';
        $offsetSql = '';
        if ($limit) {
            $limitSql = "LIMIT " . (int)$limit;

            if ($offset) {
                $offsetSql = "OFFSET " . (int)$offset;
            }
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS s.idsite as idsite, s.name as site_name, GROUP_CONCAT(a.access SEPARATOR "|") as access
                  FROM ' . Common::prefixTable('access') . " a
                $joins
                $where
              GROUP BY s.idsite
              ORDER BY s.name ASC, s.idsite ASC
              $limitSql $offsetSql";
        $db = $this->getDb();

        $access = $db->fetchAll($sql, $bind);
        foreach ($access as &$entry) {
            $entry['access'] = explode('|', $entry['access']);
        }

        $count = $db->fetchOne("SELECT FOUND_ROWS()");

        return [$access, $count];
    }

    public function getIdSitesAccessMatching($userLogin, $filter_search = null, $filter_access = null, $idSites = null)
    {
        $siteAccessFilter = new SiteAccessFilter($userLogin, $filter_search, $filter_access, $idSites);

        list($joins, $bind) = $siteAccessFilter->getJoins('a');

        list($where, $whereBind) = $siteAccessFilter->getWhere();
        $bind = array_merge($bind, $whereBind);

        $sql = 'SELECT s.idsite FROM ' . Common::prefixTable('access') . " a $joins $where";

        $db = $this->getDb();

        $sites = $db->fetchAll($sql, $bind);
        $sites = array_column($sites, 'idsite');
        return $sites;
    }

    public function getUser($userLogin)
    {
        $db = $this->getDb();

        $matchedUsers = $db->fetchAll("SELECT * FROM {$this->table} WHERE login = ?", $userLogin);

        // for BC in 2.15 LTS, if there is a user w/ an exact match to the requested login, return that user.
        // this is done since before this change, login was case sensitive. until 3.0, we want to maintain
        // this behavior.
        foreach ($matchedUsers as $user) {
            if ($user['login'] == $userLogin) {
                return $user;
            }
        }

        return reset($matchedUsers);
    }

    public function getUserByEmail($userEmail)
    {
        $db = $this->getDb();
        return $db->fetchRow("SELECT * FROM " . $this->table . " WHERE email = ?", $userEmail);
    }

    public function getUserByTokenAuth($tokenAuth)
    {
        $db = $this->getDb();
        return $db->fetchRow('SELECT * FROM ' . $this->table . ' WHERE token_auth = ?', $tokenAuth);
    }

    public function addUser($userLogin, $hashedPassword, $email, $alias, $tokenAuth, $dateRegistered)
    {
        $user = array(
            'login'            => $userLogin,
            'password'         => $hashedPassword,
            'alias'            => $alias,
            'email'            => $email,
            'token_auth'       => $tokenAuth,
            'date_registered'  => $dateRegistered,
            'superuser_access' => 0,
            'ts_password_modified' => Date::now()->getDatetime(),
        );

        $db = $this->getDb();
        $db->insert($this->table, $user);
    }

    public function setSuperUserAccess($userLogin, $hasSuperUserAccess)
    {
        $this->updateUserFields($userLogin, array(
            'superuser_access' => $hasSuperUserAccess ? 1 : 0
        ));
    }

    public function updateUserFields($userLogin, $fields)
    {
        $set  = array();
        $bind = array();

        foreach ($fields as $key => $val) {
            $set[]  = "`$key` = ?";
            $bind[] = $val;
        }

        if (!empty($fields['password'])) {
            $set[] = "ts_password_modified = ?";
            $bind[] = Date::now()->getDatetime();
        }

        $bind[] = $userLogin;

        $db = $this->getDb();
        $db->query(sprintf('UPDATE `%s` SET %s WHERE `login` = ?', $this->table, implode(', ', $set)), $bind);
    }

    /**
     * Note that this returns the token_auth which is as private as the password!
     *
     * @return array[] containing login, email and token_auth
     */
    public function getUsersHavingSuperUserAccess()
    {
        $db = $this->getDb();
        $users = $db->fetchAll("SELECT login, email, token_auth, superuser_access
                                FROM " . Common::prefixTable("user") . "
                                WHERE superuser_access = 1
                                ORDER BY date_registered ASC");

        return $users;
    }

    public function updateUser($userLogin, $hashedPassword, $email, $alias, $tokenAuth)
    {
        $fields = array(
            'alias'      => $alias,
            'email'      => $email,
            'token_auth' => $tokenAuth
        );
        if (!empty($hashedPassword)) {
            $fields['password'] = $hashedPassword;
        }
        $this->updateUserFields($userLogin, $fields);
    }

    public function updateUserTokenAuth($userLogin, $tokenAuth)
    {
        $this->updateUserFields($userLogin, array(
            'token_auth' => $tokenAuth
        ));
    }

    public function userExists($userLogin)
    {
        $db = $this->getDb();
        $count = $db->fetchOne("SELECT count(*) FROM " . $this->table . " WHERE login = ?", $userLogin);

        return $count != 0;
    }

    public function userEmailExists($userEmail)
    {
        $db = $this->getDb();
        $count = $db->fetchOne("SELECT count(*) FROM " . $this->table . " WHERE email = ?", $userEmail);

        return $count != 0;
    }

    public function removeUserAccess($userLogin, $access, $idSites)
    {
        $db = $this->getDb();

        $table = Common::prefixTable("access");

        foreach ($idSites as $idsite) {
            $bind = array($userLogin, $idsite, $access);
            $db->query("DELETE FROM " . $table . " WHERE login = ? and idsite = ? and access = ?", $bind);
        }
    }

    public function addUserAccess($userLogin, $access, $idSites)
    {
        $db = $this->getDb();

        $insertSql = "INSERT INTO " . Common::prefixTable("access") . ' (idsite, login, access) VALUES (?, ?, ?)';
        foreach ($idSites as $idsite) {
            $db->query($insertSql, [$idsite, $userLogin, $access]);
        }
    }

    /**
     * @param string $userLogin
     */
    public function deleteUserOnly($userLogin)
    {
        $db = $this->getDb();
        $db->query("DELETE FROM " . $this->table . " WHERE login = ?", $userLogin);

        /**
         * Triggered after a user has been deleted.
         *
         * This event should be used to clean up any data that is related to the now deleted user.
         * The **Dashboard** plugin, for example, uses this event to remove the user's dashboards.
         *
         * @param string $userLogins The login handle of the deleted user.
         */
        Piwik::postEvent('UsersManager.deleteUser', array($userLogin));
    }

    public function deleteUserOptions($userLogin)
    {
        Option::deleteLike('UsersManager.%.' . $userLogin);
    }

    /**
     * @param string $userLogin
     */
    public function deleteUserAccess($userLogin, $idSites = null)
    {
        $db = $this->getDb();

        if (is_null($idSites)) {
            $db->query("DELETE FROM " . Common::prefixTable("access") . " WHERE login = ?", $userLogin);
        } else {
            foreach ($idSites as $idsite) {
                $db->query("DELETE FROM " . Common::prefixTable("access") . " WHERE idsite = ? AND login = ?", [$idsite, $userLogin]);
            }
        }
    }

    private function getDb()
    {
        return Db::get();
    }

    public function getUserLoginsMatching($idSite = null, $pattern = null, $access = null, $logins = null)
    {
        $filter = new UserTableFilter($access, $idSite, $pattern, $logins);

        list($joins, $bind) = $filter->getJoins('u');
        list($where, $whereBind) = $filter->getWhere();

        $bind = array_merge($bind, $whereBind);

        $sql = 'SELECT u.login FROM ' . $this->table . " u $joins $where";

        $db = $this->getDb();

        $result = $db->fetchAll($sql, $bind);
        $result = array_column($result, 'login');
        return $result;
    }

    /**
     * Returns all users and their access to `$idSite`.
     *
     * @param int $idSite
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $pattern text to search for if any
     * @param string|null $access 'noaccess','some','view','admin' or 'superuser'
     * @param string[]|null $logins the logins to limit the search to (if any)
     * @return array
     */
    public function getUsersWithRole($idSite, $limit = null, $offset = null, $pattern = null, $access = null, $logins = null)
    {
        $filter = new UserTableFilter($access, $idSite, $pattern, $logins);

        list($joins, $bind) = $filter->getJoins('u');
        list($where, $whereBind) = $filter->getWhere();

        $bind = array_merge($bind, $whereBind);

        $limitSql = '';
        $offsetSql = '';
        if ($limit) {
            $limitSql = "LIMIT " . (int)$limit;

            if ($offset) {
                $offsetSql = "OFFSET " . (int)$offset;
            }
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS u.*, GROUP_CONCAT(a.access SEPARATOR "|") as access
                  FROM ' . $this->table . " u
                $joins
                $where
              GROUP BY u.login
              ORDER BY u.login ASC
                 $limitSql $offsetSql";

        $db = $this->getDb();

        $users = $db->fetchAll($sql, $bind);
        foreach ($users as &$user) {
            $user['access'] = explode('|', $user['access']);
        }

        $count = $db->fetchOne("SELECT FOUND_ROWS()");

        return [$users, $count];
    }

    public function getSiteAccessCount($userLogin)
    {
        $sql = "SELECT COUNT(*) FROM " . Common::prefixTable('access') . " WHERE login = ?";
        $bind = [$userLogin];

        $db = $this->getDb();
        return $db->fetchOne($sql, $bind);
    }

    public function getUsersWithAccessToSites($idSites)
    {
        $idSites = array_map('intval', $idSites);

        $loginSql = 'SELECT DISTINCT ia.login FROM ' . Common::prefixTable('access') . ' ia WHERE ia.idsite IN ('
            . implode(',', $idSites) . ')';

        $logins = \Piwik\Db::fetchAll($loginSql);
        $logins = array_column($logins, 'login');
        return $logins;
    }

}
