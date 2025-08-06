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

		$this->restore_db_snapshot();
	}

	protected function assume_admin_page() {
		set_current_screen( 'edit.php' );
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

			wpmu_delete_blog( $blog['blog_id'] );
		}
	}

	/**
	 * @return string
	 */
	protected function get_type_attribute() {
		$type = '';
		if ( function_exists( 'wp_get_inline_script_tag' ) && ! is_admin() && ! current_theme_supports( 'html5', 'script' ) ) {
			$type = 'type="text/javascript"';
		}
		return $type;
	}

	private function snapshot_db_data() {
		global $wpdb;

		if ( ! empty( self::$initial_table_data ) ) {
			return;
		}

		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_A );
		foreach ( $tables as $row ) {
			$table                              = reset( $row );
			self::$initial_table_data[ $table ] = $wpdb->get_results( "SELECT * FROM `$table`", ARRAY_A );
		}
	}

	private function restore_db_snapshot() {
		global $wpdb, $table_prefix;

		$tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_A );
		foreach ( $tables as $row ) {
			$table = reset( $row );
			$wpdb->query( "TRUNCATE `$table`" );

			$rows = ! empty( self::$initial_table_data[ $table ] ) ? self::$initial_table_data[ $table ] : [];
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

					throw new \Exception( $msg );
				}

				return $result;
			}

			public function &__get( $name ) {
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
				getenv( 'WORDPRESS_VERSION' ) !== 'latest'
				&& getenv( 'WORDPRESS_VERSION' ) !== 'trunk'
				&& version_compare( getenv( 'WORDPRESS_VERSION' ), '6.4', '<' )
			);
	}
}
