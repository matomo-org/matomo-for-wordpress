<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Db\Adapter;

use WpMatomo\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

require_once 'WordPressDbStatement.php';
require_once 'WordpressTracker.php';

class Wordpress extends Mysqli {

	private $old_suppress_errors_value = null;

	/**
	 * Return default port.
	 *
	 * @return int
	 */
	public static function getDefaultPort() {
		return 3306;
	}

	/**
	 * Returns true if this adapter supports blobs as fields
	 *
	 * @return bool
	 */
	public function hasBlobDataType() {
		return true;
	}

	/**
	 * Returns true if this adapter supports bulk loading
	 *
	 * @return bool
	 */
	public function hasBulkLoader() {
		return false;
	}


	public static function isEnabled() {
		return true;
	}

	/**
	 * Is the connection character set equal to utf8?
	 *
	 * @return bool
	 */
	public function isConnectionUTF8() {
		$value = $this->fetchOne( 'SELECT @@character_set_client;' );

		return ! empty( $value ) && strtolower( $value ) === 'utf8';
	}

	public function checkClientVersion() {
		// not implemented as we don't need to check that
	}

	public function getClientVersion() {
		$value = $this->fetchOne( 'SELECT @@version;' );

		return ! empty( $value ) && strtolower( $value ) === 'utf8';
	}

	public function closeConnection() {
		// we do not want to disconnect wordpress DB ever as it breaks eg the tests where it loses all
		// temporary tables... also we should leave it up to wordpress whether it wants to close db or not
		// global $wpdb;
		// $wpdb->close();
		//if ($this->_connection) {
		//parent::closeConnection();
		//}
	}

	public function lastInsertId( $tableName = null, $primaryKey = null ) {
		global $wpdb;

		if ( empty( $wpdb->insert_id ) ) {
			return $this->fetchOne( 'SELECT LAST_INSERT_ID()' );
		}

		return $wpdb->insert_id;
	}

	public function listTables() {
		$sql = 'SHOW TABLES';

		return $this->fetchAll( $sql );
	}

	public function getServerVersion() {
		global $wpdb;

		return $wpdb->db_version();
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

		$sql = str_replace( '%', '%%', $sql ); // eg when "value like 'done%'"

		if ( is_array( $bind ) && empty( $bind ) ) {
			return $sql;
		}
		if ( ! is_array( $bind ) ) {
			$bind = array( $bind );
		}

		$sql = str_replace( '?', '%s', $sql );

		return $wpdb->prepare( $sql, $bind );
	}

	public function query( $sql, $bind = array() ) {
		global $wpdb;

		$test_sql = trim( $sql );
		if ( strpos( $test_sql, '/*' ) === 0 ) {
			// remove eg "/* trigger = CronArchive */"
			$startPos = strpos( $test_sql, '*/' );
			$test_sql = substr( $test_sql, $startPos + strlen( '*/' ) );
			$test_sql = trim( $test_sql );
		}

		if ( preg_match( '/^\s*(select)\s/i', $test_sql ) ) {
			// wordpress does not fetch any result when doing a select... it's only supposed to be used for things like
			// insert / update / drop ...
			$result = $this->fetchAll( $sql, $bind );
		} else {
			$prepare = $this->prepareWp( $sql, $bind );

			$this->before_execute_query( $wpdb, $sql );

			$result = $wpdb->query( $prepare );

			$this->after_execute_query( $wpdb );
		}

		return new WordPressDbStatement( $this, $sql, $result );
	}

	public function exec( $sqlQuery ) {
		global $wpdb;

		$this->before_execute_query( $wpdb, $sqlQuery );

		$exec = $wpdb->query( $sqlQuery );
		$this->after_execute_query( $wpdb );

		return $exec;
	}

	public function fetch( $query, $parameters = array() ) {
		return $this->fetchRow( $query, $parameters );
	}

	public function fetchCol( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		$this->before_execute_query( $wpdb, $sql );

		$col = $wpdb->get_col( $prepare );

		$this->after_execute_query( $wpdb );

		return $col;
	}

	public function fetchAssoc( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		$this->before_execute_query( $wpdb, $sql );

		$assoc = $wpdb->get_results( $prepare, ARRAY_A );

		$this->after_execute_query( $wpdb );

		return $assoc;
	}

