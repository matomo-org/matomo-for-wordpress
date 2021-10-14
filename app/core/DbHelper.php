<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Exception;
use Piwik\Db\Schema;
use Piwik\DataAccess\ArchiveTableCreator;

/**
 * Contains database related helper functions.
 */
class DbHelper
{
    /**
     * Get list of tables installed
     *
     * @param bool $forceReload Invalidate cache
     * @return array  Tables installed
     */
    public static function getTablesInstalled($forceReload = true)
    {
        return Schema::getInstance()->getTablesInstalled($forceReload);
    }

    /**
     * Returns `true` if a table in the database, `false` if otherwise.
     *
     * @param string $tableName The name of the table to check for. Must be prefixed.
     *                          Avoid using user input, as the variable will be used in a query unescaped.
     * @return bool
     * @throws \Exception
     */
    public static function tableExists($tableName)
    {
        $tableName = str_replace(['%', '_', "'"], ['\%', '\_', '_'], $tableName);
        return Db::get()->query(sprintf("SHOW TABLES LIKE '%s'", $tableName))->rowCount() > 0;
    }

    /**
     * Get list of installed columns in a table
     *
     * @param  string $tableName The name of a table.
     *
     * @return array  Installed columns indexed by the column name.
     */
    public static function getTableColumns($tableName)
    {
        return Schema::getInstance()->getTableColumns($tableName);
    }

    /**
     * Creates a new table in the database.
     *
     * Example:
     * ```
     * $tableDefinition = "`age` INT(11) NOT NULL AUTO_INCREMENT,
     *                     `name` VARCHAR(255) NOT NULL";
     *
     * DbHelper::createTable('tablename', $tableDefinition);
     * ``
     *
     * @param string $nameWithoutPrefix   The name of the table without any piwik prefix.
     * @param string $createDefinition    The table create definition
     *
     * @api
     */
    public static function createTable($nameWithoutPrefix, $createDefinition)
    {
        Schema::getInstance()->createTable($nameWithoutPrefix, $createDefinition);
    }

