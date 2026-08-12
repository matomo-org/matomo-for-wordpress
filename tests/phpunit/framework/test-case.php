<?php
/**
 * @package matomo
 */

require_once __DIR__ . '/fixture/test-wordpress-fixture.php';

/**
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 */
class MatomoUnit_TestCase extends WP_UnitTestCase {

	/**
	 * @var MatomoUnit_WordPress_Fixture
	 */
	protected $wordpress_fixture;

	protected static $initial_table_data = [];

	private $original_wpdb = null;

	protected $overwrite_wpdb = true;

	protected $tracker_user;

	protected $application_password;

	/**
	 * The ROLLBACK executed by WP_UnitTestCase sometimes does not rollback to the correct
	 * state, which causes succeeding tests to fail. (Specifically, it can revert to
	 * a state where multiple blogs exist in the blogs table, but no other tables exist).
	 * I am unable to find the reason why the rollback fails, but disabling
	 * transactions entirely and manually dropping and refilling tables to get to a clean
	 * state seems to fix things.
	 */
	public function start_transaction() {
		$this->snapshot_db_data();
	}

	public function setUp(): void {
		parent::setUp();

		if ( $this->overwrite_wpdb ) {
			$this->overwrite_wpdb();
		}

		$this->set_ajax_die_handler();

		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}

