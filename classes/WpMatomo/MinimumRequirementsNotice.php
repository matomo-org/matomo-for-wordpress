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

	const REQUIRED_PHP_VERSION     = '8.1';
	const REQUIRED_MYSQL_VERSION   = '8.0';
	const REQUIRED_MARIADB_VERSION = '10.6';

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

		$is_plugins_admin_page = $this->is_plugins_admin_page();
		$is_matomo_page        = Admin::is_matomo_admin();

		// only display on matomo admin pages and the plugins admin page
		if ( ! $is_plugins_admin_page && ! $is_matomo_page ) {
			return;
		}

		if (
			$is_plugins_admin_page
			&& get_user_meta( get_current_user_id(), self::OPTION_NAME_MINIMUM_REQUIREMENTS_DISMISSED, true )
		) {
			return;
		}

		$unmet = $this->get_unmet_requirements();
		if ( empty( $unmet ) ) {
			return;
		}

		$dismissible = $is_plugins_admin_page ? 'is-dismissible' : '';

		echo '<div class="matomo-notice notice notice-warning ' . esc_attr( $dismissible ) . '" id="matomo-minimumrequirements"><p>'
			. sprintf(
				esc_html__( '%1$sHeads up!%2$s Matomo Analytics version 6 and later will require a newer server environment. You will not be able to update the plugin to that version until your server meets the new minimum requirements:', 'matomo' ),
				'<strong>',
				'</strong>'
			)
			. '</p><ul style="list-style: disc; margin-left: 20px;">';

		foreach ( $unmet as $requirement ) {
			echo '<li>' . esc_html( $requirement ) . '</li>';
		}

		echo '</ul><p>'
			. sprintf(
				esc_html__( 'Please ask your hosting provider to update your server by %1$sNovember, 2026%2$s so you can keep receiving the latest Matomo features, bug fixes and security updates.', 'matomo' ),
				'<strong>',
				'</strong>'
			)
			. '</p></div>';
	}

	public function get_unmet_requirements( $php_version = PHP_VERSION ) {
		$unmet = [];

		if ( version_compare( $php_version, self::REQUIRED_PHP_VERSION, '<' ) ) {
			$unmet[] = sprintf(
				__( 'PHP %1$s or higher is required (you are currently using PHP %2$s).', 'matomo' ),
				self::REQUIRED_PHP_VERSION,
				$php_version
			);
		}

		$db = $this->get_db_server();
		if ( ! empty( $db['version'] ) ) {
			if ( $db['is_mariadb'] ) {
				if ( version_compare( $db['version'], self::REQUIRED_MARIADB_VERSION, '<' ) ) {
					$unmet[] = sprintf(
						__( 'MariaDB %1$s or higher is required (you are currently using MariaDB %2$s).', 'matomo' ),
						self::REQUIRED_MARIADB_VERSION,
						$db['version']
					);
				}
			} elseif ( version_compare( $db['version'], self::REQUIRED_MYSQL_VERSION, '<' ) ) {
				$unmet[] = sprintf(
					__( 'MySQL %1$s or higher is required (you are currently using MySQL %2$s).', 'matomo' ),
					self::REQUIRED_MYSQL_VERSION,
					$db['version']
				);
			}
		}

		return $unmet;
	}

	/**
	 * Public for tests.
	 */
	public function get_db_server() {
		global $wpdb;

		if ( empty( $wpdb ) || empty( $wpdb->is_mysql ) ) {
			return [
				'is_mariadb' => false,
				'version'    => '',
			];
		}

		$server_info = method_exists( $wpdb, 'db_server_info' ) ? $wpdb->db_server_info() : '';
		$is_mariadb  = $server_info && false !== stripos( $server_info, 'mariadb' );

		if ( $is_mariadb && preg_match( '/(?:5\.5\.5-)?(\d+\.\d+\.\d+).*?mariadb/i', $server_info, $matches ) ) {
			$version = $matches[1];
		} else {
			// db_version() strips any suffix and returns the numeric version only.
			$version = (string) $wpdb->db_version();
		}

		return [
			'is_mariadb' => $is_mariadb,
			'version'    => $version,
		];
	}

	private function is_plugins_admin_page() {
		$screen = get_current_screen();
		return ! empty( $screen ) && 'plugins' === $screen->id;
	}
}
