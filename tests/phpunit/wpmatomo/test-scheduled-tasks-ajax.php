<?php
/**
 * @package matomo
 */

use WpMatomo\Capabilities;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site\Sync\SyncConfig;

class ScheduledTasksAjaxTest extends MatomoUnit_Ajax_TestCase {

	const A_RECORDED_FAILURE = [ 'matomo_sync' => 'Something went wrong while syncing.' ];

	public function setUp(): void {
		parent::setUp();

		$settings = new Settings();
		( new ScheduledTasks( $settings, new SyncConfig( $settings ) ) )->register_ajax();

		$this->wordpress_fixture->switch_to_admin_page();

		update_option( ScheduledTasks::FAILURES_LIST_OPTION, self::A_RECORDED_FAILURE );
	}

	public function tearDown(): void {
		delete_option( ScheduledTasks::FAILURES_LIST_OPTION );

		parent::tearDown();
	}

	public function test_remove_cron_error_ajax_should_refuse_a_user_who_does_not_administrate_the_blog() {
		$this->_setRole( 'subscriber' );

		$this->assertFalse( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$response = $this->call_ajax(
			'mtm_remove_cron_error',
			[],
			[
				'_ajax_nonce'   => wp_create_nonce( 'matomo-scheduled-task-errors' ),
				'matomo_job_id' => 'matomo_sync',
			]
		);

		$this->assertSame(
			[
				'success' => false,
				'data'    => [ 'message' => 'forbidden' ],
			],
			$response
		);

		// recorded once for the blog, so clearing it would have hidden the failure from its administrators
		$this->assertSame( self::A_RECORDED_FAILURE, get_option( ScheduledTasks::FAILURES_LIST_OPTION ) );
	}

	public function test_remove_cron_error_ajax_should_remove_the_recorded_failure_for_an_administrator() {
		$this->_setRole( 'administrator' );

		$this->assertTrue( current_user_can( Capabilities::KEY_SUPERUSER ) );

		$response = $this->call_ajax(
			'mtm_remove_cron_error',
			[],
			[
				'_ajax_nonce'   => wp_create_nonce( 'matomo-scheduled-task-errors' ),
				'matomo_job_id' => 'matomo_sync',
			]
		);

		$this->assertTrue( $response );

		$this->assertSame( [], get_option( ScheduledTasks::FAILURES_LIST_OPTION ) );
	}
}
