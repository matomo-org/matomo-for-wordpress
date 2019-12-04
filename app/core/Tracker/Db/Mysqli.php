<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Tracker\Db;

use Exception;
use Piwik\Tracker\Db;

/**
 * mysqli wrapper
 *
 */
class Mysqli extends Db
{
    protected $connection = null;
    protected $host;
    protected $port;
    protected $socket;
    protected $dbname;
    protected $username;
    protected $password;
    protected $charset;
    protected $activeTransaction = false;

    protected $enable_ssl;
    protected $ssl_key;
    protected $ssl_cert;
    protected $ssl_ca;
    protected $ssl_ca_path;
    protected $ssl_cipher;
    protected $ssl_no_verify;

    /**
     * Builds the DB object
     *
     * @param array $dbInfo
     * @param string $driverName
     */
    public function __construct($dbInfo, $driverName = 'mysql')
    {
	    if (isset($dbInfo['unix_socket']) && substr($dbInfo['unix_socket'], 0, 1) == '/') {
            $this->host = null;
            $this->port = null;
            $this->socket = $dbInfo['unix_socket'];
	    } elseif (isset($dbInfo['port']) && substr($dbInfo['port'], 0, 1) == '/') {
            $this->host = null;
            $this->port = null;
            $this->socket = $dbInfo['port'];
        } else {
            $this->host = $dbInfo['host'];
            $this->port = (int)$dbInfo['port'];
            $this->socket = null;
        }
        $this->dbname = $dbInfo['dbname'];
        $this->username = $dbInfo['username'];
        $this->password = $dbInfo['password'];
        $this->charset = isset($dbInfo['charset']) ? $dbInfo['charset'] : null;


        if(!empty($dbInfo['enable_ssl'])){
            $this->enable_ssl = $dbInfo['enable_ssl'];
        }
        if(!empty($dbInfo['ssl_key'])){
            $this->ssl_key = $dbInfo['ssl_key'];
        }
        if(!empty($dbInfo['ssl_cert'])){
            $this->ssl_cert = $dbInfo['ssl_cert'];
        }
        if(!empty($dbInfo['ssl_ca'])){
            $this->ssl_ca = $dbInfo['ssl_ca'];
        }
        if(!empty($dbInfo['ssl_ca_path'])){
            $this->ssl_ca_path = $dbInfo['ssl_ca_path'];
        }
        if(!empty($dbInfo['ssl_cipher'])){
            $this->ssl_cipher = $dbInfo['ssl_cipher'];
        }
        if(!empty($dbInfo['ssl_no_verify'])){
            $this->ssl_no_verify = $dbInfo['ssl_no_verify'];
        }

    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->connection = null;
    }

    /**
     * Connects to the DB
     *
     * @throws Exception|DbException  if there was an error connecting the DB
     */
    public function connect()
    {
        if (self::$profiling) {
            $timer = $this->initProfiler();
        }

        $this->connection = mysqli_init();


        if($this->enable_ssl){
            mysqli_ssl_set($this->connection, $this->ssl_key, $this->ssl_cert, $this->ssl_ca, $this->ssl_ca_path, $this->ssl_cipher);
        }

        // Make sure MySQL returns all matched rows on update queries including
        // rows that actually didn't have to be updated because the values didn't
        // change. This matches common behaviour among other database systems.
        // See #6296 why this is important in tracker
        $flags = MYSQLI_CLIENT_FOUND_ROWS;
        if ($this->enable_ssl){
            $flags = $flags | MYSQLI_CLIENT_SSL;
        }
        if ($this->ssl_no_verify && defined('MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT')){
            $flags = $flags | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT;
        }
        mysqli_real_connect($this->connection, $this->host, $this->username, $this->password, $this->dbname, $this->port, $this->socket, $flags);
        if (!$this->connection || mysqli_connect_errno()) {
            throw new DbException("Connect failed: " . mysqli_connect_error());
        }

        if ($this->charset && !mysqli_set_charset($this->connection, $this->charset)) {
            throw new DbException("Set Charset failed: " . mysqli_error($this->connection));
        }

        $this->password = '';

        if (self::$profiling && isset($timer)) {
            $this->recordQueryProfile('connect', $timer);
        }
    }

    /**
     * Disconnects from the server
     */
    public function disconnect()
    {
        mysqli_close($this->connection);
        $this->connection = null;
    }

    /**
     * Returns an array containing all the rows of a query result, using optional bound parameters.
     *
     * @see query()
     *
     * @param string $query Query
     * @param array $parameters Parameters to bind
     * @return array
     * @throws Exception|DbException if an exception occurred
     */
    public function fetchAll($query, $parameters = array())
    {
        try {
            if (self::$profiling) {
                $timer = $this->initProfiler();
            }

            $rows = array();
            $query = $this->prepare($query, $parameters);
            $rs = mysqli_query($this->connection, $query);
            if (is_bool($rs)) {
                throw new DbException('fetchAll() failed: ' . mysqli_error($this->connection) . ' : ' . $query);
            }

            while ($row = mysqli_fetch_array($rs, MYSQLI_ASSOC)) {
                $rows[] = $row;
            }
            mysqli_free_result($rs);

            if (self::$profiling && isset($timer)) {
                $this->recordQueryProfile($query, $timer);
            }
            return $rows;
        } catch (Exception $e) {
            throw new DbException("Error query: " . $e->getMessage());
        }
    }

