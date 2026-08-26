<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
 */

trait MatomoMockDbServerTest {

	/**
	 * @var mixed
	 */
	private $original_global_wpdb;

	private function remember_global_wpdb() {
		$this->original_global_wpdb = isset( $GLOBALS['wpdb'] ) ? $GLOBALS['wpdb'] : null;
	}

	private function restore_global_wpdb() {
		$GLOBALS['wpdb'] = $this->original_global_wpdb;
	}

	/**
	 * Replaces $wpdb with one reporting the given versions.
	 *
	 * @param string $server_info what db_server_info() should return
	 * @param string $db_version  what db_version() should return
	 * @return object
	 */
	private function set_fake_db( $server_info, $db_version ) {
		$fake              = $this->get_mock_wpdb();
		$fake->is_mysql    = true;
		$fake->server_info = $server_info;
		$fake->version     = $db_version;
		$GLOBALS['wpdb']   = $fake;
		return $fake;
	}

	private function get_mock_wpdb() {
		return new class( $this->original_global_wpdb ) {
			private $original_db;

			public $is_mysql = true;

			public $server_info = '';

			public $version = '';

			public function __construct( $original_db ) {
				$this->original_db = $original_db;
			}

			public function db_server_info() {
				return $this->server_info;
			}

			public function db_version() {
				return $this->version;
			}

			public function __call( $name, $arguments ) {
				return call_user_func_array( [ $this->original_db, $name ], $arguments );
			}

			public function __get( $name ) {
				return $this->original_db->$name;
			}

			public function __set( $name, $value ) {
				$this->original_db->$name = $value;
			}
		};
	}

	private function get_mock_wpdb_without_server_info() {
		return new class() {
			public $is_mysql = true;
			public $version  = '';

			public function db_version() {
				return $this->version;
			}
		};
	}
}
