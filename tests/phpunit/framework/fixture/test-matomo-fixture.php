<?php
/**
 * Matomo test case bootstrapping an entire Matomo.
 *
 * @package matomo
 */

use Piwik\Archive;
use Piwik\ArchiveProcessor\PluginsArchiver;
use Piwik\Cache;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\DataTable\Manager;
use Piwik\Date;
use Piwik\FrontController;
use Piwik\Option;
use Piwik\Plugin\API;
use Piwik\Site;
use WpMatomo\Bootstrap;
use WpMatomo\Capabilities;
use WpMatomo\Installer;
use WpMatomo\Logger;
use WpMatomo\Report\Metadata;
use WpMatomo\Roles;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;
use WpMatomo\User;

/**
 * @package matomo
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 */
class MatomoUnit_Matomo_Fixture {

	/**
	 * Applies the per test annotations this fixture supports: noTestMode, noDebugLog and
	 * provideContainerConfig.
	 *
	 * Kept separate from set_up() so MatomoAnalytics_SharedFixture_TestCase, which does not run the
	 * installer per test, can still honor them before it bootstraps.
	 *
	 * @param mixed $test_case
	 * @param bool  $enable_test_mode set to false when the caller knows PIWIK_TEST_MODE must not be
	 *                                defined, but has no test case to read @noTestMode off
	 */
	public function apply_test_annotations( $test_case = null, $enable_test_mode = true ) {
		$annotations = [];

		if ( $test_case ) {
			$test_class_name  = get_class( $test_case );
			$test_method_name = $test_case->getName();

			unset( $GLOBALS['MATOMO_SWITCH_BLOG_SET_UP'] );

			$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( $test_class_name, $test_method_name );
		}

		if (
			$enable_test_mode
			&& empty( $annotations['method']['noTestMode'] )
			&& ! defined( 'PIWIK_TEST_MODE' )
		) {
			define( 'PIWIK_TEST_MODE', true );
		}

		if ( defined( 'PIWIK_TEST_MODE' ) ) {
			if ( ! empty( $annotations['method']['provideContainerConfig'][0] ) ) {
				$container_config = $annotations['method']['provideContainerConfig'][0];

				$method      = new ReflectionMethod( $test_case, $container_config );
				$definitions = $method->invoke( $test_case );

				Bootstrap::set_extra_di_definitions( $definitions );
			} else {
				Bootstrap::set_extra_di_definitions( [] );
			}
		}

		if ( ! empty( $annotations['method']['noDebugLog'] ) && ! defined( 'MATOMO_DEBUG' ) ) {
			define( 'MATOMO_DEBUG', false );
		}
	}

	public function set_up( $test_case = null, $enable_test_mode = true ) {
		$this->apply_test_annotations( $test_case, $enable_test_mode );

		$this->uninstall_matomo();

		clearstatcache();

		Bootstrap::set_not_bootstrapped();

		// to make sure installation goes forward
		$this->reset_config_for_install();

		$settings  = new Settings();
		$installer = new Installer( $settings );
		$installer->install();

		// we need to init roles again... seems like WP isn't doing this by themselves...
		// otherwise if one test adds eg Capability WRITE_MATOMO to a role "editor", in other tests this
		// capability will still be present
		global $wp_roles;
		$wp_roles->init_roles();

		$roles = new Roles( $settings );
		// we force add the roles because the option marking roles as setup is added before tests run, and thus
		// is always set when tests start
		$roles->add_roles( true );

		$this->register_hooks();

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}
	}

	public function register_hooks() {
		add_action(
			'set_current_user',
			function () {
				// auth might still be pointing to a different user...
				Bootstrap::set_not_bootstrapped();
			}
		);

		add_action(
			'matomo_uninstall',
			function () {
				Option::clearCache();
				Cache::flushAll();
				\Piwik\Singleton::clearAll();
				API::unsetAllInstances();
				ArchiveTableCreator::clear();
				Site::clearCache();
				Archive::clearStaticCache();
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				FrontController::$requestId = null;
				Date::$now                  = null;
				\Piwik\Tracker\Cache::deleteTrackerCache();
				\Piwik\NumberFormatter::getInstance()->clearCache();
				\Piwik\Plugins\ScheduledReports\API::$cache = array();
				Manager::getInstance()->deleteAll();
				\WpMatomo\Updater::unlock();
				PluginsArchiver::$archivers = array();
				$_GET                       = array();
				$_REQUEST                   = array();
				\Piwik\Container\StaticContainer::get( \Piwik\Translation\Translator::class )->reset();
				\Piwik\Log::unsetInstance();
			}
		);
	}

	public function tear_down() {
		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}

		$this->uninstall_matomo();

		$this->reset_config_for_install();
		if ( defined( 'PIWIK_TEST_MODE' ) ) {
			Bootstrap::set_extra_di_definitions( [] );
		}

		unset( $_GET['trigger'] );
		Metadata::clear_cache();

		Bootstrap::destroy_bootstrapped_environment();
	}

	public function reset_config_for_install() {
		$paths      = new \WpMatomo\Paths();
		$local_path = $paths->get_config_ini_path();
		if ( is_file( $local_path ) ) {
			unlink( $local_path );
		}

		if ( $this->container_exists() ) {
			\Piwik\Container\StaticContainer::get( \Piwik\Application\Kernel\GlobalSettingsProvider::class )->reload();
		}
	}

	private function container_exists() {
		if ( ! class_exists( \Piwik\Container\StaticContainer::class ) ) {
			return false;
		}

		try {
			\Piwik\Container\StaticContainer::getContainer();
		} catch ( \Piwik\Container\ContainerDoesNotExistException $ex ) {
			return false;
		}

		return true;
	}

	private function uninstall_matomo() {
		try {
			// will not be defined for the first run test case
			if (
				class_exists( '\Piwik\SettingsPiwik' )
				&& \Piwik\SettingsPiwik::isMatomoInstalled()
			) {
				$uninstall = new Uninstaller();
				$uninstall->uninstall( true );
			}
		} catch ( \Exception $ex ) {
			// ignore
		}
	}

	/**
	 * @param mixed $wp_factory WP_UnitTestCase::factory() instance
	 * @return mixed
	 */
	public function create_set_super_admin( $wp_factory ) {
		$logger = new Logger();
		$logger->log( 'creating super admin' );
		$id = $wp_factory->user->create();

		$sync = new User\Sync();
		$sync->sync_current_users();

		wp_set_current_user( $id );
		$user = wp_get_current_user();

		if ( is_multisite() ) {
			grant_super_admin( $id );
			$user->add_cap( Capabilities::KEY_SUPERUSER );
		} else {
			$user->add_role( 'administrator' );
			$user->add_role( Roles::ROLE_SUPERUSER );
			$user->add_cap( Capabilities::KEY_SUPERUSER );
		}

		$sync = new User\Sync();
		$sync->sync_current_users();

		return $id;
	}
}
