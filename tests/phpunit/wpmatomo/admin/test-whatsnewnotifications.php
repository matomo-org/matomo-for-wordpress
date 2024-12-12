<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\WhatsNewNotifications;
use WpMatomo\Settings;

/**
 * TODO: multisite tests (manual)
 *
 * @group only
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

	public function test_on_admin_notices_outputs_nothing_when_no_notifications_to_show() {
		// TODO
	}

	public function test_on_admin_notices_outputs_notification_html_correctly() {
		// TODO
	}

	public function test_on_admin_enqueue_scripts_marks_notifications_for_the_current_page_as_seen() {
		// TODO
	}

	public function test_on_admin_enqueue_scripts_does_not_change_notification_status_if_no_notifications_for_the_current_page() {
		// TODO
	}

	public function test_on_dismiss_notification_aborts_if_supplied_nonce_value_is_incorrect() {
		// TODO
	}

	public function test_on_dismiss_notification_returns_false_if_no_notification_id_supplied() {
		// TODO
	}

	public function test_on_dismiss_notification_returns_false_if_notification_id_is_invalid() {
		// TODO
	}

	public function test_on_dismiss_notification_changes_status_of_requested_notification_to_dismissed() {
		// TODO
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

	private function dismiss_all_notifications( $notifications ) {
		$status = [];
		foreach ( $notifications as $id => $notification ) {
			$status[ $id ] = WhatsNewNotifications::STATUS_DISMISSED;
		}
		update_option( WhatsNewNotifications::NOTIFICATION_STATUSES_OPTION_NAME, $status );
	}
}
