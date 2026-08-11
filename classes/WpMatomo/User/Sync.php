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
use WP_User;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Feature;
use WpMatomo\Logger;
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

	public function is_active() {
		return is_admin();
	}

	public function register_hooks() {
		add_action( 'add_user_role', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'remove_user_role', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'add_user_to_blog', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'remove_user_from_blog', [ $this, 'on_remove_user_from_blog' ], $prio = 10, $args = 2 );
		add_action( 'clean_user_cache', [ $this, 'on_clean_user_cache' ], $prio = 10, $args = 1 );
		add_action( 'user_register', [ $this, 'sync_current_users_1000' ], $prio = 10, $args = 0 );
		add_action( 'update_option_WPLANG', [ $this, 'on_site_language_change' ], $prio = 10, $args = 0 );
		add_action( 'profile_update', [ $this, 'sync_maybe_background' ], $prio = 10, $args = 0 );
	}

	/**
	 * WordPress fires this action before it removes the user's capabilities, so we can't sync the
	 * user here, the access it has to the site would not be removed. Instead, we remember them
	 * and have on_clean_user_cache() do the actual re-syncing.
	 *
	 * @param int $wp_user_id
	 * @param int $blog_id
	 */
	public function on_remove_user_from_blog( $wp_user_id, $blog_id ) {
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
		if ( ! Capabilities::is_capability_check_available() ) {
			return false;
		}

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
				$mapped_matomo_login = User::get_matomo_user_login( $wp_user_id );

				$access = $this->sync_user_access( $wp_user, $idsite, $user_model );

				if ( $access['login'] ) {
					// the user still legitimately has access here, eg. a network super admin who is
					// no longer a member of this blog
					if ( $access['is_superuser'] ) {
						$user_model->setSuperUserAccess( $access['login'], true );
					}

					return;
				}

				if ( $mapped_matomo_login && ! $user_model->getSiteAccessCount( $mapped_matomo_login ) ) {
					// user has access to no sites, delete the user also
					$user_model->deleteUserOnly( $mapped_matomo_login );
					$user_model->deleteUserOptions( $mapped_matomo_login );
					$user_model->deleteUserAccess( $mapped_matomo_login );
				}
			}
		);
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
			foreach ( get_sites() as $site ) {
				if ( 1 === (int) $site->deleted ) {
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
		if ( ! is_plugin_active( 'matomo/matomo.php' ) ) {
			// @see https://github.com/matomo-org/matomo-for-wordpress/issues/577
			return;
		}
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( $idsite ) {
			$num_users = count_users();
			$num_users = $num_users['total_users'];
			if ( $num_users < 1000 ) {
				$users = $this->get_users();
				$this->sync_users( $users, $idsite );
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

		$super_users                  = [];
		$logins_with_some_view_access = [ 'anonmyous' ]; // may or may not exist... we don't want to delete this user though
		$user_model                   = new Model();

		// need to make sure we recreate new instance later with latest dependencies in case they changed
		API::unsetInstance();

		foreach ( $users as $user ) {
			// todo if we used transactions we could commit it after a possibly new access has been added
			// to prevent UI preventing randomly saying no access between deleting and adding access

			$access = $this->sync_user_access( $user, $idsite, $user_model );

			$matomo_login = $access['login'];

			if ( $matomo_login ) {
				$logins_with_some_view_access[] = $matomo_login;

				if ( $access['is_superuser'] ) {
					$super_users[ $matomo_login ] = $user;
				}

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
			}
			// phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
			if ( 1 != $idsite ) {
				// only needed if the actual site is not the default site... makes sure when they click in Matomo
				// UI on "Dashboard" that the correct site is being opened by default
				// eg if the linked site is actually idSite=2.
				Access::doAsSuperUser(
					function () use ( $matomo_login, &$idsite ) {
						try {
							UsersManager\API::unsetInstance();
							// we need to unset the instance to make sure it fetches the
							// up to date dependencies eg current plugin manager etc

							UsersManager\API::getInstance()->setUserPreference(
								$matomo_login,
								UsersManager\API::PREFERENCE_DEFAULT_REPORT,
								$idsite
							);
							//phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
						} catch ( Exception $e ) {
							// ignore any error for now
						}
					}
				);
			}
		}

		foreach ( $super_users as $matomo_login => $user ) {
			$user_model->setSuperUserAccess( $matomo_login, true );
		}

		$logins_with_some_view_access = array_unique( $logins_with_some_view_access );
		$all_users                    = $user_model->getUsers( [] );
		foreach ( $all_users as $all_user ) {
			if ( ! in_array( $all_user['login'], $logins_with_some_view_access, true )
				&& ! empty( $all_user['login'] ) ) {
				Access::doAsSuperUser(
					function () use ( $user_model, $all_user ) {
						$user_model->deleteUserOnly( $all_user['login'] );
						$user_model->deleteUserOptions( $all_user['login'] );
						$user_model->deleteUserAccess( $all_user['login'] );
					}
				);
				// the WP -> Matomo mapping is cleaned up via the UsersManager.deleteUser event
				// that deleteUserOnly() fires (see WordPress::onDeleteMatomoUser).
			}
		}
	}

	/**
	 * @param WP_User    $user
	 * @param int|string $idsite
	 * @param Model      $user_model
	 *
	 * @return array{login: string|null, is_superuser: bool} login is null when the user should have
	 *                                                       no access to this site at all
	 */
	protected function sync_user_access( $user, $idsite, $user_model ) {
		$mapped_matomo_login = User::get_matomo_user_login( $user->ID );

		$role = Capabilities::get_highest_role_for_user( $user );

		if ( Capabilities::ROLE_SUPERUSER === $role ) {
			return [
				'login'        => $this->ensure_user_exists( $user ),
				'is_superuser' => true,
			];
		}

		if ( null === $role ) {
			if ( $mapped_matomo_login ) {
				$user_model->deleteUserAccess( $mapped_matomo_login, [ $idsite ] );
			}

			return [
				'login'        => null,
				'is_superuser' => false,
			];
		}

		$matomo_login = $this->ensure_user_exists( $user );
		$user_model->deleteUserAccess( $mapped_matomo_login, [ $idsite ] );
		$user_model->addUserAccess( $matomo_login, $role, [ $idsite ] );
		$user_model->setSuperUserAccess( $matomo_login, false );

		return [
			'login'        => $matomo_login,
			'is_superuser' => false,
		];
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
}
