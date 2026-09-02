<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use WP_Roles;
use WpMatomo\Admin\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Capabilities extends Feature {

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
	const KEY_STEALTH   = 'stealth_matomo';

	/**
	 * Matomo has Role classes for view/write/admin, but superuser access is a flag on the user
	 * rather than a role, so there is no Matomo constant to reuse for it.
	 */
	const ROLE_SUPERUSER = 'superuser';

	/**
	 * @var Settings
	 */
	private $settings;

	public function __construct( $settings ) {
		$this->settings = $settings;
	}

	public function register_hooks() {
		add_action( 'wp_roles_init', [ $this, 'add_capabilities_to_roles' ] );
		add_filter( 'user_has_cap', [ $this, 'add_capabilities_to_user' ], 10, 4 );
		add_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10, 4 );
		add_filter( 'editable_roles', [ $this, 'remove_roles_the_current_user_may_not_grant' ] );
	}

	/**
	 * Tests only
	 *
	 * @internal
	 */
	public function remove_hooks() {
		remove_action( 'wp_roles_init', [ $this, 'add_capabilities_to_roles' ] );
		remove_filter( 'user_has_cap', [ $this, 'add_capabilities_to_user' ], 10 );
		remove_filter( 'map_meta_cap', [ $this, 'map_meta_cap' ], 10 );
		remove_filter( 'editable_roles', [ $this, 'remove_roles_the_current_user_may_not_grant' ] );
	}

	public function remove_roles_the_current_user_may_not_grant( $roles ) {
		// do not allow non-matomo-superusers from granting matomo superuser access to another user
		if ( isset( $roles[ Roles::ROLE_SUPERUSER ] ) && ! current_user_can( self::KEY_SUPERUSER ) ) {
			unset( $roles[ Roles::ROLE_SUPERUSER ] );
		}

		return $roles;
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		if ( self::KEY_STEALTH === $cap ) {
			// in multisite prevent super admin from having their tracking being filtered
			// a super admin is usually allowed all actions... unless we add do_not_allow
			if ( is_multisite() && is_super_admin( $user_id ) ) {
				$stealth = $this->settings->get_stealth_roles();
				if ( ! empty( $stealth['administrator'] ) ) {
					$caps[] = 'do_not_allow';
				}
			}
		}

		if ( Menu::CAP_NOT_EXISTS === $cap
			&& is_multisite()
			&& is_super_admin( $user_id ) ) {
			$caps[] = 'do_not_allow'; // prevent matomo-analytics submenu to be shown
		}

		return $caps;
	}

	public function add_capabilities_to_user( $allcaps, $caps, $args, $user ) {
		if ( isset( $caps[0] ) ) {
			$cap_request = $caps[0];
			switch ( $cap_request ) {
				// ensure the Matomo capability inheritcance always works
				case self::KEY_SUPERUSER:
					if ( $this->has_matomo_super_user_capability( $allcaps, $user ) ) {
						$allcaps[ $cap_request ] = true;
					}
					break;

				case self::KEY_VIEW:
				case self::KEY_WRITE:
				case self::KEY_ADMIN:
					if ( empty( $allcaps[ $cap_request ] ) ) {
						// when user has the above permission we also make sure to add all capabilites below... eg
						// when user has write... then we ensure the user also has the view capability
						if ( $this->has_any_higher_permission( $cap_request, $allcaps )
							|| $this->has_matomo_super_user_capability( $allcaps, $user ) ) {
							$allcaps[ $cap_request ] = true;
						}
					}

					break;
			}
		}

		return $allcaps;
	}

	/**
	 * Whether the user is entitled to Matomo super user access on the blog this request is for.
	 *
	 * An administrator administrates their blog, and every blog has a Matomo install of its own, so
	 * they are the superuser of it.
	 *
	 * In multisite this is decided a blog at a time: roles are held per blog and $allcaps carries
	 * the ones for the current blog, so an administrator of one blog is nothing on another. An
	 * administrator of the network is a super user on all of them.
	 *
	 * @param array    $allcaps
	 * @param \WP_User $user
	 *
	 * @return bool
	 */
	private function has_matomo_super_user_capability( $allcaps, $user ) {
		if ( ! empty( $allcaps['administrator'] ) ) {
			return true;
		}

		return is_multisite() && is_super_admin( $user->ID );
	}

	/**
	 * @param WP_Roles $roles
	 */
	public function add_capabilities_to_roles( $roles ) {
		$access  = ( new Access( $this->settings ) )->get_configured_permissions_for_roles();
		$stealth = $this->settings->get_stealth_roles();

		foreach ( $access as $role_name => $cap ) {
			$role = $roles->get_role( $role_name );
			if ( $role ) {
				$role->capabilities[ $cap ] = true;
			}
		}

		if ( ! empty( $stealth ) && is_array( $stealth ) ) {
			foreach ( $stealth as $role_name => $enabled ) {
				$role = $roles->get_role( $role_name );
				if ( $role && $enabled ) {
					$role->capabilities[ self::KEY_STEALTH ] = true;
				}
			}
		}
	}

	public function get_all_capabilities_sorted_by_highest_permission() {
		return array_keys( self::get_capability_role_map() );
	}

	/**
	 * The Matomo access each Matomo capability corresponds to, highest permission first.
	 *
	 * The values are Matomo role IDs (Piwik\Access\Role\Admin::ID and friends), spelled out
	 * literally because this map is reached from the user_has_cap filter, long before Matomo is
	 * bootstrapped and those classes can be loaded. WpMatomoCapabilitiesTest asserts they match.
	 *
	 * @return array<string, string>
	 */
	private static function get_capability_role_map() {
		return [
			self::KEY_SUPERUSER => self::ROLE_SUPERUSER,
			self::KEY_ADMIN     => 'admin',
			self::KEY_WRITE     => 'write',
			self::KEY_VIEW      => 'view',
		];
	}

	/**
	 * @param int|\WP_User $user
	 * @return string|null a Matomo role ID or self::ROLE_SUPERUSER, null when they are entitled to
	 *                     no access at all
	 */
	public static function get_highest_role_for_user( $user ) {
		foreach ( self::get_capability_role_map() as $capability => $role ) {
			if ( user_can( $user, $capability ) ) {
				return $role;
			}
		}

		return null;
	}

	/**
	 * @param string|null $role
	 * @return int
	 */
	public static function get_role_ranking( $role ) {
		$roles = array_reverse( array_values( self::get_capability_role_map() ) );

		$rank = array_search( $role, $roles, true );

		return false === $rank ? 0 : $rank + 1;
	}

	protected function has_any_higher_permission( $cap_to_find, $allcaps ) {
		$all_caps = $this->get_all_capabilities_sorted_by_highest_permission();
		if ( ! in_array( $cap_to_find, $all_caps, true ) ) {
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
