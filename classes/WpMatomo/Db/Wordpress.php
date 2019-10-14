<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Db\Adapter;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

require_once 'WordPressDbStatement.php';
require_once 'WordpressTracker.php';

class Wordpress extends Mysqli {

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
			$result  = $wpdb->query( $prepare );
		}

		return new WordPressDbStatement( $this, $sql, $result );
	}

	public function exec( $sqlQuery ) {
		global $wpdb;

		return $wpdb->query( $sqlQuery );
	}

	public function fetch( $query, $parameters = array() ) {
		return $this->fetchRow( $query, $parameters );
	}

	public function fetchCol( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		return $wpdb->get_col( $prepare );
	}

	public function fetchAssoc( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		return $wpdb->get_results( $prepare, ARRAY_A );
	}

	public function fetchAll( $sql, $bind = array(), $fetchMode = null ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		return $wpdb->get_results( $prepare, ARRAY_A );
	}

	public function fetchOne( $sql, $bind = array() ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );
		$value   = $wpdb->get_var( $prepare );
		if ( $value === null ) {
			return false; // make sure to behave same way as matomo
		}

		return $value;
	}

	public function fetchRow( $sql, $bind = array(), $fetchMode = null ) {
		global $wpdb;
		$prepare = $this->prepareWp( $sql, $bind );

		return $wpdb->get_row( $prepare, ARRAY_A );
	}

	public function insert( $table, array $bind ) {
		global $wpdb;

		return $wpdb->insert( $table, $bind );
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

		return $wpdb->query( $prepared );
	}
}
