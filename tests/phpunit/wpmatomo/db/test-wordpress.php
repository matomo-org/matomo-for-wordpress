<?php
/**
 * @package matomo
 */

use Piwik\Common;
use Piwik\Db\Adapter\WordPress;

class DbWordPressTest extends MatomoAnalytics_TestCase {

	/**
	 * @var WordPress
	 */
	private $db;

	public function setUp(): void {
		parent::setUp();
		$this->db = new WordPress(
			array(
				'enable_ssl'     => false,
				'options'        => array(),
				'driver_options' => array(),
				'dbname'         => 'foo',
				'username'       => ' ',
				'password'       => ' ',
			)
		);
		$this->insert_many_values();
	}

	public function test_listTables() {
		 // we needed to overwrite this method as Zend uses by default getConnection() which we don't support.
		$tables = $this->db->listTables();
		$this->assertTrue( is_array( $tables ) );
	}

	public function test_describeTable() {
		$tables = $this->db->listTables();
		// we needed to overwrite this method as Zend uses by default getConnection() which we don't support.
		$tables = $this->db->describeTable( $tables[0] );
		$this->assertTrue( is_array( $tables ) );
		$this->assertNotEmpty( $tables );
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_query_triggers_error_when_wrong_sql() {
		$this->db->query( 'select * from foobarbaz' );
	}

	public function test_query_handles_null_values() {
		$table = Common::prefixTable( 'log_action' );
		$this->db->query(
			'INSERT INTO ' . $table . ' (name, hash, type, url_prefix) VALUES (?,CRC32(?),?,?)',
			array( 'myname', 'myname', 2, null )
		);

		$all = $this->db->fetchAll( 'select * from ' . $table );

		$this->assertSame(
			array(
				array(
					'idaction'   => '1',
					'name'       => 'myname',
					'hash'       => '2383257219',
					'type'       => '2',
					'url_prefix' => null,
				),
			),
			$all
		);
	}

	public function test_query_detects_error_code() {
		try {
			$this->db->query(
				'SELECT * from foobarbaz;'
			);
			$this->fail( 'Expected exception not thrown' );
		} catch ( Zend_Db_Exception $e ) {
			$this->assertContains( '[1146]', $e->getMessage() );
			$this->assertTrue( $this->db->isErrNo( $e, 1146 ) );
			$this->assertFalse( $this->db->isErrNo( $e, 1145 ) );
			$this->assertFalse( $this->db->isErrNo( $e, 1147 ) );
		}

		// make sure when there are two errors on same connection the correct error code is used...
		try {
			$table = Common::prefixTable( 'user' );
			$this->db->query(
				'SELECT bar from ' . $table
			);
			$this->fail( 'Expected exception not thrown 2' );
		} catch ( Zend_Db_Exception $e ) {
			$this->assertContains( '[1054]', $e->getMessage() );
			$this->assertTrue( $this->db->isErrNo( $e, 1054 ) );
		}
	}

	public function test_query_can_execute_select_queries() {
		$table  = Common::prefixTable( 'user' );
		$result = $this->db->query( ' select * from ' . $table );
		$first  = $result->fetch();
		$this->assertEquals( 'admin', $first['login'] );

		// calling it again will no longer find a result
		$this->assertEquals( array(), $result->fetch() );
	}

	public function test_query_can_execute_select_queries_with_comments() {
		$table  = Common::prefixTable( 'user' );
		$result = $this->db->query( ' /* trigger = CronArchive */ select * from ' . $table );

		$first = $result->fetch();
		$this->assertEquals( 'admin', $first['login'] );

		// calling it again will no longer find a result
		$this->assertEquals( array(), $result->fetch() );
	}

	public function test_query_finds_multiple_results() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->query( 'select access from ' . $table );

		$first = $result->fetch();
		$this->assertEquals( 'view', $first['access'] );

		$first = $result->fetch();
		$this->assertEquals( 'write', $first['access'] );

		$first = $result->fetch();
		$this->assertEquals( 'admin', $first['access'] );
	}

	public function test_query_can_insert() {
		$result = $this->insert_access( 'foobar', 'view' );
		$this->assertEquals( 1, $result->fetch() );
		$this->assertEquals( 4, $this->db->lastInsertId() );
	}

	public function test_fetch_all() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchAll( 'select * from ' . $table );
		$this->assertCount( 3, $result );
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_fetch_all_triggers_error_when_wrong_sql() {
		$this->db->fetchAll( 'select * from foobarbaz' );
	}

	public function test_fetch_all_works_with_bind_params() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchAll(
			'select * from ' . $table . ' where access = ? and idsite = ?',
			array(
				'view',
				1,
			)
		);
		$this->assertCount( 1, $result );
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_fetch_one_triggers_error_when_wrong_sql() {
		$this->db->fetchOne( 'select foo from foobarbaz' );
	}

	public function test_fetch_one() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchOne( 'select access from ' . $table . ' limit 1' );
		$this->assertEquals( 'view', $result );
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_fetch_row_triggers_error_when_wrong_sql() {
		$this->db->fetchRow( 'select * from foobarbaz limit 1' );
	}

	public function test_fetch_row() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchRow( 'select * from ' . $table . ' limit 1' );
		$this->assertEquals(
			array(
				'idaccess' => '1',
				'login'    => 'foo',
				'idsite'   => '1',
				'access'   => 'view',
			),
			$result
		);
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_exec_triggers_error_when_wrong_sql() {
		$this->db->exec( 'select * from foobarbaz' );
	}

	public function test_exec() {
		$table = Common::prefixTable( 'access' );

		$result = $this->db->fetchOne( 'select count(*) from ' . $table . ' where idsite = 2' );
		$this->assertEquals( 0, $result );

		$result = $this->db->exec( 'update ' . $table . ' set idsite = 2' );
		$this->assertEquals( 3, $result );

		$result = $this->db->fetchOne( 'select count(*) from ' . $table . ' where idsite = 2' );
		$this->assertEquals( 3, $result );
	}

	private function insert_many_values() {
		$this->insert_access( 'foo', 'view' );
		$this->insert_access( 'bar', 'write' );
		$this->insert_access( 'baz', 'admin' );
	}

	private function insert_access( $login, $permission ) {
		$table = Common::prefixTable( 'access' );

		return $this->db->query( sprintf( "insert into %s (login, idsite, access) values('%s', '1', '%s')", $table, $login, $permission ) );
	}


}
