<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

/**
 * Checks if the system requirements for Matomo core are met by the current system.
 *
 * This class is used while the plugin is still booting (see WpMatomo::is_safe_mode())
 * and on servers that may not be able to run the bundled Matomo at all. It must
 * stay parseable on the oldest PHP version the plugin supports and must
 * never load anything from app/.
 */
class MinimumRequirements {

	const REQUIRED_PHP_VERSION     = '8.1';
	const REQUIRED_MYSQL_VERSION   = '8.0';
	const REQUIRED_MARIADB_VERSION = '10.6';

	/**
	 * The first plugin version that actually requires the versions above. Until the
	 * plugin is updated to that version everything in this class is only used to warn
	 * users about the upcoming change.
	 */
	const ENFORCED_FROM_VERSION = '6.0.0';

	/**
	 * @var array|null
	 */
	private $db_server;

	/**
	 * @param string $plugin_version
	 * @return bool
	 */
	public function does_plugin_version_require_new_minimums( $plugin_version ) {
		if ( empty( $plugin_version ) ) {
			return false;
		}

		return version_compare( $plugin_version, self::ENFORCED_FROM_VERSION, '>=' );
	}

	/**
	 * @param string $plugin_version
	 * @param string $php_version
	 * @return bool
	 */
	public function can_this_system_run_plugin_version( $plugin_version, $php_version = PHP_VERSION ) {
		if ( ! $this->does_plugin_version_require_new_minimums( $plugin_version ) ) {
			return true;
		}

		return ! $this->get_unmet_requirements( $php_version );
	}

	/**
	 * @param string $php_version
	 * @return string[]
	 */
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
	 * Detects the database server this WordPress install is connected to.
	 *
	 * @return array an array with an `is_mariadb` bool and a `version` string. The version
	 *               is empty when it cannot be detected.
	 */
	public function get_db_server() {
		if ( isset( $this->db_server ) ) {
			return $this->db_server;
		}

		global $wpdb;

		if ( empty( $wpdb ) || empty( $wpdb->is_mysql ) ) {
			$this->db_server = [
				'is_mariadb' => false,
				'version'    => '',
			];

			return $this->db_server;
		}

		$server_info = method_exists( $wpdb, 'db_server_info' ) ? $wpdb->db_server_info() : '';
		$is_mariadb  = $server_info && false !== stripos( $server_info, 'mariadb' );

		if ( $is_mariadb && preg_match( '/(?:5\.5\.5-)?(\d+\.\d+\.\d+).*?mariadb/i', $server_info, $matches ) ) {
			$version = $matches[1];
		} else {
			// db_version() strips any suffix and returns the numeric version only.
			$version = (string) $wpdb->db_version();
		}

		$this->db_server = [
			'is_mariadb' => $is_mariadb,
			'version'    => $version,
		];

		return $this->db_server;
	}
}