		$this->wordpress_fixture = new MatomoUnit_WordPress_Fixture();
		$this->wordpress_fixture->set_up();
	}

	public function tearDown(): void {
		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}

		$this->wordpress_fixture->tear_down();

		$this->remove_ajax_die_handler();

		if ( $this->overwrite_wpdb ) {
			$this->restore_wpdb();
		}

		parent::tearDown();

		self::restore_db_snapshot();
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
	}

	protected function skip_if_old_wordpress() {
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '<' ) ) {
			$this->markTestSkipped( 'WordPress version does not support application passwords.' );
		}
	}

	protected function create_set_super_admin() {
		$logger = new \WpMatomo\Logger();
		$logger->log( 'creating super admin' );
		$id = self::factory()->user->create();

		wp_set_current_user( $id );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			grant_super_admin( $id );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		} else {
			$user->add_role( 'administrator' );
			$user->add_role( \WpMatomo\Roles::ROLE_SUPERUSER );
			$user->add_cap( \WpMatomo\Capabilities::KEY_SUPERUSER );
		}

		return $id;
	}

	protected function assert_tracking_response( $tracking_response ) {
		$this->assertEquals( $this->get_expected_tracking_response(), $tracking_response );
	}

	protected function assert_not_tracking_response( $tracking_response ) {
		$this->assertNotEquals( $this->get_expected_tracking_response(), $tracking_response );
	}

	protected function make_local_tracker( $date_time, $set_auth = false ) {
		\WpMatomo\Bootstrap::do_bootstrap();

		include_once __DIR__ . '/test-local-tracker.php';
		$site     = new \WpMatomo\Site();
		$paths    = new \WpMatomo\Paths();
		$endpoint = $paths->get_tracker_api_rest_api_endpoint();
		$tracker  = new MatomoLocalTracker( $site->get_current_matomo_site_id(), $endpoint );

		$tracker->setForceVisitDateTime( $date_time );
		$tracker->setIp( '156.5.3.2' );
		// Optional tracking
		$tracker->setUserAgent( 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.2.6) Gecko/20100625 Firefox/3.6.6 (.NET CLR 3.5.30729)' );
		$tracker->setBrowserLanguage( 'fr' );
		$tracker->setLocalTime( '12:34:06' );
		$tracker->setResolution( 1024, 768 );
		$tracker->setBrowserHasCookies( true );
		$tracker->setPlugins( true, true, false );

		if ( $set_auth ) {
			$this->create_user_for_tracker();
			$tracker->setTokenAuth( 'testtesttest' ); // ignored
			$tracker->setExtraServerVar( 'PHP_AUTH_USER', $this->tracker_user );
			$tracker->setExtraServerVar( 'PHP_AUTH_PW', $this->application_password );
		}

		return $tracker;
	}

	protected function create_user_for_tracker() {
		if ( isset( $this->tracker_user ) ) {
			return;
		}

		$user_id = self::factory()->user->create(
			array(
				'role' => 'administrator',
			)
		);
		wp_set_current_user( $user_id );
		$user_login = wp_get_current_user()->user_login;

		$this->assertNotEmpty( $user_login );

		$sync = new \WpMatomo\User\Sync();
		$sync->sync_all();

		$user_model = new \Piwik\Plugins\UsersManager\Model();
		$this->assertNotEmpty( $user_model->getUser( \WpMatomo\User::get_matomo_user_login( $user_id ) ) );

		\Piwik\Tracker\TrackerConfig::setConfigValue( 'allow_wp_app_password_auth', 1 );

		// add application password
		// NOTE: we don't skip all the tests here to make sure the auth code
		// works when application password functions do not exist
		if ( version_compare( getenv( 'WORDPRESS_VERSION' ), '5.6', '>=' ) ) {
			add_filter( 'wp_is_application_passwords_available', '__return_true' );

			$request = new WP_REST_Request( 'POST', '/wp/v2/users/me/application-passwords' );
			$request->set_param( 'name', 'test' );
			$response = rest_get_server()->dispatch( $request );

			$response_data        = $response->get_data();
			$application_password = $response_data['password'];

			$this->application_password = $application_password;
		}

		$this->tracker_user = $user_login;
	}

	private function get_expected_tracking_response() {
		$trans_gif_64 = 'R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$expected_response = base64_decode( $trans_gif_64 );
		return $expected_response;
	}

	private function delete_extraneous_blogs() {
		global $wpdb;

		while ( ms_is_switched() ) {
			restore_current_blog();
		}

		$blogs = $wpdb->get_results( 'SELECT blog_id, deleted FROM ' . $wpdb->blogs . ' ORDER BY blog_id', ARRAY_A );
		foreach ( $blogs as $blog ) {
			if ( 1 === (int) $blog['deleted'] || 1 === (int) $blog['blog_id'] ) {
				continue;
			}

			$this->delete_matomo_upload_dir( $blog['blog_id'] );

			wpmu_delete_blog( $blog['blog_id'] );
		}
	}

	/**
	 * @param int $blog_id
	 */
	private function delete_matomo_upload_dir( $blog_id ) {
		switch_to_blog( $blog_id );

		try {
			// removes the blog's matomo upload and cache dirs, and nothing else
			( new \WpMatomo\Paths() )->uninstall();
			// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		} catch ( \Exception $e ) {
			// ignore; a blog we could not clean up must not stop the rest from being deleted
		}

		restore_current_blog();
	}

	/**
	 * @return string
	 */
	protected function get_type_attribute() {
		if (
			function_exists( 'wp_get_inline_script_tag' )
			&& ! is_admin()
			&& ! current_theme_supports( 'html5', 'script' )
			&& getenv( 'WORDPRESS_VERSION' ) !== 'trunk'
			&& version_compare( getenv( 'WORDPRESS_VERSION' ), '6.4', '<' )
		) {
			return ' type="text/javascript"';
		}

		if (
			getenv( 'WORDPRESS_VERSION' ) !== 'trunk'
			&& getenv( 'WORDPRESS_VERSION' ) !== 'latest'
			&& version_compare( getenv( 'WORDPRESS_VERSION' ), '5.2', '<=' )
		) {
			return ' '; // in these versions, there is a space before the end of the tag, ie, '<script >'
		}

		return '';
	}

	private function snapshot_db_data() {
		if ( ! empty( self::$initial_table_data ) ) {
			return;
		}

		self::$initial_table_data = self::capture_db_snapshot();
	}

	/**
	 * @return array table name => [ 'create' => string|null, 'rows' => array ]
	 */
	protected static function capture_db_snapshot() {
		global $wpdb;

		$data = [];

		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_A );
		foreach ( $tables as $row ) {
			$table = reset( $row );

			$create = $wpdb->get_row( "SHOW CREATE TABLE `$table`", ARRAY_N );

			$data[ $table ] = [
				'create' => isset( $create[1] ) ? $create[1] : null,
				'rows'   => $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A ),
			];
		}

		return $data;
	}

	protected static function restore_db_snapshot() {
		global $wpdb, $table_prefix;

		$existing = [];
		foreach ( $wpdb->get_results( 'SHOW TABLES', ARRAY_A ) as $row ) {
			$existing[ reset( $row ) ] = true;
		}

		// create tables that are missing from the db
		foreach ( self::$initial_table_data as $table => $snapshot ) {
			if ( isset( $existing[ $table ] ) || empty( $snapshot['create'] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SHOW CREATE TABLE output, nothing to prepare
			$wpdb->query( $snapshot['create'] );
			$existing[ $table ] = true;
		}

		// then fill tables with snapshot data
		foreach ( array_keys( $existing ) as $table ) {
			$wpdb->query( "TRUNCATE `$table`" );

			$rows = ! empty( self::$initial_table_data[ $table ]['rows'] ) ? self::$initial_table_data[ $table ]['rows'] : [];
			if ( ! empty( $rows ) ) {
				foreach ( $rows as $data_row ) {
					$wpdb->insert( $table, $data_row );
				}
			} else {
				$is_multisite_table = preg_match( '/^' . preg_quote( $table_prefix, '/' ) . '\d+_/', $table );
				if ( $is_multisite_table ) {
					// WordPress will only initialize a site if the site table for it does not exist
					// so we have to drop these if present, not just truncate.
					$wpdb->query( "DROP TABLE `$table`" );
				}
			}
		}
	}

	private function overwrite_wpdb() {
		global $wpdb;

		$this->original_wpdb = $wpdb;

		$wpdb = new class( $this->original_wpdb ) {
			private $original_wpdb;

			private $use_mysqli = true; // see class-wpdb.php

			public function __construct( $original_wpdb ) {
				$this->original_wpdb = $original_wpdb;
			}

			public function __call( $name, $arguments ) {
				global $EZSQL_ERROR;

				$original_error_count = $EZSQL_ERROR ? count( $EZSQL_ERROR ) : 0;

				$result = call_user_func_array( [ $this->original_wpdb, $name ], $arguments );

				$error_count = $EZSQL_ERROR ? count( $EZSQL_ERROR ) : 0;

				if ( ! $this->original_wpdb->suppress_errors
					&& $original_error_count !== $error_count
				) {
					$error_info = end( $EZSQL_ERROR );

					$msg = sprintf(
						"%s [%s]\n%s\n",
						'WordPress database error:',
						$error_info['error_str'],
						$error_info['query']
					);

					// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					throw new \Exception( $msg );
				}

				return $result;
			}

			public function &__get( $name ) {
				if ( 'use_mysqli' === $name ) {
					return $this->use_mysqli;
				}
				return $this->original_wpdb->$name;
			}

			public function __set( $name, $value ) {
				$this->original_wpdb->$name = $value;
			}

			public function __isset( $name ) {
				return isset( $this->original_wpdb->$name );
			}

			public function __unset( $name ) {
				unset( $this->original_wpdb->$name );
			}
		};
	}

	private function restore_wpdb() {
		global $wpdb;
		$wpdb = $this->original_wpdb;
	}

	protected function get_hook_count( $hook_name ) {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook_name ] ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $wp_filter[ $hook_name ]->callbacks as $entries_by_priority ) {
			$count += count( $entries_by_priority );
		}
		return $count;
	}

	private function set_ajax_die_handler() {
		add_filter( 'wp_die_ajax_handler', [ $this, 'get_wp_die_handler' ], 1, 1 );
	}

	private function remove_ajax_die_handler() {
		remove_filter( 'wp_die_ajax_handler', [ $this, 'get_wp_die_handler' ], 1, 1 );
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	protected function doing_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
	}

	protected function stopped_doing_ajax() {
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	public function assert_event_not_scheduled( $event_name ) {
		$this->assert_event_scheduled( $event_name, 0 );
	}

	public function assert_event_scheduled( $event_name, $times = 1 ) {
		$events = $this->get_events_scheduled( $event_name );
		$this->assertCount( $times, $events );
	}

	public function get_events_scheduled( $event_name ) {
		$result = [];

		$cron = _get_cron_array();
		foreach ( $cron as $cronhooks ) {
			if ( isset( $cronhooks[ $event_name ] ) ) {
				$result = array_merge( $result, $cronhooks[ $event_name ] );
			}
		}

		return $result;
	}

	public function execute_scheduled_event( $event_name, $execute_all = false ) {
		$events = $this->get_events_scheduled( $event_name );

		if ( ! $execute_all ) {
			$events         = [ reset( $events ) ];
			$rest_of_events = array_slice( $events, 1 );
		} else {
			$rest_of_events = [];
		}

		foreach ( $events as $event ) {
			do_action_ref_array( $event_name, $event['args'] );
		}

		_set_cron_array( $rest_of_events );
	}

	protected function is_wordpress_not_using_cdata_tags() {
		return getenv( 'WORDPRESS_VERSION' )
			&& (
				version_compare( getenv( 'WORDPRESS_VERSION' ), '6.4', '<' )
				|| getenv( 'WORDPRESS_VERSION' ) === 'latest'
				|| getenv( 'WORDPRESS_VERSION' ) === 'trunk'
				|| version_compare( getenv( 'WORDPRESS_VERSION' ), '7.0', '>=' )
			);
	}
}
