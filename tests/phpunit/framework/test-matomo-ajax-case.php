<?php
/**
 * Matomo test case bootstrapping an entire Matomo.
 *
 * @package matomo
 */
class MatomoAnalytics_Ajax_TestCase extends MatomoUnit_Ajax_TestCase {
	/**
	 * @var MatomoUnit_Matomo_Fixture
	 */
	private $matomo_fixture;

	public function setUp(): void {
		parent::setUp();
		$this->matomo_fixture = new MatomoUnit_Matomo_Fixture();
		$this->matomo_fixture->set_up( static::class, $this->getName() );
	}

	public function tearDown(): void {
		$this->matomo_fixture->tear_down();
		parent::tearDown();
	}

}
