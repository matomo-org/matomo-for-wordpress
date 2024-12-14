<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\WhatsNewNotifications;
use WpMatomo\Settings;

/**
 * TODO: multisite tests (manual)
 */
class WhatsNewNotificationsTest extends MatomoUnit_TestCase {

	/**
	 * @var Settings
	 */
	private $settings;

	public function setUp(): void {
		parent::setUp();

		$this->settings = new Settings();
	}

	public function test_is_active_should_return_false_if_not_admin_page() {
		$notifications = $this->get_visible_notifications();
		$instance      = $this->make_test_instance( $notifications );
		$actual        = $instance->is_active();

		$this->assertFalse( $actual );
	}

	public function test_is_active_should_return_false_if_no_notifications() {
		$this->assume_admin_page();

		$instance = $this->make_test_instance( [] );
		$actual   = $instance->is_active();

		$this->assertFalse( $actual );
	}

	public function test_is_active_should_return_false_if_no_notifications_for_current_page() {
		$this->assume_admin_page();

		$_GET['page'] = 'matomo-get-started';

		$notifications = $this->get_notifications_for_single_page();
		$instance      = $this->make_test_instance( $notifications );
		$actual        = $instance->is_active();

		$this->assertFalse( $actual );
	}

	public function test_is_active_should_return_false_if_all_notifications_dismissed() {
		$this->assume_admin_page();

		$_GET['page'] = 'matomo-marketplace';

		$notifications = $this->get_notifications_with_all_types();
		$this->dismiss_all_notifications( $notifications );

		$instance = $this->make_test_instance( $notifications );
		$actual   = $instance->is_active();

		$this->assertFalse( $actual );
	}

	public function test_is_active_returns_true_if_at_least_one_undismissed_notification_should_be_shown() {
		$this->assume_admin_page();

		$_GET['page'] = 'matomo-marketplace';

		$notifications = $this->get_notifications_with_all_types();

		$instance = $this->make_test_instance( $notifications );
		$actual   = $instance->is_active();

		$this->assertTrue( $actual );
	}

	public function test_register_hooks_adds_some_hooks_if_not_on_admin_page() {
		$this->assume_admin_page();

		$admin_enqueue_scripts_count_before = $this->get_hook_count( 'admin_enqueue_scripts' );
		$admin_notices_count_before         = $this->get_hook_count( 'admin_notices' );

		$instance = $this->make_test_instance( [] );
		$instance->register_hooks();

		$admin_enqueue_scripts_count_after = $this->get_hook_count( 'admin_enqueue_scripts' );
		$admin_notices_count_after         = $this->get_hook_count( 'admin_notices' );

		$this->assertEquals( 1, $admin_enqueue_scripts_count_after - $admin_enqueue_scripts_count_before );
		$this->assertEquals( 0, $admin_notices_count_after - $admin_notices_count_before );
	}

	public function test_register_hooks_adds_all_hooks_when_on_admin_page() {
		$this->assume_admin_page();

		$_GET['page'] = 'matomo-marketplace';

		$admin_enqueue_scripts_count_before = $this->get_hook_count( 'admin_enqueue_scripts' );
		$admin_notices_count_before         = $this->get_hook_count( 'admin_notices' );

		$instance = $this->make_test_instance( [] );
		$instance->register_hooks();

		$admin_enqueue_scripts_count_after = $this->get_hook_count( 'admin_enqueue_scripts' );
		$admin_notices_count_after         = $this->get_hook_count( 'admin_notices' );

		$this->assertEquals( 1, $admin_enqueue_scripts_count_after - $admin_enqueue_scripts_count_before );
		$this->assertEquals( 1, $admin_notices_count_after - $admin_notices_count_before );
	}

	public function test_on_admin_notices_outputs_nothing_when_no_notifications() {
		$instance = $this->make_test_instance( [] );

		ob_start();
		$instance->on_admin_notices();
		$output = trim( ob_get_clean() );

		$this->assertEmpty( $output );
	}

	public function test_on_admin_notices_outputs_nothing_when_no_notifications_to_show() {
		$notifications = $this->get_notifications_with_all_types();
		$instance      = $this->make_test_instance( $notifications );

		$this->dismiss_all_notifications( $notifications );

		ob_start();
		$instance->on_admin_notices();
		$output = trim( ob_get_clean() );

		$this->assertEmpty( $output );
	}

	public function test_on_admin_notices_outputs_notification_html_correctly() {
		$_GET['page'] = 'matomo-marketplace';

		$notifications = $this->get_notifications_with_all_types();
		$instance      = $this->make_test_instance( $notifications );

		ob_start();
		$instance->on_admin_notices();
		$output = trim( ob_get_clean() );

		$this->assertStringContainsString( 'test message all-pages-promo', $output );
		$this->assertStringContainsString( 'test message single-pages-promo', $output );
	}

	public function test_on_admin_enqueue_scripts_marks_notifications_for_the_current_page_as_seen() {
		$_GET['page'] = 'matomo-gdpr-tools';

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses                = $this->get_all_statuses();
		$expected_start_statuses = [];

		$this->assertEquals( $expected_start_statuses, $statuses );

		$instance->on_admin_enqueue_scripts();

		$statuses                = $this->get_all_statuses();
		$expected_start_statuses = [
			'all-pages-promo' => WhatsNewNotifications::STATUS_SEEN,
		];

		$this->assertEquals( $expected_start_statuses, $statuses );

		$_GET['page'] = 'matomo-marketplace';

		$instance->on_admin_enqueue_scripts();

		$statuses                = $this->get_all_statuses();
		$expected_start_statuses = [
			'all-pages-promo'    => WhatsNewNotifications::STATUS_SEEN,
			'single-pages-promo' => WhatsNewNotifications::STATUS_SEEN,
		];

		$this->assertEquals( $expected_start_statuses, $statuses );
	}

