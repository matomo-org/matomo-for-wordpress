<?php

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}
class ErrorNotice {

	const OPTION_NAME_SYSTEM_REPORT_ERRORS_DISMISSED = 'matomo_system_report_errors_dismissed';

	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}
	public function register_hooks() {
		add_action( 'admin_notices', [ $this, 'check_errors' ] );

		add_action(
			'wp_ajax_matomo_system_report_error_dismissed',
			function () {
				if ( is_admin() ) {
					update_user_meta( get_current_user_id(), self::OPTION_NAME_SYSTEM_REPORT_ERRORS_DISMISSED, true );
				}
			}
		);
	}

	public function check_errors() {
		if ( isset( $_GET['page'] ) && substr( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 0, 7 ) === 'matomo-' ) {
			$system_report = new \WpMatomo\Admin\SystemReport( $this->settings );
			if ( ! get_user_meta( get_current_user_id(), self::OPTION_NAME_SYSTEM_REPORT_ERRORS_DISMISSED ) && $system_report->errors_present() ) {
				echo '<div class="notice notice-warning is-dismissible" id="matomo-systemreporterrors"><p>'
					. sprintf(
						esc_html__( 'There are some errors in the %1$sMatomo Diagnostics System report%2$s that may prevent the plugin for working normally.', 'matomo' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=matomo-systemreport' ) ) . '">',
						'</a>'
					)
					. '</p></div>';
			}
		}
	}

}
