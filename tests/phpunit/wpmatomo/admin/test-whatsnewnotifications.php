<?php
/**
 * @package matomo
 */

use WpMatomo\Settings;

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
		// TODO
	}

	public function test_is_active_should_return_false_if_no_notifications() {
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
}
