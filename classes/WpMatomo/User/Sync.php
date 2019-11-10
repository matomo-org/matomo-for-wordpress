<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\User;

use Piwik\Access\Role\Admin;
use Piwik\Access\Role\View;
use Piwik\Access\Role\Write;
use Piwik\Auth\Password;
use Piwik\Common;
use Piwik\Date;
use Piwik\Plugin;
use Piwik\Plugins\LanguagesManager\API;
use Piwik\Plugins\UsersManager\Model;
use Piwik\Plugins\UsersManager;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Logger;
use WpMatomo\Site;
use WpMatomo\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Sync {
	/**
	 * actually allowed is 100 characters...
	 * but we do -5 to have some room to append `wp_`.$login.XYZ if needed
	 */
	const MAX_USER_NAME_LENGTH = 95;

	/**
	 * @var Logger
	 */
	private $logger;

	public function __construct() {
		$this->logger = new Logger();
	}

	public function register_hooks() {
		add_action( 'add_user_role', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
		add_action( 'remove_user_role', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
		add_action( 'add_user_to_blog', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
		add_action( 'remove_user_from_blog', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
		add_action( 'user_register', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
		add_action( 'profile_update', array( $this, 'sync_current_users' ), $prio = 10, $args = 0 );
	}

	public function sync_all() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			foreach ( get_sites() as $site ) {
				switch_to_blog( $site->blog_id );

				$idsite = Site::get_matomo_site_id( $site->blog_id );

				try {

					if ( $idsite ) {
						$users = get_users( array( 'blog_id' => $site->blog_id ) );
						$this->sync_users( $users, $idsite );
					}

				} catch ( \Exception $e ) {
					// we don't want to rethrow exception otherwise some other blogs might never sync
					$this->logger->log( 'Matomo error syncing users: ' . $e->getMessage() );
				}

				restore_current_blog();
			}
		} else {
			$this->sync_current_users();
		}
	}

	public function sync_current_users() {
		$idsite = Site::get_matomo_site_id( get_current_blog_id() );
		if ( $idsite ) {
			$users = get_users();
			$this->sync_users( $users, $idsite );
		}
	}

	/**
	 * Sync all users. Make sure to always pass all sites that exist within a given site... you cannot just sync an individual
	 * user... we would delete all other users
	 *
	 * @param \WP_User[] $users
	 * @param $idsite
	 */
	protected function sync_users( $users, $idsite ) {
		Bootstrap::do_bootstrap();

		$this->logger->log( 'Matomo will now sync ' . count( $users ) . ' users' );

		$super_users                  = array();
		$logins_with_some_view_access = array( 'anonmyous' ); // may or may not exist... we don't want to delete this user though
		$user_model                   = new Model();

		// need to make sure we recreate new instance later with latest dependencies in case they changed
		API::unsetInstance();

		foreach ( $users as $user ) {
			$user_id = $user->ID;

			// todo if we used transactions we could commit it after a possibly new access has been added
			// to prevent UI preventing randomly saying no access between deleting and adding access

			$mapped_matomo_login = User::get_matomo_user_login( $user_id );
			if ( $mapped_matomo_login ) {
				$user_model->deleteUserAccess( $mapped_matomo_login, array( $idsite ) );
			}

			$matomo_login = null;

			if ( user_can( $user, Capabilities::KEY_SUPERUSER ) ) {
				$matomo_login                   = $this->ensure_user_exists( $user );
				$super_users[ $matomo_login ]   = $user;
				$logins_with_some_view_access[] = $matomo_login;
			} elseif ( user_can( $user, Capabilities::KEY_ADMIN ) ) {
				$matomo_login = $this->ensure_user_exists( $user );
				$user_model->addUserAccess( $matomo_login, Admin::ID, array( $idsite ) );
				$user_model->setSuperUserAccess( $matomo_login, false );
				$logins_with_some_view_access[] = $matomo_login;
			} elseif ( user_can( $user, Capabilities::KEY_WRITE ) ) {
				$matomo_login = $this->ensure_user_exists( $user );
				$user_model->addUserAccess( $matomo_login, Write::ID, array( $idsite ) );
				$user_model->setSuperUserAccess( $matomo_login, false );
				$logins_with_some_view_access[] = $matomo_login;
			} elseif ( user_can( $user, Capabilities::KEY_VIEW ) ) {
				$matomo_login = $this->ensure_user_exists( $user );
				$user_model->addUserAccess( $matomo_login, View::ID, array( $idsite ) );
				$user_model->setSuperUserAccess( $matomo_login, false );
				$logins_with_some_view_access[] = $matomo_login;
			}

			if ( $matomo_login ) {
				$locale = get_user_locale( $user->ID );
				$parts  = explode( '_', $locale );

				if ( !empty( $parts[0] ) ) {
					$lang = $parts[0];
					if (Plugin\Manager::getInstance()->isPluginActivated('LanguagesManager')
					    && Plugin\Manager::getInstance()->isPluginInstalled('LanguagesManager')
					    && API::getInstance()->isLanguageAvailable( $lang ) ) {
						$user_lang_model = new \Piwik\Plugins\LanguagesManager\Model();
						$user_lang_model->setLanguageForUser( $matomo_login, $lang );
					}
				}
			}
		}

		foreach ( $super_users as $matomo_login => $user ) {
			$user_model->setSuperUserAccess( $matomo_login, true );
		}

		$logins_with_some_view_access = array_unique( $logins_with_some_view_access );
		$all_users                    = $user_model->getUsers( array() );
		foreach ( $all_users as $all_user ) {
			if ( ! in_array( $all_user['login'], $logins_with_some_view_access )
			     && ! empty( $all_user['login'] ) ) {
				$user_model->deleteUserOnly( $all_user['login'] );
			}
		}
	}

	/**
	 * @param \WP_User $wp_user
	 */
	protected function ensure_user_exists( $wp_user ) {
		$user_model = new Model();
		$user_id    = $wp_user->ID;
		$login      = $wp_user->user_login;

		$matomo_user_login = User::get_matomo_user_login( $user_id );
		$user_in_matomo    = null;

		if ( $matomo_user_login ) {
			$user_in_matomo = $user_model->getUser( $matomo_user_login );
		} else {
			$login = substr( $login, 0, self::MAX_USER_NAME_LENGTH );

			if ( ! $user_model->getUser( $login ) ) {
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

					$index ++;

				} while ( $user_model->getUser( $matomo_user_login ) );
			}
		}

		if ( ! $matomo_user_login || empty( $user_in_matomo ) ) {
			$this->logger->log( 'Matomo is now creating a user forUserId ' . $user_id . ' with matomo login ' . $matomo_user_login );

			$now      = Date::now()->getDatetime();
			$password = new Password();
			// we generate some random password since log in using matomo won't be happening anyway
			$password = $password->hash( $login . $now . Common::getRandomString( 200 ) . microtime( true ) . Common::generateUniqId() );

			UsersManager\API::unsetInstance(); // make sure latest instance is loaded with all current dependencies... mainly needed for tests
			$token = UsersManager\API::getInstance()->createTokenAuth( $login );
			$user_model->addUser( $matomo_user_login, $password, $wp_user->user_email, $login, $token, $now );

			User::map_matomo_user_login( $user_id, $matomo_user_login );

		} elseif ( $user_in_matomo['email'] != $wp_user->user_email ) {
			$this->logger->log( 'Matomo is now updating the email for wpUserID ' . $user_id . ' matomo login ' . $matomo_user_login );
			$user_model->updateUserFields( $matomo_user_login, array( 'email' => $wp_user->user_email ) );
		}

		return $matomo_user_login;
	}
}
