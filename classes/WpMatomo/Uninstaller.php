<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Uninstaller {

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct() {
		$this->logger = self::makeLogger();
	}

	private static function makeLogger() {
		return new Logger();
	}

	public function uninstall( $should_remove_all_data ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$this->uninstall_multisite( $should_remove_all_data );
		} else {
			$this->uninstall_blog( $should_remove_all_data );
		}

		do_action( 'matomo_uninstall', $should_remove_all_data );
	}

	public function uninstall_blog( $should_remove_all_data ) {
		$this->logger->log( 'Matomo is now uninstalling blogId ' . get_current_blog_id() );

		$settings = new Settings();

		$tasks = new ScheduledTasks( $settings );
		$tasks->uninstall();

		$roles = new Roles( $settings );
		$roles->uninstall();

		$paths = new Paths();

		if ( $should_remove_all_data ) {
			$this->logger->log( 'Matomo is forced to remove all data' );

			$settings->uninstall();

			$this->drop_tables();

			$site = new Site();
			$site->uninstall();

			$site = new User();
			$site->uninstall();

			$paths->uninstall();
		} else {
			$paths->clear_cache_dir();
		}

		do_action( 'matomo_uninstall_blog', $should_remove_all_data );

		$this->logger->log( 'Matomo has finished uninstalling ' . get_current_blog_id() );
	}

	public static function uninstall_options( $prefix ) {
		global $wpdb;

		self::makeLogger()->log( 'Removing options with prefix ' . $prefix );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '" . $prefix . "%';" );

		wp_cache_flush();
	}

	public static function uninstall_site_meta( $prefix ) {
		global $wpdb;

		if ( ! empty( $wpdb->sitemeta ) ) {
			// multisite
			self::makeLogger()->log( 'Removing sitemeta with prefix ' . $prefix );
			$wpdb->query( "DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '" . $prefix . "%';" );

			wp_cache_flush();
		} else {
			// not multisite
			self::uninstall_options( $prefix );
		}
	}

	public function uninstall_multisite( $should_remove_all_data ) {
		global $wpdb;

		$this->logger->log( 'Matomo is now uninstalling all blogs: ' . (int) $should_remove_all_data );

		$blogs = $wpdb->get_results( 'SELECT blog_id FROM ' . $wpdb->blogs . ' ORDER BY blog_id', ARRAY_A );

		if ( is_array( $blogs ) ) {
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog['blog_id'] );

				$this->uninstall_blog( $should_remove_all_data );

				restore_current_blog();
			}
		}
	}

	public function get_installed_matomo_tables() {
		global $wpdb;

		$tableNames = array();
		$tables     = $wpdb->get_results( 'SHOW TABLES LIKE "' . $wpdb->prefix . str_replace( '_', '\_', MATOMO_DATABASE_PREFIX ) . '%"', ARRAY_N );
		foreach ( $tables as $table_name_to_look_for ) {
			$tableNames[] = array_shift( $table_name_to_look_for );
		}

		// we need to hard code them unfortunately for tests cause there are temporary tables used and we can't find a
		// list of existing temp tables
		$table_names_to_look_for = array(
			'access',
			'archive_blob_2010_01',
			'archive_numeric_2010_01',
			'brute_force_log',
			'goal',
			'locks',
			'log_action',
			'log_conversion',
			'log_conversion_item',
			'log_link_visit_action',
			'log_profiling',
			'log_visit',
			'logger_message',
			'option',
			'plugin_setting',
			'privacy_logdata_anonymizations',
			'report',
			'report_subscriptions',
			'segment',
			'sequence',
			'session',
			'site',
			'site_setting',
			'site_url',
			'tagmanager_container',
			'tagmanager_container_release',
			'tagmanager_container_version',
			'tagmanager_tag',
			'tagmanager_trigger',
			'tagmanager_variable',
			'tracking_failure',
			'twofactor_recovery_code',
			'user',
			'user_dashboard',
			'user_language'
		);
		foreach ( range( 2010, date( 'Y' ) ) as $year ) {
			foreach ( range( 1, 12 ) as $month ) {
				$table_names_to_look_for[] = 'archive_numeric_' . $year . '_' . str_pad( $month, 2, '0' );
				$table_names_to_look_for[] = 'archive_blob_' . $year . '_' . str_pad( $month, 2, '0' );
			}
		}
		$table_names_to_look_for = apply_filters( 'matomo_install_tables', $table_names_to_look_for );

		foreach ( $table_names_to_look_for as $table_name_to_look_for ) {
			$table_name_to_test = $wpdb->prefix . MATOMO_DATABASE_PREFIX . $table_name_to_look_for;
			if ( ! in_array( $table_name_to_test, $tableNames ) ) {
				$tableNames[] = $table_name_to_test;
			}
		}

		return $tableNames;
	}

	private function drop_tables() {
		global $wpdb;

		$installed_tables = $this->get_installed_matomo_tables();
		$this->logger->log( sprintf( 'Matomo will now drop %s matomo tables', count( $installed_tables ) ) );

		foreach ( $installed_tables as $table_name ) {
			// temporary table are used in tests and just making sure they are being removed
			//$wpdb->query( "DROP TEMPORARY TABLE IF EXISTS `$tableName`" );
			// two spaces between drop and table so it won't be replaced in WP tests
			$wpdb->query( "DROP TABLE IF EXISTS `$table_name`" );
		}
	}
}