    /**
     * Returns true if Piwik is installed
     *
     * @since 0.6.3
     *
     * @return bool  True if installed; false otherwise
     */
    public static function isInstalled()
    {
        try {
            return Schema::getInstance()->hasTables();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Truncate all tables
     */
    public static function truncateAllTables()
    {
        Schema::getInstance()->truncateAllTables();
    }

    /**
     * Creates an entry in the User table for the "anonymous" user.
     */
    public static function createAnonymousUser()
    {
        Schema::getInstance()->createAnonymousUser();
    }

    /**
     * Records the Matomo version a user used when installing this Matomo for the first time
     */
    public static function recordInstallVersion()
    {
        Schema::getInstance()->recordInstallVersion();
    }

    /**
     * Returns which Matomo version was used to install this Matomo for the first time.
     */
    public static function getInstallVersion()
    {
        return Schema::getInstance()->getInstallVersion();
    }

    public static function wasMatomoInstalledBeforeVersion($version)
    {
        $installVersion = self::getInstallVersion();
        if (empty($installVersion)) {
            return true; // we assume yes it was installed
        }
        return true === version_compare($version, $installVersion, '>');
    }

    /**
     * Create all tables
     */
    public static function createTables()
    {
        Schema::getInstance()->createTables();
    }

    /**
     * Drop database, used in tests
     */
    public static function dropDatabase($dbName = null)
    {
        if (defined('PIWIK_TEST_MODE') && PIWIK_TEST_MODE) {
            Schema::getInstance()->dropDatabase($dbName);
        }
    }


    /**
     * Checks the database server version against the required minimum
     * version.
     *
     * @see config/global.ini.php
     * @since 0.4.4
     * @throws Exception if server version is less than the required version
     */
    public static function checkDatabaseVersion()
    {
        Db::get()->checkServerVersion();
    }

    /**
     * Disconnect from database
     */
    public static function disconnectDatabase()
    {
        Db::get()->closeConnection();
    }

    /**
     * Create database
     *
     * @param string|null $dbName
     */
    public static function createDatabase($dbName = null)
    {
        Schema::getInstance()->createDatabase($dbName);
    }

    /**
     * Returns if the given table has an index with the given name
     *
     * @param string $table
     * @param string $indexName
     *
     * @return bool
     * @throws Exception
     */
    public static function tableHasIndex($table, $indexName)
    {
        $result = Db::get()->fetchOne('SHOW INDEX FROM '.$table.' WHERE Key_name = ?', [$indexName]);
        return !empty($result);
    }

    /**
     * Returns the default database charset to use
     *
     * Returns utf8mb4 if supported, with fallback to utf8
     *
     * @return string
     * @throws Tracker\Db\DbException
     */
    public static function getDefaultCharset()
    {
        $result = Db::get()->fetchRow("SHOW CHARACTER SET LIKE 'utf8mb4'");

        if (empty($result)) {
            return 'utf8'; // charset not available
        }

        $result = Db::get()->fetchRow("SHOW VARIABLES LIKE 'character_set_database'");

        if (!empty($result) && $result['Value'] === 'utf8mb4') {
            return 'utf8mb4'; // database has utf8mb4 charset, so assume it can be used
        }

        $result = Db::get()->fetchRow("SHOW VARIABLES LIKE 'innodb_file_per_table'");

        if (empty($result) || $result['Value'] !== 'ON') {
            return 'utf8'; // innodb_file_per_table is required for utf8mb4
        }

        return 'utf8mb4';
    }

    /**
     * Returns sql queries to convert all installed tables to utf8mb4
     *
     * @return array
     */
    public static function getUtf8mb4ConversionQueries()
    {
        $allTables = DbHelper::getTablesInstalled();

        $queries   = [];

        foreach ($allTables as $table) {
            $queries[] = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4;";
        }

        return $queries;
    }

    /**
     * Get the SQL to create Piwik tables
     *
     * @return array  array of strings containing SQL
     */
    public static function getTablesCreateSql()
    {
        return Schema::getInstance()->getTablesCreateSql();
    }

    /**
     * Get the SQL to create a specific Piwik table
     *
     * @param string $tableName Unprefixed table name.
     * @return string  SQL
     */
    public static function getTableCreateSql($tableName)
    {
        return Schema::getInstance()->getTableCreateSql($tableName);
    }

    /**
     * Deletes archive tables. For use in tests.
     */
    public static function deleteArchiveTables()
    {
        foreach (ArchiveTableCreator::getTablesArchivesInstalled() as $table) {
            Log::debug("Dropping table $table");

            Db::query("DROP TABLE IF EXISTS `$table`");
        }

        ArchiveTableCreator::refreshTableList($forceReload = true);
    }

    /**
     * Adds a MAX_EXECUTION_TIME hint into a SELECT query if $limit is bigger than 1
     *
     * @param string $sql  query to add hint to
     * @param int $limit  time limit in seconds
     * @return string
     */
    public static function addMaxExecutionTimeHintToQuery($sql, $limit)
    {
        if ($limit <= 0) {
            return $sql;
        }

        $sql = trim($sql);
        $pos = stripos($sql, 'SELECT');
        if ($pos !== false) {

            $timeInMs = $limit * 1000;
            $timeInMs = (int) $timeInMs;
            $maxExecutionTimeHint = ' /*+ MAX_EXECUTION_TIME('.$timeInMs.') */ ';

            $sql = substr_replace($sql, 'SELECT ' . $maxExecutionTimeHint, $pos, strlen('SELECT'));
        }

        return $sql;
    }

    /**
     * Returns true if the string is a valid database name for MySQL. MySQL allows + in the database names.
     * Database names that start with a-Z or 0-9 and contain a-Z, 0-9, underscore(_), dash(-), plus(+), and dot(.) will be accepted.
     * File names beginning with anything but a-Z or 0-9 will be rejected (including .htaccess for example).
     * File names containing anything other than above mentioned will also be rejected (file names with spaces won't be accepted).
     *
     * @param string $dbname
     * @return bool
     */
    public static function isValidDbname($dbname)
    {
        return (0 !== preg_match('/(^[a-zA-Z0-9]+([a-zA-Z0-9\_\.\-\+]*))$/D', $dbname));
    }

}
