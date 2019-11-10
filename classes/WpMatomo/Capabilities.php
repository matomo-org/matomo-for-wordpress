<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

use WP_Roles;
use WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Capabilities {
	const KEY_NONE = 'none_matomo';

	/**
	 * @api
	 */
	const KEY_VIEW = 'view_matomo';

	/**
	 * @api
	 */
	const KEY_WRITE = 'write_matomo';

	/**
	 * @api
	 */
	const KEY_ADMIN = 'admin_matomo';

	/**
	 * @api
	 */
	const KEY_SUPERUSER = 'superuser_matomo';
	const KEY_STEALTH = 'stealth_matomo';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_action( 'wp_roles_init', array( $this, 'add_capabilities_to_roles' ) );
		add_filter( 'user_has_cap', array( $this, 'add_capabilities_to_user' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
	}

	/**
	 * Tests only
	 * @internal
	 */
	public function remove_hooks()
	{
		remove_action( 'wp_roles_init', array( $this, 'add_capabilities_to_roles' ) );
		remove_filter( 'user_has_cap', array( $this, 'add_capabilities_to_user' ), 10);
		remove_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10);
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( $cap === self::KEY_STEALTH ) {
			// a super admin is usually allowed all actions... unless we add do_not_allow
			if ( is_multisite() && is_super_admin() ) {
				$stealth = $this->settings->get_global_option( Settings::OPTION_KEY_STEALTH );
				if ( ! empty( $stealth['administrator'] ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		}

		if ( $cap === Menu::CAP_NOT_EXISTS
		     && is_multisite()
		     && is_super_admin() ) {
			$caps[] = 'do_not_allow'; // prevent matomo-analytics submenu to be shown
		}

		return $caps;
	}

	public function add_capabilities_to_user( $allcaps, $caps, $args, $user ) {
		if ( isset( $caps[0] ) ) {
			$cap_request = $caps[0];
			switch ( $cap_request ) {
				// ensure the Matomo capability inheritcance always works
				case Capabilities::KEY_SUPERUSER:
					if ( $this->has_super_user_capability( $allcaps ) ) {
						$allcaps[ $cap_request ] = true;
					}
					break;

				case Capabilities::KEY_VIEW:
				case Capabilities::KEY_WRITE:
				case Capabilities::KEY_ADMIN:
					if ( empty( $allcaps[ $cap_request ] ) ) {
						// when user has the above permission we also make sure to add all capabilites below... eg
						// when user has write... then we ensure the user also has the view capability
						if ( $this->has_any_higher_permission( $cap_request, $allcaps )
						     || $this->has_super_user_capability( $allcaps ) ) {
							$allcaps[ $cap_request ] = true;
						}
					}

					break;
			}
		}

		return $allcaps;
	}

	private function has_super_user_capability( $allcaps ) {
		if ( is_multisite() && $this->settings->is_network_enabled() ) {
			if ( is_super_admin() ) {
				// only network manager can be super user in this case
				return true;
			}
		} elseif ( ! empty( $allcaps['administrator'] ) || ( is_multisite() && is_super_admin() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param WP_Roles $roles
	 */
	public function add_capabilities_to_roles( $roles ) {
		$access  = $this->settings->get_global_option( Settings::OPTION_KEY_CAPS_ACCESS );
		$stealth = $this->settings->get_global_option( Settings::OPTION_KEY_STEALTH );

		if ( ! empty( $access ) && is_array( $access ) ) {
			foreach ( $access as $role_name => $cap ) {
				$role = $roles->get_role( $role_name );
				if ( $role ) {
					$role->capabilities[ $cap ] = true;
				}
			}
		}

		if ( ! empty( $stealth ) && is_array( $stealth ) ) {
			foreach ( $stealth as $role_name => $enabled ) {
				$role = $roles->get_role( $role_name );
				if ( $role && $enabled ) {
					$role->capabilities[ Capabilities::KEY_STEALTH ] = true;
				}
			}
		}

	}

	public function get_all_capabilities_sorted_by_highest_permission() {
		return array(
			self::KEY_SUPERUSER,
			self::KEY_ADMIN,
			self::KEY_WRITE,
			self::KEY_VIEW,
		);
	}

	protected function has_any_higher_permission( $cap_to_find, $allcaps ) {
		$all_caps = $this->get_all_capabilities_sorted_by_highest_permission();
		if ( ! in_array( $cap_to_find, $all_caps ) ) {
			return false;
		}

		foreach ( $all_caps as $cap ) {
			if ( array_key_exists( $cap, $allcaps ) && ! empty( $allcaps[ $cap ] ) ) {
				// eg if user has super user... then we return right away...
				return true;
			}
			if ( $cap === $cap_to_find ) {
				return false;
			}
		}

		return false;
	}

}
