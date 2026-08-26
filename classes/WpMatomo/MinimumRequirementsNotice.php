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

		if ( $is_plugin_blocked ) {
			$this->maybe_render_blocked_notice();
			return;
		}

		$this->maybe_render_upcoming_requirements_notice();
	}

	private function maybe_render_blocked_notice() {
		$is_dismissible = ! $this->is_always_visible_page();

		$unmet = $this->get_unmet_requirements_to_show( $is_dismissible );
		if ( empty( $unmet ) ) {
			return;
		}

		$this->render_blocked_notice( $unmet, $is_dismissible );
	}

	private function maybe_render_upcoming_requirements_notice() {
		$is_matomo_page = Admin::is_matomo_admin();

		if ( ! $is_matomo_page && ! $this->is_plugin_management_page() ) {
			return;
		}

		$is_dismissible = ! $is_matomo_page;

		$unmet = $this->get_unmet_requirements_to_show( $is_dismissible );
		if ( empty( $unmet ) ) {
			return;
		}

		$this->render_upcoming_requirements_notice( $unmet, $is_dismissible );
	}

	/**
	 * @param bool $is_dismissible whether a previous dismissal should be applied on this page
	 * @return string[] empty when there is nothing to show
	 */
	private function get_unmet_requirements_to_show( $is_dismissible ) {
		if ( $is_dismissible && $this->is_dismissed() ) {
			return []; // dismissed, nothing to show
		}

		return $this->requirements->get_unmet_requirements();
	}

	/**
	 * @param string[] $unmet
	 * @param bool     $is_dismissible
	 */
	private function render_upcoming_requirements_notice( array $unmet, $is_dismissible ) {
		echo '<div class="matomo-notice ' . esc_attr( self::NOTICE_CLASS ) . ' notice notice-warning ' . esc_attr( $this->get_dismissible_class( $is_dismissible ) ) . '" id="matomo-minimumrequirements"><p>'
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
	 * @param bool     $is_dismissible
	 */
	private function render_blocked_notice( array $unmet, $is_dismissible ) {
		echo '<div class="matomo-notice ' . esc_attr( self::NOTICE_CLASS ) . ' notice notice-error ' . esc_attr( $this->get_dismissible_class( $is_dismissible ) ) . '" id="matomo-minimumrequirementsblocked"><p>'
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

	private function get_dismissible_class( $is_dismissible ) {
		return $is_dismissible ? 'is-dismissible' : '';
	}
}
