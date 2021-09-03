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

class Roles {
	const OPTION_SETUP_NAME = 'roles-setup';
	const ROLE_PREFIX       = 'matomo_';
	const ROLE_VIEW         = 'matomo_view_role';
	const ROLE_WRITE        = 'matomo_write_role';
	const ROLE_ADMIN        = 'matomo_admin_role';
	const ROLE_SUPERUSER    = 'matomo_superuser_role';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_action( 'init', [ $this, 'add_roles' ] );
	}

	public function get_available_roles_for_configuration() {
		global $wp_roles;
		$is_network_enabled = $this->settings->is_network_enabled();
		$roles              = [];

		foreach ( $wp_roles->role_names as $role_name => $name ) {
			if ( ! $is_network_enabled && 'administrator' === $role_name ) {
				// when multi site, then we consider "administrator" just a regular role and not a super user
				// when not multi site, administrator is automatically the super user
				continue;
			}

			if ( $this->is_matomo_role( $role_name ) ) {
				// a matomo capability which we don't want to change
				continue;
			}

			$roles[ $role_name ] = $name;
		}

		return $roles;
	}

	public function is_matomo_role( $role_name ) {
		return strpos( $role_name, self::ROLE_PREFIX ) === 0;
	}

	public function get_matomo_roles() {
		return [
			self::ROLE_VIEW      => [
				'name'       => 'Matomo View',
				'defaultCap' => Capabilities::KEY_VIEW,
			],
			self::ROLE_WRITE     => [
				'name'       => 'Matomo Write',
				'defaultCap' => Capabilities::KEY_WRITE,
			],
			self::ROLE_ADMIN     => [
				'name'       => 'Matomo Admin',
				'defaultCap' => Capabilities::KEY_ADMIN,
			],
			self::ROLE_SUPERUSER => [
				'name'       => 'Matomo Super User',
				'defaultCap' => Capabilities::KEY_SUPERUSER,
			],
		];
	}

	public function add_roles() {
		if ( ! $this->has_set_up_roles() ) {
			foreach ( $this->get_matomo_roles() as $role_name => $config ) {
				add_role( $role_name, $config['name'], [ $config['defaultCap'] => true ] );
			}
			$this->mark_roles_set_up();
		}
	}

	private function mark_roles_set_up() {
		update_option( Settings::OPTION_PREFIX . self::OPTION_SETUP_NAME, 1, 1 );
	}

	private function has_set_up_roles() {
		return (bool) get_option( Settings::OPTION_PREFIX . self::OPTION_SETUP_NAME );
	}

	public function uninstall() {
		foreach ( $this->get_matomo_roles() as $role_name => $role ) {
			remove_role( $role_name );
		}
		Uninstaller::uninstall_options( Settings::OPTION_PREFIX . self::OPTION_SETUP_NAME );
	}
}
