<?php
/**
 * @package matomo
 */

use WpMatomo\Admin\WhatsNewNotifications;
use WpMatomo\Settings;

/**
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

		// TODO
	}

	public function test_is_active_should_return_false_if_no_notifications_for_current_page() {
		// TODO
	}

	public function test_is_active_should_return_false_if_all_notifications_dismissed() {
		// TODO
	}

	public function test_is_active_returns_true_if_at_least_one_undismissed_notification_should_be_shown() {
		// TODO
	}

	public function test_register_hooks_adds_some_hooks_if_not_on_admin_page() {
		// TODO
	}

	public function test_register_hooks_adds_all_hooks_when_on_admin_page() {
		// TODO
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
}
