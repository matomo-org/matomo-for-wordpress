<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
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
use WpMatomo\Paths;
use WpMatomo\ScheduledTasks;
use WpMatomo\Settings;
use WpMatomo\Site\Sync as SiteSync;
use WpMatomo\User\Sync as UserSync;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class SystemReport {
	const NONCE_NAME = 'matomo_troubleshooting';
	const TROUBLESHOOT_SYNC_USERS = 'matomo_troubleshooting_action_site_users';
	const TROUBLESHOOT_SYNC_ALL_USERS = 'matomo_troubleshooting_action_all_users';
	const TROUBLESHOOT_SYNC_SITE = 'matomo_troubleshooting_action_site';
	const TROUBLESHOOT_SYNC_ALL_SITES = 'matomo_troubleshooting_action_all_sites';
	const TROUBLESHOOT_CLEAR_MATOMO_CACHE = 'matomo_troubleshooting_action_clear_matomo_cache';
	const TROUBLESHOOT_ARCHIVE_NOW = 'matomo_troubleshooting_action_archive_now';

	private $validTabs = array( 'troubleshooting' );

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	private function execute_troubleshoot_if_needed() {
		if ( ! empty( $_POST )
		     && is_admin()
		     && check_admin_referer( self::NONCE_NAME )
		     && current_user_can( Capabilities::KEY_SUPERUSER ) ) {

			if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_ARCHIVE_NOW ] ) ) {
				Bootstrap::do_bootstrap();
				$scheduled_tasks = new ScheduledTasks( $this->settings );
				$scheduled_tasks->archive( $force = true );
			}

			if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_CLEAR_MATOMO_CACHE ] ) ) {
				$paths = new Paths();
				$paths->clear_cache_dir();
				// we first delete the cache dir manually just in case there's something
				// going wrong with matomo and bootstrapping would not even be possible.
				Bootstrap::do_bootstrap();
				Filesystem::deleteAllCacheOnUpdate();
			}

			if ( ! $this->settings->is_network_enabled() || ! is_network_admin() ) {
				if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_SYNC_USERS ] ) ) {
					$sync = new UserSync();
					$sync->sync_current_users();
				}
				if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_SYNC_SITE ] ) ) {
					$sync = new SiteSync( $this->settings );
					$sync->sync_current_site();
				}
			}
			if ( $this->settings->is_network_enabled() ) {
				if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_SYNC_ALL_SITES ] ) ) {
					$sync = new SiteSync( $this->settings );
					$sync->sync_all();
				}
				if ( ! empty( $_POST[ SystemReport::TROUBLESHOOT_SYNC_ALL_USERS ] ) ) {
					$sync = new UserSync();
					$sync->sync_all();
				}
			}
		}
	}

	public function show() {
		$this->execute_troubleshoot_if_needed();

		$settings = $this->settings;

		$active_tab = '';
		if ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $this->validTabs, true ) ) {
			$active_tab = $_GET['tab'];
		}

		$tables = array();
		if ( empty( $active_tab ) ) {
			$tables = array(
				array( 'title' => 'Matomo', 'rows' => $this->get_matomo_info(), 'has_comments' => true ),
				array( 'title' => 'WordPress', 'rows' => $this->get_wordpress_info() ),
				array( 'title' => 'WordPress Plugins', 'rows' => $this->get_plugins_info() ),
				array( 'title' => 'Server', 'rows' => $this->get_server_info() ),
			);
		}

		include_once( dirname( __FILE__ ) . '/views/systemreport.php' );
	}

	private function check_file_exists_and_writable( $rows, $path_to_check, $title ) {
		$file_exists   = file_exists( $path_to_check );
		$file_readable = is_readable( $path_to_check );
		$file_writable = is_writable( $path_to_check );
		$comment       = '"' . $path_to_check . '"';
		if ( ! $file_exists ) {
			$comment .= ' ' . $title . ' does not exist.';
		}
		if ( ! $file_readable ) {
			$comment .= ' ' . $title . ' is not readable.';
		}
		if ( ! $file_writable ) {
			$comment .= ' ' . $title . ' is not writable.';
		}

		$rows[] = array(
			'name'    => $title . ' exists and is writable',
			'value'   => $file_exists && $file_readable && $file_writable ? 'Yes' : 'No',
			'comment' => $comment
		);

		return $rows;
	}

	private function get_matomo_info() {
		$rows = array();

		$plugin_data = get_plugin_data( MATOMO_ANALYTICS_FILE, $markup = false, $translate = false );

		$rows[] = array( 'name' => 'Matomo WordPress Version', 'value' => $plugin_data['Version'], 'comment' => '' );

		$paths            = new Paths();
		$upload_dir       = $paths->get_upload_base_dir();
		$path_config_file = $upload_dir . '/' . MATOMO_CONFIG_PATH;
		$rows             = $this->check_file_exists_and_writable( $rows, $path_config_file, 'Config' );

		$path_tracker_file = $upload_dir . '/matomo.js';
		$rows              = $this->check_file_exists_and_writable( $rows, $path_tracker_file, 'JS Tracker' );

		$rows[] = array(
			'name'    => 'Plugin directories',
			'value'   => ! empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? 'Yes' : 'No',
			'comment' => ! empty( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) ? json_encode( $GLOBALS['MATOMO_PLUGIN_DIRS'] ) : ''
		);

		$tmp_dir = $paths->get_tmp_dir();

		$rows[] = array(
			'name'    => 'Tmp directory writable',
			'value'   => is_writable( $tmp_dir ),
			'comment' => $tmp_dir
		);

		try {
			Bootstrap::do_bootstrap();
			/** @var DiagnosticService $service */
			$service = StaticContainer::get( \Piwik\Plugins\Diagnostics\DiagnosticService::class );
			$report  = $service->runDiagnostics();

		} catch ( \Exception $e ) {
			$rows[] = array(
				'name'    => 'Matomo System Check',
				'value'   => 'Failed to run, please open the system check in Matomo',
				'comment' => ''
			);

			return $rows;
		}

		$rows[] = array(
			'name'    => 'Matomo Version',
			'value'   => \Piwik\Version::VERSION,
			'comment' => ''
		);

		$rows[] = array(
			'section' => 'Endpoints',
		);

		$rows[] = array(
			'name'    => 'Matomo JavaScript Tracker URL',
			'value'   => '',
			'comment' => $paths->get_js_tracker_url_in_matomo_dir()
		);

		$rows[] = array(
			'name'    => 'Matomo JavaScript Tracker - WP Rest API',
			'value'   => '',
			'comment' => $paths->get_js_tracker_rest_api_endpoint()
		);

		$rows[] = array(
			'name'    => 'Matomo HTTP Tracking API',
			'value'   => '',
			'comment' => $paths->get_tracker_api_url_in_matomo_dir()
		);

		$rows[] = array(
			'name'    => 'Matomo HTTP Tracking API - WP Rest API',
			'value'   => '',
			'comment' => $paths->get_tracker_api_rest_api_endpoint()
		);

		$rows[] = array(
			'section' => 'Crons',
		);

		$scheduled_tasks = new ScheduledTasks( $this->settings );
		$all_events = $scheduled_tasks->get_all_events();

		$rows[] = array(
			'name'    => 'Server time',
			'value'   => $this->convert_time_to_date(time(), false),
			'comment' => ''
		);

		$rows[] = array(
			'name'    => 'Blog time',
			'value'   => $this->convert_time_to_date(time(), true),
			'comment' => 'Below dates are shown in blog timezone'
		);

		foreach ($all_events as $event_name => $event_config) {
			$last_run_before = $scheduled_tasks->get_last_time_before_cron($event_name);
			$last_run_after = $scheduled_tasks->get_last_time_after_cron($event_name);

			$next_scheduled = wp_next_scheduled($event_name);

			$comment  = ' Last started: ' . $this->convert_time_to_date($last_run_before, true, true) . '.';
			$comment .= ' Last ended: ' . $this->convert_time_to_date($last_run_after, true, true) . '.';
			$comment .= ' Interval: ' . $event_config['interval'];

			$rows[] = array(
				'name'    => $event_config['name'],
				'value'   => 'Next run: ' . $this->convert_time_to_date($next_scheduled, true, true),
				'comment' => $comment
			);

		}

		$rows[] = array(
			'section' => 'Mandatory checks',
		);

		$rows = $this->add_diagnostic_results( $rows, $report->getMandatoryDiagnosticResults() );

		$rows[] = array(
			'section' => 'Optional checks',
		);
		$rows   = $this->add_diagnostic_results( $rows, $report->getOptionalDiagnosticResults() );

		$cliMulti = new CliMulti();

		$rows[] = array(
			'name'    => 'Supports Async Archiving',
			'value'   => $cliMulti->supportsAsync(),
			'comment' => ''
		);

		return $rows;
	}

	private function convert_time_to_date($time, $in_blog_timezone, $print_diff = false)
	{
		if (empty($time)) {
			return 'Unknown';
		}

		$date = date( 'Y-m-d H:i:s', $time );

		if ($in_blog_timezone) {
			$date = get_date_from_gmt( $date, 'Y-m-d H:i:s' );
		}

		if ($print_diff && class_exists('\Piwik\MetricsFormatter')) {
			$date .= ' (' . MetricsFormatter::getPrettyTimeFromSeconds($time - time(), true, false, true ) . ')';
		}

		return $date;
	}

	private function add_diagnostic_results( $rows, $results ) {
		foreach ( $results as $result ) {
			$comment = '';
			if ( $result->getStatus() !== DiagnosticResult::STATUS_OK ) {
				foreach ( $result->getItems() as $item ) {
					if ( $item->getComment() ) {
						$comment .= $item->getComment();
					}
				}
			}
			$rows[] = array(
				'name'       => $result->getLabel(),
				'value'      => $result->getStatus() . ' ' . $result->getLongErrorMessage(),
				'comment'    => $comment,
				'is_warning' => $result->getStatus() === DiagnosticResult::STATUS_WARNING,
				'is_error'   => $result->getStatus() === DiagnosticResult::STATUS_ERROR
			);
		}

		return $rows;
	}

	private function get_wordpress_info() {
		global $wpdb;

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
		$rows[] = array( 'name' => 'Home URL', 'value' => home_url() );
		$rows[] = array( 'name' => 'Site URL', 'value' => site_url() );
		$rows[] = array( 'name' => 'WordPress Version', 'value' => get_bloginfo( 'version' ) );
		$rows[] = array( 'name' => 'Number of blogs', 'value' => $num_blogs );
		$rows[] = array( 'name' => 'Multisite Enabled', 'value' => $is_multi_site );
		$rows[] = array( 'name' => 'Network Enabled', 'value' => $is_network_enabled );
		$rows[] = array( 'name' => 'Debug Mode Enabled', 'value' => defined( 'WP_DEBUG' ) && WP_DEBUG );
		$rows[] = array( 'name' => 'Cron Enabled', 'value' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON );
		$rows[] = array( 'name' => 'DB Prefix', 'value' => $wpdb->prefix );

		return $rows;
	}

	private function get_server_info() {
		global $wpdb;
		$rows = array();

		$rows[] = array( 'name' => 'Server Info', 'value' => $_SERVER['SERVER_SOFTWARE'] );
		$rows[] = array( 'name' => 'PHP Version', 'value' => phpversion() );
		$rows[] = array( 'name' => 'MySQL Version', 'value' => ! empty( $wpdb->is_mysql ) ? $wpdb->db_version() : '' );
		$rows[] = array( 'name' => 'Timezone', 'value' => date_default_timezone_get() );
		$rows[] = array( 'name' => 'Locale', 'value' => get_locale() );
		$rows[] = array( 'name' => 'Memory Limit', 'value' => max( WP_MEMORY_LIMIT, @ini_get( 'memory_limit' ) ) );
		$rows[] = array( 'name' => 'Time', 'value' => time() );

		$rows[] = array( 'name' => 'Max Execution Time', 'value' => ini_get( 'max_execution_time' ) );
		$rows[] = array( 'name' => 'Max Post Size', 'value' => ini_get( 'post_max_size' ) );
		$rows[] = array( 'name' => 'Max Upload Size', 'value' => wp_max_upload_size() );
		$rows[] = array( 'name' => 'Max Input Vars', 'value' => ini_get( 'max_input_vars' ) );

		if ( function_exists( 'curl_version' ) ) {
			$curl_version = curl_version();
			$curl_version = $curl_version['version'] . ', ' . $curl_version['ssl_version'];
			$rows[]       = array( 'name' => 'Curl Version', 'value' => $curl_version );
		}

		return $rows;
	}

	private function get_plugins_info() {
		$rows       = array();
		$mu_plugins = get_mu_plugins();

		if ( ! empty( $mu_plugins ) ) {

			$rows[] = array(
				'section' => 'MU Plugins',
			);

			foreach ( $mu_plugins as $mu_pin ) {
				$rows[] = array( 'name' => $mu_pin['Name'], 'value' => $mu_pin['Version'] );
			}

			$rows[] = array(
				'section' => 'Plugins',
			);
		}

		$plugins = get_plugins();

		foreach ( $plugins as $plugin ) {
			$rows[] = array( 'name' => $plugin['Name'], 'value' => $plugin['Version'] );
		}

		return $rows;
	}


}
