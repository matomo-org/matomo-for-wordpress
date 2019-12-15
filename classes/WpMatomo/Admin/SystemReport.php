<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use Piwik\CliMulti;
use Piwik\Container\StaticContainer;
use Piwik\Filesystem;
use Piwik\MetricsFormatter;
use Piwik\Plugins\Diagnostics\Diagnostic\DiagnosticResult;
use Piwik\Plugins\Diagnostics\DiagnosticService;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Logger;
use WpMatomo\Paths;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\User\Sync as UserSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class SystemReport {
	const NONCE_NAME                      = 'matomo_troubleshooting';
	const TROUBLESHOOT_SYNC_USERS         = 'matomo_troubleshooting_action_site_users';
	const TROUBLESHOOT_SYNC_ALL_USERS     = 'matomo_troubleshooting_action_all_users';
	const TROUBLESHOOT_SYNC_SITE          = 'matomo_troubleshooting_action_site';
	const TROUBLESHOOT_SYNC_ALL_SITES     = 'matomo_troubleshooting_action_all_sites';
	const TROUBLESHOOT_CLEAR_MATOMO_CACHE = 'matomo_troubleshooting_action_clear_matomo_cache';
	const TROUBLESHOOT_ARCHIVE_NOW        = 'matomo_troubleshooting_action_archive_now';

	private $not_compatible_plugins = array(
		'background-manager/background-manager.php', // Uses an old version of Twig and plugin is no longer maintained.
	);

	private $valid_tabs = array( 'troubleshooting' );

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_not_compatible_plugins() {
		return $this->not_compatible_plugins;
	}

	private function execute_troubleshoot_if_needed() {
		if ( ! empty( $_POST )
			 && is_admin()
			 && check_admin_referer( self::NONCE_NAME )
			 && current_user_can( Capabilities::KEY_SUPERUSER ) ) {
			if ( ! empty( $_POST[ self::TROUBLESHOOT_ARCHIVE_NOW ] ) ) {
				Bootstrap::do_bootstrap();
				$scheduled_tasks = new ScheduledTasks( $this->settings );

				try {
					$errors = $scheduled_tasks->archive( $force = true, $throw_exception = false );
				} catch (\Exception $e) {
					echo '<div class="error">' . esc_html_e('Error', 'matomo') . ': '. matomo_anonymize_value($e->getMessage()) . '</div>';
					throw $e;
				}

				if ( ! empty( $errors ) ) {
					echo '<div class="notice notice-warning">';
					foreach ($errors as $error) {
						echo nl2br(esc_html(matomo_anonymize_value(var_export($error, 1))));
						echo '<br/>';
					}
					echo '</div>';
				}
			}

			if ( ! empty( $_POST[ self::TROUBLESHOOT_CLEAR_MATOMO_CACHE ] ) ) {
				$paths = new Paths();
				$paths->clear_cache_dir();
				// we first delete the cache dir manually just in case there's something
				// going wrong with matomo and bootstrapping would not even be possible.
				Bootstrap::do_bootstrap();
				Filesystem::deleteAllCacheOnUpdate();
			}

			if ( ! $this->settings->is_network_enabled() || ! is_network_admin() ) {
				if ( ! empty( $_POST[ self::TROUBLESHOOT_SYNC_USERS ] ) ) {
					$sync = new UserSync();
					$sync->sync_current_users();
				}
				if ( ! empty( $_POST[ self::TROUBLESHOOT_SYNC_SITE ] ) ) {
					$sync = new SiteSync( $this->settings );
					$sync->sync_current_site();
				}
			}
			if ( $this->settings->is_network_enabled() ) {
				if ( ! empty( $_POST[ self::TROUBLESHOOT_SYNC_ALL_SITES ] ) ) {
					$sync = new SiteSync( $this->settings );
					$sync->sync_all();
				}
				if ( ! empty( $_POST[ self::TROUBLESHOOT_SYNC_ALL_USERS ] ) ) {
					$sync = new UserSync();
					$sync->sync_all();
				}
			}
		}
	}

	public function show() {
		$this->execute_troubleshoot_if_needed();

		$settings = $this->settings;

		$matomo_active_tab = '';
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $this->valid_tabs, true ) ) {
			$matomo_active_tab = $_GET['tab'];
		}

		$matomo_tables = array();
		if ( empty( $matomo_active_tab ) ) {
			$matomo_tables = array(
				array(
					'title'        => 'Matomo',
					'rows'         => $this->get_matomo_info(),
					'has_comments' => true,
				),
				array(
					'title' => 'WordPress',
					'rows'  => $this->get_wordpress_info(),
				),
				array(
					'title'        => 'WordPress Plugins',
					'rows'         => $this->get_plugins_info(),
					'has_comments' => true,
				),
				array(
					'title'        => 'Server',
					'rows'         => $this->get_server_info(),
					'has_comments' => true,
				),
				array(
					'title'        => 'Database',
					'rows'         => $this->get_db_info(),
					'has_comments' => true,
				),
			);
		}

		$matomo_tables                    = $this->add_errors_first( $matomo_tables );
		$matomo_has_warning_and_no_errors = $this->has_only_warnings_no_error( $matomo_tables );

		include dirname( __FILE__ ) . '/views/systemreport.php';
	}

	private function has_only_warnings_no_error( $report_tables ) {
		$has_warning = false;
		$has_error   = false;
		foreach ( $report_tables as $report_table ) {
			foreach ( $report_table['rows'] as $row ) {
				if ( ! empty( $row['is_error'] ) ) {
					$has_error = true;
				}
				if ( ! empty( $row['is_warning'] ) ) {
					$has_warning = true;
				}
			}
		}

		return $has_warning && ! $has_error;
	}

	private function add_errors_first( $report_tables ) {
		$errors = array(
			'title'        => 'Errors',
			'rows'         => array(),
			'has_comments' => true,
		);
		foreach ( $report_tables as $report_table ) {
			foreach ( $report_table['rows'] as $row ) {
				if ( ! empty( $row['is_error'] ) ) {
					$errors['rows'][] = $row;
				}
			}
		}

		if ( ! empty( $errors['rows'] ) ) {
			array_unshift( $report_tables, $errors );
		}

		return $report_tables;
	}

	private function check_file_exists_and_writable( $rows, $path_to_check, $title, $required ) {
		$file_exists   = file_exists( $path_to_check );
		$file_readable = is_readable( $path_to_check );
		$file_writable = is_writable( $path_to_check );
		$comment       = '"' . $path_to_check . '" ';
		if ( ! $file_exists ) {
			$comment .= sprintf( esc_html__( '%s does not exist. ', 'matomo' ), $title );
		}
		if ( ! $file_readable ) {
			$comment .= sprintf( esc_html__( '%s is not readable. ', 'matomo' ), $title );
		}
		if ( ! $file_writable ) {
			$comment .= sprintf( esc_html__( '%s is not writable. ', 'matomo' ), $title );
		}

		$rows[] = array(
			'name'    => sprintf( esc_html__( '%s exists and is writable.', 'matomo' ), $title ),
			'value'   => $file_exists && $file_readable && $file_writable ? esc_html__( 'Yes', 'matomo' ) : esc_html__( 'No', 'matomo' ),
			'comment' => $comment,
			'is_error' => $required && (!$file_exists || !$file_readable),
			'is_warning' => !$required && (!$file_exists || !$file_readable)
		);

		return $rows;
	}

	private function get_matomo_info() {
		$rows = array();

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );

		$rows[] = array(
			'name'    => esc_html__( 'Matomo Plugin Version', 'matomo' ),
			'value'   => $plugin_data['Version'],
			'comment' => '',
		);

		$paths            = new Paths();
		$upload_dir       = $paths->get_upload_base_dir();
		$path_config_file = $upload_dir . '/' . MATOMO_CONFIG_PATH;
		$rows             = $this->check_file_exists_and_writable( $rows, $path_config_file, 'Config', true );

		$path_tracker_file = $upload_dir . '/matomo.js';
		$rows              = $this->check_file_exists_and_writable( $rows, $path_tracker_file, 'JS Tracker', false );

		$rows[] = array(
			'name'    => esc_html__( 'Plugin directories', 'matomo' ),
			'value'   => ! empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? 'Yes' : 'No',
			'comment' => ! empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? wp_json_encode( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) : '',
		);

		$tmp_dir = $paths->get_tmp_dir();

		$rows[] = array(
			'name'    => esc_html__( 'Tmp directory writable', 'matomo' ),
			'value'   => is_writable( $tmp_dir ),
			'comment' => $tmp_dir,
		);

		if ( ! empty( $_ENV['MATOMO_WP_ROOT_PATH'] ) ) {
			$custom_path = rtrim($_ENV['MATOMO_WP_ROOT_PATH'], '/') . '/wp-load.php';
			$path_exists = file_exists($custom_path );
			$comment = '';
			if (!$path_exists) {
				$comment = 'It seems the path does not point to the WP root directory.';
			}

			$rows[] = array(
				'name'    => 'Custom MATOMO_WP_ROOT_PATH',
				'value'   => $path_exists,
				'is_error' => ! $path_exists,
				'comment' => $comment,
			);
		}

		$report = null;

		if ( ! \WpMatomo::is_safe_mode() ) {
			try {
				Bootstrap::do_bootstrap();
				/** @var DiagnosticService $service */
				$service = StaticContainer::get( DiagnosticService::class );
				$report  = $service->runDiagnostics();

				$rows[] = array(
					'name'    => esc_html__( 'Matomo Version', 'matomo' ),
					'value'   => \Piwik\Version::VERSION,
					'comment' => '',
				);
			} catch ( \Exception $e ) {
				$rows[] = array(
					'name'    => esc_html__( 'Matomo System Check', 'matomo' ),
					'value'   => 'Failed to run, please open the system check in Matomo',
					'comment' => $e->getMessage(),
				);
			}

		}

		$site   = new Site();
		$idsite = $site->get_current_matomo_site_id();

		$rows[] = array(
			'name'    => esc_html__( 'Matomo Blog idSite', 'matomo' ),
			'value'   => $idsite,
			'comment' => '',
		);

		$rows[] = array(
			'section' => 'Endpoints',
		);

		$rows[] = array(
			'name'    => 'Matomo JavaScript Tracker URL',
			'value'   => '',
			'comment' => $paths->get_js_tracker_url_in_matomo_dir(),
		);

		$rows[] = array(
			'name'    => 'Matomo JavaScript Tracker - WP Rest API',
			'value'   => '',
			'comment' => $paths->get_js_tracker_rest_api_endpoint(),
		);

		$rows[] = array(
			'name'    => 'Matomo HTTP Tracking API',
			'value'   => '',
			'comment' => $paths->get_tracker_api_url_in_matomo_dir(),
		);

		$rows[] = array(
			'name'    => 'Matomo HTTP Tracking API - WP Rest API',
			'value'   => '',
			'comment' => $paths->get_tracker_api_rest_api_endpoint(),
		);

		$rows[] = array(
			'section' => 'Crons',
		);

		$scheduled_tasks = new ScheduledTasks( $this->settings );
		$all_events      = $scheduled_tasks->get_all_events();

		$rows[] = array(
			'name'    => esc_html__( 'Server time', 'matomo' ),
			'value'   => $this->convert_time_to_date( time(), false ),
			'comment' => '',
		);

		$rows[] = array(
			'name'    => esc_html__( 'Blog time', 'matomo' ),
			'value'   => $this->convert_time_to_date( time(), true ),
			'comment' => esc_html__( 'Below dates are shown in blog timezone', 'matomo' ),
		);

		foreach ( $all_events as $event_name => $event_config ) {
			$last_run_before = $scheduled_tasks->get_last_time_before_cron( $event_name );
			$last_run_after  = $scheduled_tasks->get_last_time_after_cron( $event_name );

			$next_scheduled = wp_next_scheduled( $event_name );

			$comment  = ' Last started: ' . $this->convert_time_to_date( $last_run_before, true, true ) . '.';
			$comment .= ' Last ended: ' . $this->convert_time_to_date( $last_run_after, true, true ) . '.';
			$comment .= ' Interval: ' . $event_config['interval'];

			$rows[] = array(
				'name'    => $event_config['name'],
				'value'   => 'Next run: ' . $this->convert_time_to_date( $next_scheduled, true, true ),
				'comment' => $comment,
			);
		}

		if ( ! \WpMatomo::is_safe_mode() && $report ) {
			$rows[] = array(
				'section' => esc_html__( 'Mandatory checks', 'matomo' ),
			);

			$rows = $this->add_diagnostic_results( $rows, $report->getMandatoryDiagnosticResults() );

			$rows[] = array(
				'section' => esc_html__( 'Optional checks', 'matomo' ),
			);
			$rows   = $this->add_diagnostic_results( $rows, $report->getOptionalDiagnosticResults() );

			$cli_multi = new CliMulti();

			$rows[] = array(
				'name'    => 'Supports Async Archiving',
				'value'   => $cli_multi->supportsAsync(),
				'comment' => '',
			);
		}

		$rows[] = array(
			'section' => 'Matomo Settings',
		);

		// always show these settings
		$global_settings_always_show = array(
			'track_mode',
			'track_codeposition',
			'track_api_endpoint',
			'track_js_endpoint',
		);
		foreach ( $global_settings_always_show as $key ) {
			$rows[] = array(
				'name'    => ucfirst( str_replace( '_', ' ', $key ) ),
				'value'   => $this->settings->get_global_option( $key ),
				'comment' => '',
			);
		}

		// otherwise show only few customised settings
		// mostly only numeric values and booleans to not eg accidentally show anything that would store a token etc
		// like we don't want to show license key etc
		foreach ( $this->settings->get_customised_global_settings() as $key => $val ) {
			if ( is_numeric( $val ) || is_bool( $val ) || 'track_content' === $key || 'track_user_id' === $key || 'core_version' === $key || 'version_history' === $key ) {
				if (is_array($val)) {
					$val = implode(', ', $val);
				}

				$rows[] = array(
					'name'    => ucfirst( str_replace( '_', ' ', $key ) ),
					'value'   => $val,
					'comment' => '',
				);
			}
		}

		$logs = new Logger();
		$error_log_entries = $logs->get_last_logged_entries();
		if (!empty($error_log_entries)) {
			$rows[] = array(
				'section' => 'Logs',
			);
			foreach ($error_log_entries as $error) {
				$error['value'] = $this->convert_time_to_date($error['value'], true, false);
				$rows[] = $error;
			}
		}

		return $rows;
	}

	private function convert_time_to_date( $time, $in_blog_timezone, $print_diff = false ) {
		if ( empty( $time ) ) {
			return esc_html__( 'Unknown', 'matomo' );
		}

		$date = gmdate( 'Y-m-d H:i:s', $time );

		if ( $in_blog_timezone ) {
			$date = get_date_from_gmt( $date, 'Y-m-d H:i:s' );
		}

		if ( $print_diff && class_exists( '\Piwik\MetricsFormatter' ) ) {
			$date .= ' (' . MetricsFormatter::getPrettyTimeFromSeconds( $time - time(), true, false, true ) . ')';
		}

		return $date;
	}

	private function add_diagnostic_results( $rows, $results ) {
		foreach ( $results as $result ) {
			$comment = '';
			/** @var DiagnosticResult $result */
			if ( $result->getStatus() !== DiagnosticResult::STATUS_OK ) {
				foreach ( $result->getItems() as $item ) {
					$item_comment = $item->getComment();
					if ( !empty($item_comment) && is_string($item_comment) ) {
						if (stripos($item_comment, 'core:archive') > 0) {
							// we only want to keep the first sentence like "	Archiving last ran successfully on Wednesday, January 2, 2019 00:00:00 which is 335 days 20:08:11 ago"
							// but not anything that asks user to set up a cronjob
							$item_comment = substr($item_comment, 0, stripos($item_comment, 'core:archive'));
							if (strpos($item_comment, '.') > 0) {
								$item_comment = substr($item_comment, 0, strripos($item_comment, '.'));
							} else {
								$item_comment = 'Archiving hasn\'t run in a while.';
							}
						}
						$comment .= $item_comment;
					}
				}
			}


			$rows[] = array(
				'name'       => $result->getLabel(),
				'value'      => $result->getStatus() . ' ' . $result->getLongErrorMessage(),
				'comment'    => $comment,
				'is_warning' => $result->getStatus() === DiagnosticResult::STATUS_WARNING,
				'is_error'   => $result->getStatus() === DiagnosticResult::STATUS_ERROR,
			);
		}

		return $rows;
	}

	private function get_wordpress_info() {
		$is_multi_site      = is_multisite();
		$num_blogs          = 1;
		$is_network_enabled = false;
		if ( $is_multi_site ) {
			if ( function_exists( 'get_blog_count' ) ) {
				$num_blogs = get_blog_count();
			}
			$settings           = new Settings();
			$is_network_enabled = $settings->is_network_enabled();
		}

		$rows   = array();
		$rows[] = array(
			'name'  => 'Home URL',
			'value' => home_url(),
		);
		$rows[] = array(
			'name'  => 'Site URL',
			'value' => site_url(),
		);
		$rows[] = array(
			'name'  => 'WordPress Version',
			'value' => get_bloginfo( 'version' ),
		);
		$rows[] = array(
			'name'  => 'Number of blogs',
			'value' => $num_blogs,
		);
		$rows[] = array(
			'name'  => 'Multisite Enabled',
			'value' => $is_multi_site,
		);
		$rows[] = array(
			'name'  => 'Network Enabled',
			'value' => $is_network_enabled,
		);
		$rows[] = array(
			'name'  => 'Debug Mode Enabled',
			'value' => defined( 'WP_DEBUG' ) && WP_DEBUG,
		);
		$rows[] = array(
			'name'  => 'Cron Enabled',
			'value' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);
		$rows[] = array(
			'name'  => 'Force SSL Admin',
			'value' => defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN,
		);

		$rows[] = array(
			'name'  => 'Language',
			'value' => defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US',
		);

		$rows[] = array(
			'name'  => 'Permalink Structure',
			'value' => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default',
		);

		$rows[] = array(
			'name'  => 'Possibly uses symlink',
			'value' => strpos(__DIR__, ABSPATH) === false && strpos(__DIR__, WP_CONTENT_DIR) === false,
		);

		$rows[] = array(
			'name'  => 'WP Cache enabled',
			'value' => defined('WP_CACHE') && WP_CACHE,
		);

		return $rows;
	}

	private function get_server_info() {
		$rows = array();

		if ( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			$rows[] = array(
				'name'  => 'Server Info',
				'value' => $_SERVER['SERVER_SOFTWARE'],
			);
		}
		if ( PHP_OS ) {
			$rows[] = array(
				'name'  => 'PHP OS',
				'value' => PHP_OS,
			);
		}
		$rows[] = array(
			'name'  => 'PHP Version',
			'value' => phpversion(),
		);
		$rows[] = array(
			'name'  => 'Timezone',
			'value' => date_default_timezone_get(),
		);
		$rows[] = array(
			'name'  => 'Locale',
			'value' => get_locale(),
		);

		$rows[] = array(
			'name'    => 'Memory Limit',
			'value'   => max( WP_MEMORY_LIMIT, @ini_get( 'memory_limit' ) ),
			'comment' => 'At least 128MB recommended. Depending on your traffic 256MB or more may be needed.',
		);

		$rows[] = array(
			'name'    => 'Max Memory Limit',
			'value'   => defined('WP_MAX_MEMORY_LIMIT') ? WP_MAX_MEMORY_LIMIT : '',
			'comment' => '',
		);

		$rows[] = array(
			'name'  => 'Time',
			'value' => time(),
		);

		$rows[] = array(
			'name'  => 'Max Execution Time',
			'value' => ini_get( 'max_execution_time' ),
		);
		$rows[] = array(
			'name'  => 'Max Post Size',
			'value' => ini_get( 'post_max_size' ),
		);
		$rows[] = array(
			'name'  => 'Max Upload Size',
			'value' => wp_max_upload_size(),
		);
		$rows[] = array(
			'name'  => 'Max Input Vars',
			'value' => ini_get( 'max_input_vars' ),
		);

		$zlib_compression = ini_get( 'zlib.output_compression' );
		$row              = array(
			'name'  => 'zlib.output_compression is off',
			'value' => $zlib_compression !== '1',
		);

		if ( $zlib_compression === '1' ) {
			$row['is_error'] = true;
			$row['comment']  = 'You need to set "zlib.output_compression" in your php.ini to "Off".';
		}
		$rows[] = $row;

		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
			$rows[]       = array(
				'name'  => 'Curl Version',
				'value' => $curl_version,
			);
		}

		return $rows;
	}

	private function get_db_info() {
		global $wpdb;
		$rows = array();

		$rows[] = array(
			'name'    => 'MySQL Version',
			'value'   => ! empty( $wpdb->is_mysql ) ? $wpdb->db_version() : '',
			'comment' => '',
		);

		$rows[] = array(
			'name'    => 'Mysqli Connect',
			'value'   => function_exists( 'mysqli_connect' ),
			'comment' => '',
		);
		$rows[] = array(
			'name'    => 'Force MySQL over Mysqli',
			'value'   => defined( 'WP_USE_EXT_MYSQL' ) && WP_USE_EXT_MYSQL,
			'comment' => '',
		);

		$rows[] = array(
			'name'  => 'DB Prefix',
			'value' => $wpdb->prefix,
		);

		if ( method_exists( $wpdb, 'parse_db_host' ) ) {
			$host_data = $wpdb->parse_db_host( DB_HOST );
			if ( $host_data ) {
				list( $host, $port, $socket, $is_ipv6 ) = $host_data;
			}

			$rows[] = array(
				'name'  => 'Uses Socket',
				'value' => ! empty( $socket ),
			);
			$rows[] = array(
				'name'  => 'Uses IPv6',
				'value' => ! empty( $is_ipv6 ),
			);
		}

		$grants = $this->get_db_grants();

		// we only show these grants for security reasons as only they are needed and we don't need to know any other ones
		$needed_grants = array( 'SELECT', 'INSERT', 'UPDATE', 'INDEX', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'CREATE TEMPORARY TABLES', 'LOCK TABLES' );
		if ( in_array( 'ALL PRIVILEGES', $grants, true ) ) {
			// ALL PRIVILEGES may be used pre MySQL 8.0
			$grants = $needed_grants;
		}

		$grants_missing = array_diff( $needed_grants, $grants );

		if ( empty( $grants )
		     || !is_array($grants)
		     || count($grants_missing) === count($needed_grants) ) {
			$rows[] = array(
				'name'       => esc_html__( 'Required permissions', 'matomo' ),
				'value'      => esc_html__( 'Failed to detect granted permissions', 'matomo' ),
				'comment'    => esc_html__( 'Please check your MySQL user has these permissions (grants):', 'matomo' ) . '<br />' . implode( ', ', $needed_grants ),
				'is_warning' => false,
			);
		} else {
			if ( ! empty( $grants_missing ) ) {
				$rows[] = array(
					'name'       => esc_html__( 'Required permissions', 'matomo' ),
					'value'      => esc_html__( 'Error', 'matomo' ),
					'comment'    => esc_html__( 'Missing permissions', 'matomo' ) . ': ' . implode( ', ', $grants_missing ) . '. ' . __( 'Please check if any of these MySQL permission (grants) are missing and add them if needed.', 'matomo' ) . ' ' . __( 'Learn more', 'matomo' ) . ': https://matomo.org/faq/troubleshooting/how-do-i-check-if-my-mysql-user-has-all-required-grants/',
					'is_warning' => true,
				);
			} else {
				$rows[] = array(
					'name'       => esc_html__( 'Required permissions', 'matomo' ),
					'value'      => esc_html__( 'OK', 'matomo' ),
					'comment'    => '',
					'is_warning' => false,
				);
			}
		}

		return $rows;
	}

	private function get_db_grants() {
		global $wpdb;

		$suppress_errors = $wpdb->suppress_errors;
		$wpdb->suppress_errors( true );// prevent any of this showing in logs just in case

		try {
			$values = $wpdb->get_results( 'SHOW GRANTS', ARRAY_N );
		} catch ( \Exception $e ) {
			// We ignore any possible error in case of permission or not supported etc.
			$values = array();
		}
		
		$wpdb->suppress_errors( $suppress_errors );

		$grants = array();
		foreach ( $values as $index => $value ) {
			if ( empty( $value[0] ) || ! is_string( $value[0] ) ) {
				continue;
			}

			if (stripos($value[0], 'ALL PRIVILEGES') !== false) {
				return array('ALL PRIVILEGES'); // the split on empty string wouldn't work otherwise
			}

			foreach ( array( ' ON ', ' TO ', ' IDENTIFIED ', ' BY ' ) as $keyword ) {
				if ( stripos( $values[ $index ][0], $keyword ) !== false ) {
					// make sure to never show by any accident a db user or password by cutting anything after on/to
					$values[ $index ][0] = substr( $value[0], 0, stripos( $value[0], $keyword ) );
				}
				if ( stripos( $values[ $index ][0], 'GRANT' ) !== false ) {
					// otherwise we end up having "grant select"... instead of just "select"
					$values[ $index ][0] = substr( $value[0], stripos( $values[ $index ][0], 'GRANT' ) + 5 );
				}
			}
			// make sure to never show by any accident a db user or password
			$values[ $index ][0] = str_replace( array( DB_USER, DB_PASSWORD ), array( 'DB_USER', 'DB_PASS' ), $values[ $index ][0] );

			$grants = array_merge( $grants, explode( ',', $values[ $index ][0] ) );
		}
		$grants = array_map( 'trim', $grants );
		$grants = array_map( 'strtoupper', $grants );
		$grants = array_unique( $grants );
		return $grants;
	}

	private function get_plugins_info() {
		$rows       = array();
		$mu_plugins = get_mu_plugins();

		if ( ! empty( $mu_plugins ) ) {
			$rows[] = array(
				'section' => 'MU Plugins',
			);

			foreach ( $mu_plugins as $mu_pin ) {
				$comment = '';
				if ( ! empty( $plugin['Network'] ) ) {
					$comment = 'Network enabled';
				}
				$rows[] = array(
					'name'    => $mu_pin['Name'],
					'value'   => $mu_pin['Version'],
					'comment' => $comment,
				);
			}

			$rows[] = array(
				'section' => 'Plugins',
			);
		}

		$plugins = get_plugins();

		foreach ( $plugins as $plugin ) {
			$comment = '';
			if ( ! empty( $plugin['Network'] ) ) {
				$comment = 'Network enabled';
			}
			$rows[] = array(
				'name'    => $plugin['Name'],
				'value'   => $plugin['Version'],
				'comment' => $comment,
			);
		}

		$active_plugins = get_option( 'active_plugins', array() );

		if ( ! empty( $active_plugins ) && is_array( $active_plugins ) ) {
			$active_plugins = array_map(function ($active_plugin){
				$parts = explode('/', trim($active_plugin));
				return trim($parts[0]);
			}, $active_plugins);

			$rows[] = array(
				'name'    => 'Active Plugins',
				'value'   => count( $active_plugins ),
				'comment' => implode( ' ', $active_plugins ),
			);

			$used_not_compatible = array_intersect( $active_plugins, $this->not_compatible_plugins );
			if ( ! empty( $used_not_compatible ) ) {
				$rows[] = array(
					'name'     => __( 'Not compatible plugins', 'matomo' ),
					'value'    => count( $used_not_compatible ),
					'comment'  => implode( ', ', $used_not_compatible ),
					'is_error' => true,
				);
			}
		}

		return $rows;
	}


}
