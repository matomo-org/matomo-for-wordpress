<?php
/**
 * @package matomo
 */

use Piwik\Common;
use Piwik\Tracker\Db\Wordpress;

class DbWordPressTrackerTest extends MatomoAnalytics_TestCase {

	/**
	 * @var WordPress
	 */
	private $db;

	public function setUp() {
		parent::setUp();
		$this->db = new Wordpress(
			array(
				'enable_ssl'     => false,
				'options'        => array(),
				'driver_options' => array(),
				'dbname'         => 'foo',
				'username'       => ' ',
				'password'       => ' ',
				'port'           => ' ',
				'host'           => ' ',
			)
		);
		$this->insert_many_values();
	}

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_query_triggers_error_when_wrong_sql() {
		$this->db->query( 'select * from foobarbaz' );
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

	/**
	 * @expectedException \Zend_Db_Statement_Exception
	 * @expectedExceptionMessage  foobarbaz' doesn't exist
	 */
	public function test_fetch_all_triggers_error_when_wrong_sql() {
		$this->db->fetchAll( 'select * from foobarbaz' );
	}

	public function test_fetch_all() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchAll( 'select * from ' . $table );
		$this->assertCount( 3, $result );
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

	public function test_fetch_one() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetchOne( 'select access from ' . $table . ' limit 1' );
		$this->assertEquals( 'view', $result );
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

	public function test_fetch() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetch( 'select * from ' . $table . ' limit 1' );
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

	public function test_fetch_with_bind_params() {
		$table  = Common::prefixTable( 'access' );
		$result = $this->db->fetch( 'select * from ' . $table . ' where idsite = ? limit 1', array( '1' ) );
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

	public function test_exec() {
		$table = Common::prefixTable( 'access' );

		$result = $this->db->fetchOne( 'select count(*) from ' . $table . ' where idsite = 2' );
		$this->assertEquals( 0, $result );

		$result = $this->db->exec( 'update ' . $table . ' set idsite = 2' );
		$this->assertNull( $result );

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