	/**
	 * @param \wpdb $wpdb
	 *
	 * @throws \Zend_Db_Statement_Exception
	 */
	private function before_execute_query( $wpdb, $sql ) {
		if ( ! $wpdb->suppress_errors ) {

			if ( defined( 'MATOMO_SUPPRESS_DB_ERRORS' ) ) {
				// allow users to always suppress or never suppress
				if ( MATOMO_SUPPRESS_DB_ERRORS === true ) {
					$this->old_suppress_errors_value = $wpdb->suppress_errors( true );
				}

				return;
			}

			if ( defined( 'WP_DEBUG' )
			     && WP_DEBUG
			     && defined( 'WP_DEBUG_DISPLAY' )
			     && WP_DEBUG_DISPLAY
			     && ! is_admin() ) {
				// prevent showing some notices in frontend eg if cronjob runs there

				$is_likely_dedicated_cron = defined( 'DOING_CRON' ) && DOING_CRON && defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
				if ( ! $is_likely_dedicated_cron ) {
					// if cron is triggered through wp-cron.php, then we should maybe not suppress!
					$this->old_suppress_errors_value = $wpdb->suppress_errors( true );

					return;
				}
			}

			if ( ( stripos( $sql, 'SELECT 1 FROM' ) !== false && stripos( $sql, 'matomo_logtmpsegment' ) !== false )
			     || stripos( $sql, 'SELECT @@TX_ISOLATION' ) !== false
			     || stripos( $sql, 'SELECT @@transaction_isolation' ) !== false ) {
				// prevent notices for queries that are expected to fail
				//  SELECT 1 FROM wp_matomo_logtmpsegment1cc77bce7a13181081e44ea6ffc0a9fd LIMIT 1 => runs to detect if temp table exists or not and regularly the query fails which is expected
				//  SELECT @@TX_ISOLATION => not available in all mysql versions
				//  SELECT @@transaction_isolation => not available in all mysql versions
				// we show notices only in admin...
				$this->old_suppress_errors_value = $wpdb->suppress_errors( true );

				return;
			}

		}
	}

	/**
	 * @param \wpdb $wpdb
	 *
	 * @throws \Zend_Db_Statement_Exception
	 */
	private function after_execute_query( $wpdb ) {
		if ( isset( $this->old_suppress_errors_value ) ) {
			$wpdb->suppress_errors( $this->old_suppress_errors_value );
			$this->old_suppress_errors_value = null;
		}

		if ( $wpdb->last_error ) {
			throw new \Zend_Db_Statement_Exception( 'WP DB Error: ' . $wpdb->last_error );
		}
	}

	public function fetchAll( $sql, $bind = array(), $fetchMode = null ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		$this->before_execute_query( $wpdb, $sql );

		$results = $wpdb->get_results( $prepare, ARRAY_A );

		$this->after_execute_query( $wpdb );

		return $results;
	}

	public function fetchOne( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		$this->before_execute_query( $wpdb, $sql );

		$value = $wpdb->get_var( $prepare );

		$this->after_execute_query( $wpdb );

		if ( $value === null ) {
			return false; // make sure to behave same way as matomo
		}

		return $value;
	}

	public function fetchRow( $sql, $bind = array(), $fetchMode = null ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		$this->before_execute_query( $wpdb, $sql );

		$row = $wpdb->get_row( $prepare, ARRAY_A );

		$this->after_execute_query( $wpdb );

		return $row;
	}

	public function insert( $table, array $bind ) {
		global $wpdb;

		$this->before_execute_query( $wpdb, '' );

		$insert = $wpdb->insert( $table, $bind );

		$this->after_execute_query( $wpdb );

		return $insert;
	}

	public function update( $table, array $bind, $where = '' ) {
		global $wpdb;

		$fields = array();
		foreach ( $bind as $field => $val ) {
			$fields[] = "`$field` = %s";
		}
		$fields = implode( ', ', $fields );

		$sql      = "UPDATE `$table` SET $fields " . ( ( $where ) ? " WHERE $where" : '' );
		$prepared = $wpdb->prepare( $sql, $bind );

		$this->before_execute_query( $wpdb, '' );

		$update = $wpdb->query( $prepared );

		$this->after_execute_query( $wpdb );

		return $update;
	}
}
