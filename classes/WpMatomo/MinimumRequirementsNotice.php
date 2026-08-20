<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WpMatomo;
use WpMatomo\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class MinimumRequirementsNotice extends Feature {
	const OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED = 'matomo_minimum_requirements_notice_dismissed';
	const DISMISS_NONCE_NAME                         = 'matomo-minimum-requirements-notice-dismiss';

	const REQUIREMENTS_FAQ_URL = 'https://matomo.org/faq/wordpress/what-are-the-requirements-for-matomo-for-wordpress/';

	const NOTICE_CLASS = 'matomo-minimum-requirements-notice';

	const PLUGIN_MANAGEMENT_SCREENS = [ 'plugins', 'plugin-install', 'update-core' ];

	/**
	 * @var MinimumRequirements
	 */
	protected $requirements;

	/**
	 * @param MinimumRequirements|null $requirements
	 */
	public function __construct( $requirements = null ) {
		$this->requirements = $requirements ? $requirements : new MinimumRequirements();
	}

	public function is_active() {
		return is_admin();
	}

	public function register_hooks() {
		add_action( 'admin_notices', [ $this, 'check_requirements' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	public function enqueue_scripts() {
		wp_localize_script(
			'matomo-admin-js',
			'mtmMinimumRequirementsNoticeAjax',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::DISMISS_NONCE_NAME ),
			]
		);
	}

	public function register_ajax() {
		add_action(
			'wp_ajax_matomo_minimum_requirements_notice_dismissed',
			function () {
				check_ajax_referer( self::DISMISS_NONCE_NAME );

				if ( is_admin() ) {
					update_user_meta( get_current_user_id(), self::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED, true );
				}
			}
		);
	}

	public function check_requirements() {
		if ( ! \WpMatomo::is_admin_user() ) {
			return;
		}

		$is_plugin_blocked = $this->requirements->does_plugin_version_require_new_minimums( $this->get_plugin_version() );

		if ( ! $this->is_always_visible_page() ) {
			if ( ! $is_plugin_blocked || $this->is_dismissed() ) {
				return;
			}
		}

		$unmet = $this->requirements->get_unmet_requirements();
		if ( empty( $unmet ) ) {
			return;
		}

		if ( $is_plugin_blocked ) {
			$this->render_blocked_notice( $unmet );
			return;
		}

		$this->render_upcoming_requirements_notice( $unmet );
	}

	private function render_upcoming_requirements_notice( array $unmet ) {
		echo '<div class="matomo-notice ' . esc_attr( self::NOTICE_CLASS ) . ' notice notice-warning ' . esc_attr( $this->get_dismissible_class() ) . '" id="matomo-minimumrequirements"><p>'
			. sprintf(
				esc_html__( '%1$sHeads up!%2$s Matomo Analytics version 6 and later will require a newer server environment. You will not be able to update the plugin to that version until your server meets the new minimum requirements:', 'matomo' ),
				'<strong>',
				'</strong>'
			)
			. '</p>';

		$this->render_requirements_list( $unmet );

		echo '<p>'
			. sprintf(
				esc_html__( 'Please ask your hosting provider to update your server by %1$sNovember, 2026%2$s so you can keep receiving the latest Matomo features, bug fixes and security updates.', 'matomo' ),
				'<strong>',
				'</strong>'
			)
			. '</p></div>';
	}

	/**
	 * @param string[] $unmet
	 */
	private function render_blocked_notice( array $unmet ) {
		echo '<div class="matomo-notice ' . esc_attr( self::NOTICE_CLASS ) . ' notice notice-error ' . esc_attr( $this->get_dismissible_class() ) . '" id="matomo-minimumrequirementsblocked"><p>'
			. sprintf(
				esc_html__( '%1$sMatomo Analytics has been disabled.%2$s The installed version of Matomo Analytics needs a newer server environment than this server provides, so tracking and reporting are turned off to prevent errors. Your data has not been deleted.', 'matomo' ),
				'<strong>',
				'</strong>'
			)
			. '</p><p>'
			. esc_html__( 'Please ask your hosting provider to update your server so Matomo can be enabled again:', 'matomo' )
			. '</p>';

		$this->render_requirements_list( $unmet );

		echo '<p><a href="' . esc_url( self::REQUIREMENTS_FAQ_URL ) . '" target="_blank" rel="noreferrer noopener">'
			. esc_html__( 'Learn more about the requirements for Matomo for WordPress', 'matomo' )
			. '</a></p></div>';
	}

	/**
	 * @param string[] $unmet
	 */
	private function render_requirements_list( array $unmet ) {
		echo '<ul style="list-style: disc; margin-left: 20px;">';

		foreach ( $unmet as $requirement ) {
			echo '<li>' . esc_html( $requirement ) . '</li>';
		}

		echo '</ul>';
	}

	/**
	 * Overridable for tests.
	 *
	 * @return string
	 */
	protected function get_plugin_version() {
		return WpMatomo::VERSION;
	}

	/**
	 * @return bool
	 */
	private function is_always_visible_page() {
		return Admin::is_matomo_admin() || $this->is_plugin_management_page();
	}

	private function is_plugin_management_page() {
		$screen = get_current_screen();
		if ( empty( $screen ) || empty( $screen->id ) ) {
			return false;
		}

		$screen_id = preg_replace( '/-network$/', '', $screen->id );

		return in_array( $screen_id, self::PLUGIN_MANAGEMENT_SCREENS, true );
	}

	private function is_dismissed() {
		return (bool) get_user_meta( get_current_user_id(), self::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED, true );
	}

	private function get_dismissible_class() {
		return $this->is_always_visible_page() ? '' : 'is-dismissible';
	}
}
