<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Tracker\Db;

use Piwik\Db\Adapter\WordPressDbStatement;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Wordpress extends Mysqli {

	private $old_suppress_errors_value = null;

	public function disconnect() {
		// we do not want to disconnect wordpress DB ever as it breaks eg the tests where it loses all
		// temporary tables... also we should leave it up to wordpress whether it wants to close db or not
		// global $wpdb;
		// $wpdb->close();
		//if ($this->connection) {
		// parent::disconnect();
		// }
	}

	public function connect() {
		// do not connect to DB
	}

	public function lastInsertId( $tableName = null, $primaryKey = null ) {
		global $wpdb;

		if ( empty( $wpdb->insert_id ) ) {
			return $this->fetchOne( 'SELECT LAST_INSERT_ID()' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * @param \wpdb $wpdb
	 *
	 * @throws \Zend_Db_Statement_Exception
	 */
	private function after_execute_query($wpdb)
	{
		if (isset($this->old_suppress_errors_value)) {
			$wpdb->suppress_errors($this->old_suppress_errors_value);
			$this->old_suppress_errors_value = null;
		}

		if ($wpdb->last_error) {
			throw new \Zend_Db_Statement_Exception($wpdb->last_error);
		}
	}

	private function before_execute_query($wpdb)
	{
		if (!$wpdb->suppress_errors
		    && defined('WP_DEBUG')
		    && WP_DEBUG
		    && defined('WP_DEBUG_DISPLAY')
		    && WP_DEBUG_DISPLAY) {
			// we want to prevent showing these notices
			if (defined('MATOMO_SUPPRESS_DB_ERRORS')) {
				if (MATOMO_SUPPRESS_DB_ERRORS === true) {
					$this->old_suppress_errors_value = $wpdb->suppress_errors( true );
				}
				// any other value than false and we will not supproess
				return;
			}

			$this->old_suppress_errors_value = $wpdb->suppress_errors( true );
		}
	}

	/**
	 * Test error number
	 *
	 * @param \Exception $e
	 * @param string $errno
	 *
	 * @return bool
	 */
	public function isErrNo( $e, $errno ) {
		if ( preg_match( '/(?:\[|\s)([0-9]{4})(?:\]|\s)/', $e->getMessage(), $match ) ) {
			return $match[1] == $errno;
		}
	}

	public function rowCount( $queryResult ) {
		return $queryResult->rowCount();
	}

	private function prepareWp( $sql, $bind = array() ) {
		global $wpdb;

		// fix some queries
		$sql = str_replace( '%', '%%', $sql ); // eg when "value like 'done%'"

		if ( is_array( $bind ) && empty( $bind ) ) {
			return $sql;
		}
		if ( ! is_array( $bind ) ) {
			$bind = array( $bind );
		}

		$sql = str_replace( '%', '%%', $sql );
		$sql = str_replace( '?', '%s', $sql );

		return $wpdb->prepare( $sql, $bind );
	}

	public function query( $query, $parameters = array() ) {
		global $wpdb;

		$test_query = trim( $query );
		if ( strpos( $test_query, '/*' ) === 0 ) {
			// remove eg "/* trigger = CronArchive */"
			$startPos   = strpos( $test_query, '*/' );
			$test_query = substr( $test_query, $startPos + strlen( '*/' ) );
			$test_query = trim( $test_query );
		}

		if ( preg_match( '/^\s*(select)\s/i', $test_query ) ) {
			// wordpress does not fetch any result when doing a select... it's only supposed to be used for things like
			// insert / update / drop ...
			$result = $this->fetchAll( $query, $parameters );
		} else {
			$query  = $this->prepareWp( $query, $parameters );
			$this->before_execute_query($wpdb);
			$result = $wpdb->query( $query );
			$this->after_execute_query($wpdb);
		}

		return new WordPressDbStatement( $this, $query, $result );
	}

	public function beginTransaction() {
		global $wpdb;
		if ( ! $this->activeTransaction === false ) {
			return;
		}
		$wpdb->query( 'START TRANSACTION' );
		if ( $this->connection->autocommit( false ) ) {
			$this->activeTransaction = uniqid();

			return $this->activeTransaction;
		}
	}

	/**
	 * Commit Transaction
	 *
	 * @param $xid
	 *
	 * @throws DbException
	 * @internal param TransactionID $string from beginTransaction
	 */
	public function commit( $xid ) {
		global $wpdb;

		if ( $this->activeTransaction != $xid || $this->activeTransaction === false ) {
			return;
		}

		$this->activeTransaction = false;

		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Rollback Transaction
	 *
	 * @param $xid
	 *
	 * @throws DbException
	 * @internal param TransactionID $string from beginTransaction
	 */
	public function rollBack( $xid ) {
		global $wpdb;

		if ( $this->activeTransaction != $xid || $this->activeTransaction === false ) {
			return;
		}

		$this->activeTransaction = false;

		$wpdb->query( 'ROLLBACK' );
	}

	public function fetch( $query, $parameters = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $query, $parameters );

		$this->before_execute_query($wpdb);

		$row = $wpdb->get_row( $prepare, ARRAY_A );

		$this->after_execute_query($wpdb);

		return $row;
	}

	public function fetchAll( $query, $parameters = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $query, $parameters );

		$this->before_execute_query($wpdb);

		$results = $wpdb->get_results( $prepare, ARRAY_A );

		$this->after_execute_query($wpdb);

		return $results;
	}


}