	public function test_on_admin_enqueue_scripts_does_not_change_notification_status_if_no_notifications_for_the_current_page() {
		$_GET['page'] = 'some-other-page';

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses                = $this->get_all_statuses();
		$expected_start_statuses = [];

		$this->assertEquals( $expected_start_statuses, $statuses );

		$instance->on_admin_enqueue_scripts();

		$statuses = $this->get_all_statuses();
		$this->assertEquals( $expected_start_statuses, $statuses );
	}

	public function test_on_dismiss_notification_aborts_if_supplied_nonce_value_is_incorrect() {
		$this->doing_ajax();

		wp_create_nonce( WhatsNewNotifications::NONCE_NAME );

		$_GET['_ajax_nonce'] = 'incorrectvalue';

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );

		ob_start();
		try {
			$instance->on_dismiss_notification();
		} catch ( \WPDieException $ex ) {
			// ignore
		}
		$output = ob_end_clean();

		$this->assertTrue( $output );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );
	}

	public function test_on_dismiss_notification_returns_false_if_no_notification_id_supplied() {
		$this->doing_ajax();

		$nonce                   = wp_create_nonce( WhatsNewNotifications::NONCE_NAME );
		$_REQUEST['_ajax_nonce'] = $nonce;

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );

		ob_start();
		try {
			$instance->on_dismiss_notification();
		} catch ( \WPDieException $ex ) {
			// ignore
		}
		$output = ob_end_clean();

		$this->assertEquals( 'false', $output );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );
	}

	public function test_on_dismiss_notification_returns_false_if_notification_id_is_invalid() {
		$this->doing_ajax();

		$nonce                   = wp_create_nonce( WhatsNewNotifications::NONCE_NAME );
		$_REQUEST['_ajax_nonce'] = $nonce;

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );

		$_POST['matomo_notification'] = 'slakdjfasldkfjsd';

		ob_start();
		try {
			$instance->on_dismiss_notification();
		} catch ( \WPDieException $ex ) {
			// ignore
		}
		$output = ob_end_clean();

		$this->assertEquals( 'false', $output );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );
	}

	public function test_on_dismiss_notification_changes_status_of_requested_notification_to_dismissed() {
		$this->doing_ajax();

		$nonce                   = wp_create_nonce( WhatsNewNotifications::NONCE_NAME );
		$_REQUEST['_ajax_nonce'] = $nonce;

		$notifications = $this->get_notifications_for_different_pages();
		$instance      = $this->make_test_instance( $notifications );

		$statuses = $this->get_all_statuses();
		$this->assertEmpty( $statuses );

		$_POST['matomo_notification'] = 'single-pages-promo';

		ob_start();
		try {
			$instance->on_dismiss_notification();
		} catch ( \WPDieException $ex ) {
			// ignore
		}
		$output = ob_end_clean();

		$this->assertEquals( 'false', $output );

		$statuses = $this->get_all_statuses();
		$this->assertEquals( [ 'single-pages-promo' => WhatsNewNotifications::STATUS_DISMISSED ], $statuses );
	}

	private function make_test_instance( $notifications ) {
		$test_instance = new class( $this->settings, $notifications ) extends WhatsNewNotifications {

			private $notifications;

			public function __construct( Settings $settings, $notifications ) {
				parent::__construct( $settings );
				$this->notifications = $notifications;
			}

			protected function get_current_notifications() {
				return $this->notifications;
			}
		};

		return $test_instance;
	}

	private function get_visible_notifications() {
		return [
			'all-pages-promo' => [
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => 'test message all-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_ALL_PAGES,
				'show_if'                  => true,
			],
		];
	}

	private function get_notifications_for_single_page() {
		return [
			'single-pages-promo' => [
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => 'test message single-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_SINGLE_PAGE,
				'show_if'                  => true,
			],
		];
	}

	private function get_notifications_with_all_types() {
		return [
			'all-pages-promo'    => [
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => 'test message all-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_ALL_PAGES,
				'show_if'                  => true,
			],
			'single-pages-promo' => [
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => 'test message single-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_SINGLE_PAGE,
				'show_if'                  => true,
			],
		];
	}

	private function get_notifications_for_different_pages() {
		return [
			'all-pages-promo'    => [
				'notification_marker_page' => 'matomo-gdpr-tools',
				'message'                  => 'test message all-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_ALL_PAGES,
				'show_if'                  => true,
			],
			'single-pages-promo' => [
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => 'test message single-pages-promo',
				'show_on'                  => WhatsNewNotifications::SHOW_ON_SINGLE_PAGE,
				'show_if'                  => true,
			],
		];
	}

	private function dismiss_all_notifications( $notifications ) {
		$status = [];
		foreach ( $notifications as $id => $notification ) {
			$status[ $id ] = WhatsNewNotifications::STATUS_DISMISSED;
		}
		update_option( WhatsNewNotifications::NOTIFICATION_STATUSES_OPTION_NAME, $status );
	}

	private function get_all_statuses() {
		$statuses = get_option( WhatsNewNotifications::NOTIFICATION_STATUSES_OPTION_NAME );
		return is_array( $statuses ) ? $statuses : [];
	}
}
