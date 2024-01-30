<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Db;

use Piwik\Config;
use Piwik\Singleton;
/**
 * Schema abstraction
 *
 * Note: no relation to the ZF proposals for Zend_Db_Schema_Manager
 *
 * @method static \Piwik\Db\Schema getInstance()
 */
class Schema extends Singleton
{
    const DEFAULT_SCHEMA = 'Mysql';
    /**
     * Type of database schema
     *
     * @var string
     */
    private $schema = null;
    /**
     * Get schema class name
     *
     * @param string $schemaName
     * @return string
     */
    private static function getSchemaClassName($schemaName)
    {
        // Upgrade from pre 2.0.4
        if (strtolower($schemaName) == 'myisam' || empty($schemaName)) {
            $schemaName = self::DEFAULT_SCHEMA;
        }
        $class = str_replace(' ', '\\', ucwords(str_replace('_', ' ', strtolower($schemaName))));
        return '\\Piwik\\Db\\Schema\\' . $class;
    }
    /**
     * Load schema
     */
    private function loadSchema()
    {
        $config = Config::getInstance();
        $dbInfos = $config->database;
        $schemaName = trim($dbInfos['schema']);
        $className = self::getSchemaClassName($schemaName);
        $this->schema = new $className();
    }
    /**
     * Returns an instance that subclasses Schema
     *
     * @return \Piwik\Db\SchemaInterface
     */
    private function getSchema()
    {
        if ($this->schema === null) {
            $this->loadSchema();
        }
        return $this->schema;
    }
    /**
     * Get the SQL to create a specific Piwik table
     *
     * @param string $tableName name of the table to create
     * @return string  SQL
     */
    public function getTableCreateSql($tableName)
    {
        return $this->getSchema()->getTableCreateSql($tableName);
    }
    /**
     * Get the SQL to create Piwik tables
     *
     * @return array   array of strings containing SQL
     */
    public function getTablesCreateSql()
    {
        return $this->getSchema()->getTablesCreateSql();
    }
    /**
     * Creates a new table in the database.
     *
     * @param string $nameWithoutPrefix   The name of the table without any piwik prefix.
     * @param string $createDefinition    The table create definition
     */
    public function createTable($nameWithoutPrefix, $createDefinition)
    {
        $this->getSchema()->createTable($nameWithoutPrefix, $createDefinition);
    }
    /**
     * Create database
     *
     * @param null|string $dbName database name to create
     */
    public function createDatabase($dbName = null)
    {
        $this->getSchema()->createDatabase($dbName);
    }
    /**
     * Drop database
     */
    public function dropDatabase($dbName = null)
    {
        $this->getSchema()->dropDatabase($dbName);
    }
    /**
     * Create all tables
     */
    public function createTables()
    {
        $this->getSchema()->createTables();
    }
    /**
     * Creates an entry in the User table for the "anonymous" user.
     */
    public function createAnonymousUser()
    {
        $this->getSchema()->createAnonymousUser();
    }
    /**
     * Records the Matomo version a user used when installing this Matomo for the first time
     */
    public function recordInstallVersion()
    {
        $this->getSchema()->recordInstallVersion();
    }
    /**
     * Returns which Matomo version was used to install this Matomo for the first time.
     */
    public function getInstallVersion()
    {
        return $this->getSchema()->getInstallVersion();
    }
    /**
     * Truncate all tables
     */
    public function truncateAllTables()
    {
        $this->getSchema()->truncateAllTables();
    }
    /**
     * Names of all the prefixed tables in piwik
     * Doesn't use the DB
     *
     * @return array Table names
     */
    public function getTablesNames()
    {
        return $this->getSchema()->getTablesNames();
    }
    /**
     * Get list of tables installed
     *
     * @param bool $forceReload Invalidate cache
     * @return array  installed tables
     */
    public function getTablesInstalled($forceReload = true)
    {
        return $this->getSchema()->getTablesInstalled($forceReload);
    }
    /**
     * Get list of installed columns in a table
     *
     * @param  string $tableName The name of a table.
     *
     * @return array  Installed columns indexed by the column name.
     */
    public function getTableColumns($tableName)
    {
        return $this->getSchema()->getTableColumns($tableName);
    }
    /**
     * Returns true if Piwik tables exist
     *
     * @return bool  True if tables exist; false otherwise
     */
    public function hasTables()
    {
        return $this->getSchema()->hasTables();
    }
}
