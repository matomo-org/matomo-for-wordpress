<?php
/**
 * @package matomo
 */

use WpMatomo\Referral;

class ReferralTest extends MatomoUnit_TestCase {

	/**
	 * @var Referral
	 */
	private $referral;

	private $time;

	private $one_day_in_seconds = 86400;

	public function setUp(): void {
		parent::setUp();

		$this->time     = 1584663656;
		$this->referral = new Referral();
		$this->referral->set_time( $this->time );
	}

	public function test_should_show_not_when_first_time_called() {
		$this->assertEmpty( $this->referral->get_last_dismissed() );
		$this->assertFalse( $this->referral->should_show() );

		// should init the time 30 days back
		$this->assertEquals( 1582071656, $this->referral->get_last_dismissed() );
		$this->assertFalse( $this->referral->should_show() );
	}

	public function test_dismiss_sets_current_time() {
		$this->referral->dismiss();

		$this->assertEquals( $this->time, $this->referral->get_last_dismissed() );
		$this->assertFalse( $this->referral->should_show() );
	}

	public function test_dismiss_forever_sets_time_in_future() {
		$this->referral->dismiss_forever();

		$this->assertEquals( 1900023656, $this->referral->get_last_dismissed() );
		$this->assertFalse( $this->referral->should_show() );
	}

	public function test_should_show_when_90_days_back() {
		$this->referral->dismiss();

		$this->assertFalse( $this->referral->should_show() );

		$this->referral->set_time( 1584663656 + ( $this->one_day_in_seconds * 89.5 ) );
		$this->assertFalse( $this->referral->should_show() );

		$this->referral->set_time( 1584663656 + ( $this->one_day_in_seconds * 90.2 ) );
		$this->assertTrue( $this->referral->should_show() );

		$this->referral->dismiss();
		$this->assertFalse( $this->referral->should_show() );
	}

	public function test_can_refer_cannot_when_not_has_capability() {
		$this->assertFalse( $this->referral->can_refer() );
	}

	public function test_should_show_on_screen() {
		$this->assertFalse( $this->referral->should_show_on_screen() );
	}

	public function test_render() {
		ob_start();
		$this->referral->render();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'Rate Matomo', $output );
	}

}
