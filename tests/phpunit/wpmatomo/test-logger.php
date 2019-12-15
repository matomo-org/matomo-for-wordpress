<?php
/**
 * Test logger.
 *
 * @package matomo
 */

use \WpMatomo\Logger;

class LoggerTest extends MatomoUnit_TestCase {

	/**
	 * @var Logger
	 */
	private $logger;

	public function setUp() {
		parent::setUp();

		$this->logger = new Logger();
	}

	public function test_get_last_logged_entries() {
		$this->assertSame( array(), $this->logger->get_last_logged_entries() );
	}

	public function test_log_different_php_types_wont_fail() {
		$this->logger->log( 'foo' );
		$this->logger->log( 555 );
		$this->logger->log( array( 'foo' ) );
		$this->logger->log( (object) array( 'foo' ) );
		$this->assertTrue( true );
	}

	public function test_log_exception() {
		$this->logger->log_exception( 'mykey', new Exception( 'foobar test' ) );
		$entries = $this->logger->get_last_logged_entries();

		$this->assertCount( 1, $entries );
		$this->assertEquals( 'mykey', $entries[0]['name'] );
		$this->assertNotEmpty( $entries[0]['value'] );
		$this->assertStringStartsWith( 'foobar test => test-logger.php:', $entries[0]['comment'] );
	}

}
