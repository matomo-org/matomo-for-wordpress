<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

// TODO: confluence documentation for this (update release process)
// TODO: tests

/**
 * TODO
 */
class WhatsNewNotifications {

	const NOTIFICATION_STATUSES_OPTION_NAME = 'matomo-notification-statuses';

	const STATUS_UNSEEN    = 0;
	const STATUS_SEEN      = 1;
	const STATUS_DISMISSED = 2;

	const SHOW_ON_ALL_PAGES   = 'all';
	const SHOW_ON_SINGLE_PAGE = 'single';

	const NONCE_NAME = 'matomo-whats-new-notifications';

	private $statuses = null;

	public function is_active() {
		return is_admin() && is_super_admin() && Admin::is_matomo_admin();
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'on_admin_enqueue_scripts' ] );
		add_action( 'admin_notices', [ $this, 'on_admin_notices' ] );
	}

	public function register_ajax() {
		add_action( 'wp_ajax_mtm_dismiss_whats_new', [ $this, 'on_dismiss_notification' ] );
	}

	public function on_admin_notices() {
		$matomo_notifications = $this->get_notifications_to_show();

		require __DIR__ . '/views/whats-new-notifications.php';
	}

	public function on_admin_enqueue_scripts() {
		$this->mark_current_page_as_seen();

		// TODO: javascript part
		wp_localize_script(
			'matomo-admin-js',
			'mtmWhatsNewNotificationAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::NONCE_NAME ),
			]
		);

		// TODO: javascript part
		$unseen_notifications = $this->get_unseen_notifications();
		wp_localize_script(
			'matomo-admin-js',
			'mtmUnseenWhatsNewNotifications',
			$unseen_notifications
		);
	}

	public function on_dismiss_notification() {
		check_ajax_referer( self::NONCE_NAME );

		if ( ! is_super_admin() ) {
			wp_send_json( false );
			return;
		}

		if ( empty( $_POST['matomo_notification'] ) ) {
			wp_send_json( false );
			return;
		}

		$statuses = $this->get_notification_statuses();

		$notification_id = sanitize_text_field( wp_unslash( $_POST['matomo_notification'] ) );
		if ( ! isset( $statuses[ $notification_id ] ) ) {
			wp_send_json( false );
			return;
		}

		$statuses[ $notification_id ] = self::STATUS_DISMISSED;
		$this->save_notification_statuses( $statuses );
	}

	private function get_notification_statuses() {
		if ( null !== $this->statuses ) {
			return $this->statuses;
		}

		$this->statuses = get_option( self::NOTIFICATION_STATUSES_OPTION_NAME );

		if ( ! is_array( $this->statuses ) ) {
			$this->statuses = [];
		}

		return $this->statuses;
	}

	private function save_notification_statuses( $statuses ) {
		if ( ! is_array( $statuses ) ) {
			$statuses = [];
		}

		update_option( self::NOTIFICATION_STATUSES_OPTION_NAME, $statuses );
		$this->statuses = $statuses;
	}

	private function mark_current_page_as_seen() {
		$current_page = Admin::get_current_page();

		$notifications = $this->get_current_notifications();
		$statuses      = $this->get_notification_statuses();

		foreach ( $notifications as $notification ) {
			if ( $notification['notification_marker_page'] === $current_page ) {
				$statuses[ $notification['id'] ] = self::STATUS_SEEN;
			}
		}

		$this->save_notification_statuses( $statuses );
	}

	private function get_unseen_notifications() {
		$matomo_notifications = $this->get_current_notifications();
		$matomo_statuses      = $this->get_notification_statuses();

		$matomo_unseen_notifications = [];
		foreach ( $matomo_notifications as $notification ) {
			$id = $notification['id'];
			if ( ! isset( $matomo_statuses[ $id ] )
				|| self::STATUS_UNSEEN === $matomo_statuses[ $id ]
			) {
				$matomo_unseen_notifications[] = $id;
			}
		}
		return $matomo_unseen_notifications;
	}

	private function get_notifications_to_show() {
		$current_page = Admin::get_current_page();

		$matomo_notifications = $this->get_current_notifications();
		$matomo_statuses      = $this->get_notification_statuses();

		$notifications = [];
		foreach ( $matomo_notifications as $notification ) {
			$id = $notification['id'];

			// do not show notification if configured to show only on one page, and the current page
			// isn't the page to display
			if ( self::SHOW_ON_SINGLE_PAGE === $notification['show_on']
				&& $notification['notification_marker_page'] !== $current_page
			) {
				continue;
			}

			// do not show the notification if it's been dismissed
			if ( isset( $matomo_statuses[ $id ] )
				&& self::STATUS_DISMISSED === $matomo_statuses[ $id ]
			) {
				continue;
			}

			$notifications[] = $notification;
		}
		return $notifications;
	}

	private function get_current_notifications() {
		return [
			// crash analytics
			[
				'id'                       => 'crash-analytics-promo',
				'notification_marker_page' => 'matomo-marketplace',
				'message'                  => '', // TODO: notification copy HTML
				'show_on'                  => self::SHOW_ON_ALL_PAGES,
			],
		];
	}
}