    /**
     * Returns the first row of a query result, using optional bound parameters.
     *
     * @see query()
     *
     * @param string $query Query
     * @param array $parameters Parameters to bind
     *
     * @return array
     *
     * @throws DbException if an exception occurred
     */
    public function fetch($query, $parameters = array())
    {
        try {
            if (self::$profiling) {
                $timer = $this->initProfiler();
            }

            $query = $this->prepare($query, $parameters);
            $rs = mysqli_query($this->connection, $query);
            if (is_bool($rs)) {
                throw new DbException('fetch() failed: ' . mysqli_error($this->connection) . ' : ' . $query);
            }

            $row = mysqli_fetch_array($rs, MYSQLI_ASSOC);
            mysqli_free_result($rs);

            if (self::$profiling && isset($timer)) {
                $this->recordQueryProfile($query, $timer);
            }
            return $row;
        } catch (Exception $e) {
            throw new DbException("Error query: " . $e->getMessage());
        }
    }

    /**
     * Executes a query, using optional bound parameters.
     *
     * @param string $query Query
     * @param array|string $parameters Parameters to bind array('idsite'=> 1)
     *
     * @return bool|resource  false if failed
     * @throws DbException  if an exception occurred
     */
    public function query($query, $parameters = array())
    {
        if (is_null($this->connection)) {
            return false;
        }

        try {
            if (self::$profiling) {
                $timer = $this->initProfiler();
            }

            $query = $this->prepare($query, $parameters);
            $result = mysqli_query($this->connection, $query);
            if (!is_bool($result)) {
                mysqli_free_result($result);
            }

            if (self::$profiling && isset($timer)) {
                $this->recordQueryProfile($query, $timer);
            }
            return $result;
        } catch (Exception $e) {
            throw new DbException("Error query: " . $e->getMessage() . "
                                   In query: $query
                                   Parameters: " . var_export($parameters, true), $e->getCode());
        }
    }

    /**
     * Returns the last inserted ID in the DB
     *
     * @return int
     */
    public function lastInsertId()
    {
        return mysqli_insert_id($this->connection);
    }

    /**
     * Input is a prepared SQL statement and parameters
     * Returns the SQL statement
     *
     * @param string $query
     * @param array $parameters
     * @return string
     */
    private function prepare($query, $parameters)
    {
        if (!$parameters) {
            $parameters = array();
        } elseif (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        $this->paramNb = 0;
        $this->params = & $parameters;
        $query = preg_replace_callback('/\?/', array($this, 'replaceParam'), $query);

        return $query;
    }

    public function replaceParam($match)
    {
        $param = & $this->params[$this->paramNb];
        $this->paramNb++;

        if ($param === null) {
            return 'NULL';
        } else {
            return "'" . addslashes($param) . "'";
        }
    }

    public function isErrNo($e, $errno)
    {
        return \Piwik\Db\Adapter\Mysqli::isMysqliErrorNumber($e, $this->connection, $errno);
    }

    /**
     * Return number of affected rows in last query
     *
     * @param mixed $queryResult Result from query()
     * @return int
     */
    public function rowCount($queryResult)
    {
        return mysqli_affected_rows($this->connection);
    }

    /**
     * Start Transaction
     * @return string TransactionID
     */
    public function beginTransaction()
    {
        if (!$this->activeTransaction === false) {
            return;
        }

        if ($this->connection->autocommit(false)) {
            $this->activeTransaction = uniqid();
            return $this->activeTransaction;
        }
    }

    /**
     * Commit Transaction
     * @param $xid
     * @throws DbException
     * @internal param TransactionID $string from beginTransaction
     */
    public function commit($xid)
    {
        if ($this->activeTransaction != $xid || $this->activeTransaction === false) {
            return;
        }

        $this->activeTransaction = false;

        if (!$this->connection->commit()) {
            throw new DbException("Commit failed");
        }

        $this->connection->autocommit(true);
    }

    /**
     * Rollback Transaction
     * @param $xid
     * @throws DbException
     * @internal param TransactionID $string from beginTransaction
     */
    public function rollBack($xid)
    {
        if ($this->activeTransaction != $xid || $this->activeTransaction === false) {
            return;
        }

        $this->activeTransaction = false;

        if (!$this->connection->rollback()) {
            throw new DbException("Rollback failed");
        }

        $this->connection->autocommit(true);
    }
}
