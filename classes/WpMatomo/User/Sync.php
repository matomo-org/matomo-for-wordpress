<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\User;

use Exception;
use Piwik\Access;
use Piwik\Auth\Password;
use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\Plugin;
use Piwik\Plugins\LanguagesManager\API;
use Piwik\Plugins\UsersManager;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Tracker\Cache as TrackerCache;
use WP_User;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Feature;
use WpMatomo\Logger;
use WpMatomo\Request;
use WpMatomo\ScheduledTasks;
use WpMatomo\Site;
use WpMatomo\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Sync extends Feature {

	/**
	 * actually allowed is 100 characters...
	 * but we do -5 to have some room to append `wp_`.$login.XYZ if needed
	 */
	const MAX_USER_NAME_LENGTH = 95;

	/**
	 * Above this many users on a blog, syncing them all is too much work for the request that
	 * happened to change one of them. The scheduled task does it instead.
	 */
	const MAX_INLINE_SYNC_USERS = 1000;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var User
	 */
	private $user;

	/**
	 * Blogs a user is being removed from in this request, as [ wp_user_id ][ blog_id ] => true.
	 * A single request can remove the same user from several blogs, see wpmu_delete_user().
	 *
	 * @var array
	 */
	private $pending_removals = [];

	public function __construct() {
		$this->logger = new Logger();
		$this->user   = new User();
	}

	public function register_hooks() {
		add_action( 'add_user_role', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'remove_user_role', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'add_user_to_blog', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'remove_user_from_blog', [ $this, 'on_remove_user_from_blog' ], $prio = 10, $args = 2 );
		add_action( 'clean_user_cache', [ $this, 'on_clean_user_cache' ], $prio = 10, $args = 1 );
		add_action( 'deleted_user_meta', [ $this, 'on_deleted_user_meta' ], $prio = 10, $args = 3 );
		add_action( 'deleted_user', [ $this, 'on_deleted_user' ], $prio = 10, $args = 1 );
		add_action( 'user_register', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'granted_super_admin', [ $this, 'on_super_admin_change' ], $prio = 10, $args = 1 );
		add_action( 'revoked_super_admin', [ $this, 'on_super_admin_change' ], $prio = 10, $args = 1 );
		add_action( 'update_option_WPLANG', [ $this, 'on_site_language_change' ], $prio = 10, $args = 0 );
		add_action( 'profile_update', [ $this, 'sync_maybe_background' ], $prio = 10, $args = 0 );

		foreach ( Site\Sync::RETURN_TO_SERVICE_ACTIONS as $blog_action ) {
			// must run after the site sync hook: the users are synced against the Matomo site that
			// is created during the site sync
			add_action( $blog_action, [ $this, 'on_blog_returned_to_service' ], $prio = Site\Sync::RETURN_TO_SERVICE_PRIORITY + 1, $args = 1 );
		}
	}

	/**
	 * Tests only
	 *
	 * @internal
	 */
	public function remove_hooks() {
		remove_action( 'add_user_role', [ $this, 'sync_current_users_1000' ], 10 );
		remove_action( 'remove_user_role', [ $this, 'sync_current_users_1000' ], 10 );
		remove_action( 'add_user_to_blog', [ $this, 'sync_current_users_1000' ], 10 );
		remove_action( 'remove_user_from_blog', [ $this, 'on_remove_user_from_blog' ], 10 );
		remove_action( 'clean_user_cache', [ $this, 'on_clean_user_cache' ], 10 );
		remove_action( 'deleted_user_meta', [ $this, 'on_deleted_user_meta' ], 10 );
		remove_action( 'deleted_user', [ $this, 'on_deleted_user' ], 10 );
		remove_action( 'user_register', [ $this, 'sync_current_users_1000' ], 10 );
		remove_action( 'granted_super_admin', [ $this, 'on_super_admin_change' ], 10 );
		remove_action( 'revoked_super_admin', [ $this, 'on_super_admin_change' ], 10 );
		remove_action( 'update_option_WPLANG', [ $this, 'on_site_language_change' ], 10 );
		remove_action( 'profile_update', [ $this, 'sync_maybe_background' ], 10 );
		foreach ( Site\Sync::RETURN_TO_SERVICE_ACTIONS as $blog_action ) {
			remove_action( $blog_action, [ $this, 'on_blog_returned_to_service' ], Site\Sync::RETURN_TO_SERVICE_PRIORITY + 1 );
		}
	}

	/**
	 * WordPress fires this action before it removes the user's capabilities, so we can't sync the
	 * user here, the access it has to the site would not be removed. Instead, we remember them
	 * and have flush_pending_removal_for_current_blog() do the actual re-syncing once the
	 * capabilities are gone.
	 *
	 * @param int $wp_user_id
	 * @param int $blog_id
	 */
	public function on_remove_user_from_blog( $wp_user_id, $blog_id ) {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		$blog_id = (int) $blog_id;
		$blog_id = $blog_id ? $blog_id : get_current_blog_id();

		$this->pending_removals[ (int) $wp_user_id ][ $blog_id ] = true;
	}

	/**
	 * Runs while WordPress is still switched to the blog the user was removed from, after their
	 * capabilities are gone. Other callers of clean_user_cache() (wp_insert_user(),
	 * add_user_to_blog(), ...) are ignored because they didn't queue anything.
	 *
	 * @param int $wp_user_id
	 */
	public function on_clean_user_cache( $wp_user_id ) {
		$this->flush_pending_removal_for_current_blog( $wp_user_id );
	}

	/**
	 * Required since remove_user_from_blog() only calls clean_user_cache() since WordPress 6.1.
	 * On older versions the capabilities meta deleted by WP_User::remove_all_caps(), so we have
	 * to try to sync from both places.
	 *
	 * Whichever of the two fires first does the sync, the other then finds nothing queued.
	 *
	 * @param array  $meta_ids
	 * @param int    $wp_user_id
	 * @param string $meta_key
	 */
	public function on_deleted_user_meta( $meta_ids, $wp_user_id, $meta_key ) {
		global $wpdb;

		$blog_prefix = $wpdb->get_blog_prefix();

		if ( $blog_prefix . 'capabilities' !== $meta_key && $blog_prefix . 'user_level' !== $meta_key ) {
			return;
		}

		$this->flush_pending_removal_for_current_blog( $wp_user_id );
	}

	/**
	 * Syncs a user queued by on_remove_user_from_blog(), if the blog WordPress is currently
	 * switched to is one they were queued for.
	 *
	 * @param int $wp_user_id
	 */
	private function flush_pending_removal_for_current_blog( $wp_user_id ) {
		$wp_user_id = (int) $wp_user_id;
		$blog_id    = get_current_blog_id();

		if ( empty( $this->pending_removals[ $wp_user_id ][ $blog_id ] ) ) {
			return;
		}

		// only this blog is done, the same user may still be queued for others
		unset( $this->pending_removals[ $wp_user_id ][ $blog_id ] );

		$this->sync_user_for_current_blog( $wp_user_id );
	}

	/**
	 * @param int $wp_user_id
	 */
	public function on_deleted_user( $wp_user_id ) {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		if ( get_userdata( $wp_user_id ) ) {
			// user still exists, do not delete user from matomo (edge case that can happen
			// on multisite installs)
			return;
		}

		try {
			$this->delete_matomo_user_for_current_blog( $wp_user_id );
		} catch ( Exception $e ) {
			// deleting a WordPress user must not fail because the Matomo cleanup did
			$this->logger->log_exception( 'user_sync', $e );
		}
	}

	/**
	 * @param int $blog_id
	 */
	public function on_blog_returned_to_service( $blog_id ) {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		// only one of the three flags was cleared, and the blog stays out of service while any
		// of the others is still set
		if ( Site::is_blog_out_of_service( $blog_id ) ) {
			return;
		}

		switch_to_blog( $blog_id );

		try {
			$this->sync_current_users_1000();
		} catch ( Exception $e ) {
			// restoring a blog must not fail because Matomo could not be synced for it
			$this->logger->log_exception( 'user_sync', $e );
		}

		restore_current_blog();
	}

	/**
	 * @param int $wp_user_id
	 */
	public function on_super_admin_change( $wp_user_id ) {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		if ( ! function_exists( 'is_multisite' ) || ! is_multisite() ) {
			return;
		}

		foreach ( get_sites( [ 'number' => 0 ] ) as $site ) {
			// a revoked super admin keeps their row on a blog skipped here, which is only safe
			// because authenticating downgrades it first
			if ( Site::is_blog_out_of_service( $site ) ) {
				continue;
			}

			switch_to_blog( $site->blog_id );

			try {
				$this->sync_user_for_current_blog( $wp_user_id );
			} catch ( Exception $e ) {
				// one blog failing must not stop the rest from being corrected
				$this->logger->log_exception( 'user_sync', $e );
			}

			restore_current_blog();
		}
	}

	/**
	 * Corrects a Matomo access row that grants the user more than their live WordPress capabilities
	 * do, before anything reads it.
	 *
	 * Only ever downgrades. A user promoted in WordPress still waits for a regular sync.
	 *
	 * @param int        $wp_user_id
	 * @param array|null $matomo_user the already fetched Matomo user row, to save a query
	 *
	 * @return bool whether the user was re-synced
	 */
	public function sync_user_if_access_exceeds_capabilities( $wp_user_id, $matomo_user = null ) {
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( ! $idsite ) {
			return false;
		}

		$matomo_login = User::get_matomo_user_login( $wp_user_id );
		if ( ! $matomo_login ) {
			return false; // nothing was ever persisted for this user
		}

		$wp_user = get_userdata( $wp_user_id );
		if ( empty( $wp_user ) ) {
			return false;
		}

		Bootstrap::do_bootstrap();

		$user_model = new Model();

		if ( null === $matomo_user ) {
			$matomo_user = $user_model->getUser( $matomo_login );
		}

		if ( empty( $matomo_user ) ) {
			return false;
		}

		$live_rank      = Capabilities::get_role_ranking( Capabilities::get_highest_role_for_user( $wp_user ) );
		$persisted_rank = $this->get_persisted_role_rank( $matomo_login, $matomo_user, $idsite );

		if ( $persisted_rank <= $live_rank ) {
			return false;
		}

		$this->sync_user_for_current_blog( $wp_user_id );

		return true;
	}

	/**
	 * @param string $matomo_login
	 * @param array  $matomo_user
	 * @param int    $idsite
	 *
	 * @return int
	 */
	private function get_persisted_role_rank( $matomo_login, $matomo_user, $idsite ) {
		if ( ! empty( $matomo_user['superuser_access'] ) ) {
			return Capabilities::get_role_ranking( Capabilities::ROLE_SUPERUSER );
		}

		$rows = Db::fetchAll(
			'SELECT access FROM ' . Common::prefixTable( 'access' ) . ' WHERE login = ? AND idsite = ?',
			[ $matomo_login, (int) $idsite ]
		);

		$rank = 0;
		foreach ( $rows as $row ) {
			$rank = max( $rank, Capabilities::get_role_ranking( $row['access'] ) );
		}

		return $rank;
	}

	/**
	 * @param int $wp_user_id
	 */
	private function sync_user_for_current_blog( $wp_user_id ) {
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( ! $idsite ) {
			return;
		}

		$wp_user = get_userdata( $wp_user_id );
		if ( empty( $wp_user ) ) {
			return;
		}

		Bootstrap::do_bootstrap();

		$user_model = new Model();

		Access::doAsSuperUser(
			function () use ( $user_model, $wp_user, $wp_user_id, $idsite ) {
				$mapped_matomo_login = $this->get_own_matomo_user_login( $wp_user_id );

				$has_access = (bool) $this->sync_user_access_for_site( $wp_user, $idsite, $user_model );
				if ( $has_access ) { // user has access
					return;
				}

				// user has no access but was never mapped originally, and thus has no matomo user
				if ( ! $mapped_matomo_login ) {
					return;
				}

				// user still exists in matomo but has no access to matomo when determined by
				// WP roles

				// user does not have super user access (otherwise, a login would have been returned above)
				$user_model->setSuperUserAccess( $mapped_matomo_login, false );

				// user may still have access to other sites, but if they don't, delete the user entirely
				if ( ! $user_model->getSiteAccessCount( $mapped_matomo_login ) ) {
					$this->delete_matomo_user( $user_model, $mapped_matomo_login );
				}
			}
		);

		$this->invalidate_tracker_cache( $idsite );
	}

	/**
	 * Returns the matomo login mapped to the given user, if and only if the matomo login
	 * is not currently mapped to another user. (should not normally happen unless in a
	 * corrupted state)
	 *
	 * @param int $wp_user_id
	 * @return string|null
	 */
	private function get_own_matomo_user_login( $wp_user_id ) {
		$matomo_login = User::get_matomo_user_login( $wp_user_id );

		if ( ! $matomo_login || $this->is_matomo_login_owned_by_other_wp_user( $matomo_login, $wp_user_id ) ) {
			return null;
		}

		return $matomo_login;
	}

	/**
	 * @param int $wp_user_id
	 */
	private function delete_matomo_user_for_current_blog( $wp_user_id ) {
		$matomo_login = $this->get_own_matomo_user_login( $wp_user_id );
		if ( ! $matomo_login ) {
			// either never mapped, or the login belongs to another WP user.
			// should not delete the matomo user in this case. instead we remove the
			// corrupted mapping.
			User::map_matomo_user_login( $wp_user_id, null );

			return;
		}

		Bootstrap::do_bootstrap();

		$user_model = new Model();

		Access::doAsSuperUser(
			function () use ( $user_model, $matomo_login ) {
				$this->delete_matomo_user( $user_model, $matomo_login );
			}
		);

		// in case a token somehow has been created for the user, invalidate the
		// tracker cache so it will not be used in the tracker
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( $idsite ) {
			$this->invalidate_tracker_cache( $idsite );
		}
	}

	/**
	 * Callers are responsible for being inside Access::doAsSuperUser().
	 *
	 * @param Model  $user_model
	 * @param string $matomo_login
	 */
	private function delete_matomo_user( $user_model, $matomo_login ) {
		$user_model->deleteUserOnly( $matomo_login );
		$user_model->deleteUserOptions( $matomo_login );
		$user_model->deleteUserAccess( $matomo_login );
	}

	public function sync_maybe_background() {
		global $pagenow;
		if ( is_admin() && 'users.php' === $pagenow ) {
			// eg for profile update we don't want to sync directly see #365 as it could cause issues with other plugins
			// if they eg alter `get_users` option
			wp_schedule_single_event( time() + 5, ScheduledTasks::EVENT_SYNC );
		} else {
			$this->sync_current_users_1000();
		}
	}

	public function on_site_language_change() {
		unset( $GLOBALS['locale'] ); // same thing that's done after saving in options.php

		$this->sync_current_users_1000();
	}

	public function sync_all() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			// number => 0 means no limit. WP_Site_Query defaults to 100, which would silently leave
			// every blog after that unsynced
			foreach ( get_sites( [ 'number' => 0 ] ) as $site ) {
				if ( Site::is_blog_out_of_service( $site ) ) {
					continue;
				}

				switch_to_blog( $site->blog_id );

				$idsite = Site::get_matomo_site_id( $site->blog_id );

				try {
					if ( $idsite ) {
						$users = $this->get_users( [ 'blog_id' => $site->blog_id ] );
						$this->sync_users( $users, $idsite );
					}
				} catch ( Exception $e ) {
					// we don't want to rethrow exception otherwise some other blogs might never sync
					$this->logger->log_exception( 'user_sync ', $e );
				}

				restore_current_blog();
			}
		} else {
			$this->sync_current_users();
		}
	}

	private function get_users( $options = [] ) {
		/** @var WP_User[] $users */
		$users = get_users( $options );

		$current_user = wp_get_current_user();
		if ( ! empty( $current_user ) && ! empty( $current_user->user_login ) ) {
			// refs https://github.com/matomo-org/matomo-for-wordpress/issues/365
			// some other plugins may under circumstances overwrite the get_users query and not return all users
			// as a result we would delete some users in the matomo users table. this way we make sure at least the current
			// user will be added and not deleted even if the list of users is not complete
			$found = false;
			foreach ( $users as $user ) {
				if ( $user->user_login === $current_user->user_login ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$users[] = $current_user;
			}
		}

		if ( is_multisite() ) {
			$super_admins = get_super_admins();
			if ( ! empty( $super_admins ) ) {
				foreach ( $super_admins as $super_admin ) {
					$found = false;
					foreach ( $users as $user ) {
						if ( $user->user_login === $super_admin ) {
							$found = true;
							break;
						}
					}
					if ( ! $found ) {
						$user = get_user_by( 'login', $super_admin );
						if ( ! empty( $user ) ) {
							$users[] = $user;
						}
					}
				}
			}
		}

		return $users;
	}

	public function sync_current_users() {
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( $idsite ) {
			$users = $this->get_users();
			$this->sync_users( $users, $idsite );
		}
	}

	/**
	 * similar method to sync_current_users which synchronise on the fly only if we have less than 1000 users.
	 * Otherwise it will be done by a background task
	 *
	 * @return void
	 * @see https://github.com/matomo-org/matomo-for-wordpress/issues/460
	 * @see Sync::sync_current_users()
	 */
	public function sync_current_users_1000() {
		if ( ! $this->is_sync_allowed_for_request() ) {
			return;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			// these hooks are not admin only, so this may run somewhere wp-admin/includes is not loaded
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! is_plugin_active( 'matomo/matomo.php' ) ) {
			// @see https://github.com/matomo-org/matomo-for-wordpress/issues/577
			return;
		}
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( $idsite ) {
			$num_users = count_users();
			$num_users = $num_users['total_users'];
			if ( $num_users < self::MAX_INLINE_SYNC_USERS ) {
				$users = $this->get_users();
				$this->sync_users( $users, $idsite );
			} else {
				// too expensive to sync in this request
				$this->logger->log( 'Deferring user sync to a scheduled task, since this blog has ' . $num_users . ' users' );

				wp_schedule_single_event( time() + 5, ScheduledTasks::EVENT_SYNC );
			}
		}
	}

	/**
	 * Sync all users. Make sure to always pass all sites that exist within a given site... you cannot just sync an individual
	 * user... we would delete all other users
	 *
	 * @param WP_User[]  $users
	 * @param int|string $idsite
	 */
	protected function sync_users( $users, $idsite ) {
		Bootstrap::do_bootstrap();

		$this->logger->log( 'Matomo will now sync ' . count( $users ) . ' users' );

		$logins_with_some_view_access = [ 'anonymous' ]; // may or may not exist... we don't want to delete this user though
		$user_model                   = new Model();

		// need to make sure we recreate new instance later with latest dependencies in case they changed
		API::unsetInstance();
		UsersManager\API::unsetInstance();

		foreach ( $users as $user ) {
			// todo if we used transactions we could commit it after a possibly new access has been added
			// to prevent UI preventing randomly saying no access between deleting and adding access

			try {
				if ( defined( 'MATOMO_PHPUNIT_TEST' ) && MATOMO_PHPUNIT_TEST ) {
					/**
					 * @internal tests only
					 * @param WP_User    $user
					 * @param int|string $idsite
					 */
					do_action( 'matomo_before_sync_user', $user, $idsite );
				}

				$matomo_login = $this->sync_user_access_for_site( $user, $idsite, $user_model );

				if ( $matomo_login ) {
					$logins_with_some_view_access[] = $matomo_login;

					$locale = get_user_locale( $user->ID );
					$lang   = self::get_matomo_lang_from_locale( $locale );
					if (
						! empty( $lang )
						&& Plugin\Manager::getInstance()->isPluginActivated( 'LanguagesManager' )
						&& Plugin\Manager::getInstance()->isPluginInstalled( 'LanguagesManager' )
						&& API::getInstance()->isLanguageAvailable( $lang )
					) {
						$user_lang_model = new \Piwik\Plugins\LanguagesManager\Model();
						$user_lang_model->setLanguageForUser( $matomo_login, $lang );
					}

					// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					if ( 1 != $idsite ) {
						// only needed if the actual site is not the default site... makes sure when they click in Matomo
						// UI on "Dashboard" that the correct site is being opened by default
						// eg if the linked site is actually idSite=2.
						Access::doAsSuperUser(
							function () use ( $matomo_login, &$idsite ) {
								try {
									UsersManager\API::getInstance()->setUserPreference(
										$matomo_login,
										UsersManager\API::PREFERENCE_DEFAULT_REPORT,
										$idsite
									);
								} catch ( Exception $e ) {
									// a preference is not worth failing the sync over, but it should not
									// disappear silently either
									$this->logger->log_exception( 'user_sync', $e );
								}
							}
						);
					}
				}
			} catch ( Exception $e ) {
				// one user sync failure must not abort the whole sync
				$this->logger->log_exception( 'user_sync', $e );

				// make sure this user for whom syncing failed is not deleted
				$login_to_keep = User::get_matomo_user_login( $user->ID );
				if ( $login_to_keep ) {
					$logins_with_some_view_access[] = $login_to_keep;
				}
			}
		}

		$logins_with_some_view_access = array_unique( $logins_with_some_view_access );
		$all_users                    = $user_model->getUsers( [] );
		foreach ( $all_users as $all_user ) {
			if ( ! in_array( $all_user['login'], $logins_with_some_view_access, true )
				&& ! empty( $all_user['login'] ) ) {
				try {
					Access::doAsSuperUser(
						function () use ( $user_model, $all_user ) {
							$this->delete_matomo_user( $user_model, $all_user['login'] );
						}
					);
					// the WP -> Matomo mapping is cleaned up via the UsersManager.deleteUser event
					// that deleteUserOnly() fires (see WordPress::onDeleteMatomoUser).
				} catch ( Exception $e ) {
					// do not abort entirely if a single delete fails
					$this->logger->log_exception( 'user_sync', $e );
				}
			}
		}

		$this->invalidate_tracker_cache( $idsite );
	}

	/**
	 * This plugin does not provide token auths to authenticate with, but in case an
	 * attacker is somehow able to create one, we want to make sure it can't be used,
	 * so after syncing, we clear the tracker cache.
	 *
	 * @param int|string $idsite
	 */
	private function invalidate_tracker_cache( $idsite ) {
		try {
			TrackerCache::deleteCacheWebsiteAttributes( $idsite );
		} catch ( Exception $e ) {
			// the sync itself is done, so a cache that could not be cleared must not fail the
			// request that triggered it
			$this->logger->log_exception( 'user_sync', $e );
		}
	}

	/**
	 * @param WP_User    $user
	 * @param int|string $idsite
	 * @param Model      $user_model
	 *
	 * @return string|null matomo login or null when the user has no access
	 */
	protected function sync_user_access_for_site( $user, $idsite, $user_model ) {
		$role = Capabilities::get_highest_role_for_user( $user );

		if ( Capabilities::ROLE_SUPERUSER === $role ) {
			$matomo_login = $this->ensure_user_exists( $user );

			$user_model->setSuperUserAccess( $matomo_login, true );

			// superuser_access already grants every site, so a per site row is redundant here. Left
			// behind it outlives the superuser flag and goes on granting this site by itself, and it
			// keeps getSiteAccessCount() non zero, which is what stops the identity being cleaned up
			$user_model->deleteUserAccess( $matomo_login );

			return $matomo_login;
		}

		if ( null === $role ) {
			// make sure the mapped matomo login is actually for the current WP user
			$mapped_matomo_login = $this->get_own_matomo_user_login( $user->ID );
			if ( $mapped_matomo_login ) {
				$user_model->deleteUserAccess( $mapped_matomo_login );
				$user_model->setSuperUserAccess( $mapped_matomo_login, false );
			}

			return null;
		}

		// note: matomo_login may not be the same as the login this user was mapped to on the way in
		$matomo_login = $this->ensure_user_exists( $user );
		$user_model->deleteUserAccess( $matomo_login );
		$user_model->addUserAccess( $matomo_login, $role, [ $idsite ] );
		$user_model->setSuperUserAccess( $matomo_login, false );

		return $matomo_login;
	}

	/**
	 * @param WP_User $wp_user
	 */
	protected function ensure_user_exists( $wp_user ) {
		$user_model = new Model();
		$user_id    = $wp_user->ID;
		$login      = $wp_user->user_login;

		$matomo_user_login = User::get_matomo_user_login( $user_id );
		$user_in_matomo    = null;

		// sanity check: make sure the matomo user login we found (if we found one) belongs
		// to the WP user being synced. if it does not, delete the mapping.
		if ( $matomo_user_login && $this->is_matomo_login_owned_by_other_wp_user( $matomo_user_login, $user_id ) ) {
			User::map_matomo_user_login( $user_id, null );
			$matomo_user_login = null;
		}

		if ( $matomo_user_login ) {
			$user_in_matomo = $user_model->getUser( $matomo_user_login );
		} else {
			$user_by_email = $user_model->getUserByEmail( $wp_user->user_email );

			// the user was deleted without matomo being notified. delete user so we can recreate it
			// below.
			//
			// note: it's also possible there are multiple users with the same email address,
			// but this is currently unsupported in matomo so we don't take that into consideration.
			if ( $user_by_email ) {
				$this->logger->log_exception(
					'user_sync',
					new \Exception(
						'Syncing user with email identical to a user already synced in Matomo. ' .
						'This means there are multiple WP users with the same email, which Matomo ' .
						'does not support, or something has deleted the WP option mapping WP user ' .
						'to Matomo user. Assuming this is a new user to sync and deleting existing user ' .
						'preferences and options.'
					)
				);

				// note: login mappings are deleted in the UsersManager.deleteUser event.
				$user_model->deleteUser( $user_by_email['login'] );
			}

			// wp usernames may include whitespace etc
			$login = preg_replace( '/[^A-Za-zÄäÖöÜüß0-9_.@+-]+/D', '_', $login );
			$login = substr( $login, 0, self::MAX_USER_NAME_LENGTH );

			if ( ! $this->is_matomo_login_taken( $user_model, $login, $user_id ) ) {
				// username is available...
				$matomo_user_login = $login;
			} else {
				// this username seems taken... lets create another one

				$index = 0;
				do {
					if ( ! $index ) {
						$matomo_user_login = 'wp_' . $login;
					} else {
						$matomo_user_login = 'wp_' . $login . $index;
					}

					++$index;
				} while ( $this->is_matomo_login_taken( $user_model, $matomo_user_login, $user_id ) );
			}
		}

		if ( ! $matomo_user_login || empty( $user_in_matomo ) ) {
			$this->logger->log( 'Matomo is now creating a user for user id ' . $user_id . ' with matomo login ' . $matomo_user_login );

			$now      = Date::now()->getDatetime();
			$password = new Password();
			// we generate some random password since log in using matomo won't be happening anyway
			$password = $password->hash( $login . $now . Common::getRandomString( 200 ) . microtime( true ) . Common::generateUniqId() );

			$user_model->addUser( $matomo_user_login, $password, $wp_user->user_email, $now );

			User::map_matomo_user_login( $user_id, $matomo_user_login );
		} elseif ( $user_in_matomo['email'] !== $wp_user->user_email ) {
			$this->logger->log( 'Matomo is now updating the email for wpUserID ' . $user_id . ' matomo login ' . $matomo_user_login );
			$user_model->updateUserFields( $matomo_user_login, [ 'email' => $wp_user->user_email ] );
		}

		return $matomo_user_login;
	}

	/**
	 * @param Model  $user_model
	 * @param string $candidate_login
	 * @param int    $wp_user_id
	 * @return bool
	 */
	private function is_matomo_login_taken( $user_model, $candidate_login, $wp_user_id ) {
		if ( $user_model->getUser( $candidate_login ) ) {
			return true; // matomo user exists
		}

		// sanity check: matomo user does not exist, but another WP user is somehow mapped to
		// this login
		return $this->is_matomo_login_owned_by_other_wp_user( $candidate_login, $wp_user_id );
	}

	/**
	 * @param string $matomo_user_login
	 * @param int    $wp_user_id
	 *
	 * @return bool
	 */
	private function is_matomo_login_owned_by_other_wp_user( $matomo_user_login, $wp_user_id ) {
		$wp_user_ids_mapped_to_matomo_login = $this->user->get_wp_user_ids_for_matomo_login( $matomo_user_login );

		if ( empty( $wp_user_ids_mapped_to_matomo_login ) ) {
			return false; // no mapping exists, matomo login not owned by anyone
		}

		if ( count( $wp_user_ids_mapped_to_matomo_login ) > 1 ) {
			return true; // more than one user mapped to login, not owned solely by this user
		}

		// the login is owned by another WP user if the single mapped user is not the requested user
		return (int) reset( $wp_user_ids_mapped_to_matomo_login ) !== (int) $wp_user_id;
	}

	public static function get_matomo_lang_from_locale( $locale ) {
		$locale_dash = Common::mb_strtolower( str_replace( '_', '-', $locale ) );
		$parts       = [];
		if ( $locale && in_array( $locale_dash, [ 'zh-cn', 'zh-tw', 'pt-br', 'es-ar' ], true ) ) {
			$parts = [ $locale_dash ];
		} elseif ( ! empty( $locale ) && is_string( $locale ) ) {
			$parts = explode( '_', $locale );
		}
		return ! empty( $parts[0] ) ? $parts[0] : null;
	}

	private function is_sync_allowed_for_request() {
		return ! Request::is_frontend();
	}
}
