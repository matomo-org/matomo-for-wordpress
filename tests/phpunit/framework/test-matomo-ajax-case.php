<?php
/**
 * Matomo test case bootstrapping an entire Matomo.
 *
 * @package matomo
 */
abstract class MatomoAnalytics_Ajax_TestCase extends MatomoUnit_Ajax_TestCase {
	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	protected $matomo_fixture;

	public function setUp(): void {
		parent::setUp();
		$this->matomo_fixture = new MatomoUnit_Matomo_Fixture();
		$this->matomo_fixture->set_up( $this );
	}

	public function tearDown(): void {
		$this->matomo_fixture->tear_down();
		parent::tearDown();
	}
}
