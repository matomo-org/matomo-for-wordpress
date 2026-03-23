<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\AbTest;

use InnoCraft\Experiments\Storage\StorageInterface;

/**
 * Stores experiment information in a WP option.
 */
class AbTestStorage implements StorageInterface {

	const OPTION_NAME = 'matomo_ab_tests_status';

	/**
	 * @var AbTestStorage|null
	 */
	private static $global_ab_test_storage = null;

	/**
	 * @var array|null
	 */
	private $data = null;

	public function get( $namespace, $key ) {
		$this->load();

		$whole_key = $namespace . '.' . $key;
		return isset( $this->data[ $whole_key ] ) ? $this->data[ $whole_key ] : null;
	}

	public function set( $namespace, $key, $value ) {
		$this->load();

		$whole_key = $namespace . '.' . $key;

		$this->data[ $whole_key ] = $value;
		$this->save();
	}

	public function get_active_ab_tests() {
		$prefix_length = strlen( 'experiment.' );

		$active_experiments = [];
		foreach ( $this->data as $key => $variation_name ) {
			if ( 'experiment.' !== substr( $key, 0, $prefix_length ) ) {
				continue;
			}

			$experiment_name                        = substr( $key, $prefix_length );
			$active_experiments[ $experiment_name ] = $variation_name;
		}
		return $active_experiments;
	}

	private function load() {
		if ( null !== $this->data ) {
			return $this->data;
		}

		$data = get_option( self::OPTION_NAME );
		if ( ! is_array( $data ) ) {
			$data = [];
		}
		return $data;
	}

	private function save() {
		update_option( self::OPTION_NAME, $this->data );
	}

	public static function get_global_instance() {
		if ( empty( self::$global_ab_test_storage ) ) {
			self::$global_ab_test_storage = new AbTestStorage();
		}

		return self::$global_ab_test_storage;
	}
}
