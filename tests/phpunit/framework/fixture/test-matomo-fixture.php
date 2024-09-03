<?php
/**
 * Matomo test case bootstrapping an entire Matomo.
 *
 * @package matomo
 */

use Piwik\Archive;
use Piwik\ArchiveProcessor\PluginsArchiver;
use Piwik\Cache;
use Piwik\Config;
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
use WpMatomo\Paths;
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
	public function set_up( $test_class_name, $test_method_name ) {
		if ( ! defined( 'PIWIK_TEST_MODE' ) ) {
			define( 'PIWIK_TEST_MODE', true );
		}

		// TODO: move this to test-matomo-fixture somehow
		$annotations = PHPUnit\Util\Test::parseTestMethodAnnotations( $test_class_name, $test_method_name );
		if ( ! empty( $annotations['method']['provideContainerConfig'][0] ) ) {
			$container_config = $annotations['method']['provideContainerConfig'][0];

			$method      = new ReflectionMethod( $this, $container_config );
			$definitions = $method->invoke( $this );

			Bootstrap::set_extra_di_definitions( $definitions );
		} else {
			Bootstrap::set_extra_di_definitions( [] );
		}

		$uninstall = new Uninstaller();
		$uninstall->uninstall( true );

		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}

		clearstatcache();

		Bootstrap::set_not_bootstrapped();

		$settings  = new Settings();
		$installer = new Installer( $settings );
		$installer->install();

		// we need to init roles again... seems like WP isn't doing this by themselves...
		// otherwise if one test adds eg Capability WRITE_MATOMO to a role "editor", in other tests this
		// capability will still be present
		global $wp_roles;
		$wp_roles->init_roles();

		$roles = new Roles( $settings );
		$roles->add_roles();

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

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( false );
		}
	}

	public function tear_down() {
		Bootstrap::set_extra_di_definitions( [] );

		if ( ! empty( $GLOBALS['wpdb'] ) ) {
			$GLOBALS['wpdb']->suppress_errors( true );
		}

		$uninstall = new Uninstaller();
		$uninstall->uninstall( true );

		unset( $_GET['trigger'] );
		Metadata::clear_cache();

		if ( is_multisite() ) {
			$this->delete_extraneous_blogs();
		}
	}

	private function delete_extraneous_blogs() {
		global $wpdb;

		switch_to_blog( 1 );

		$blogs = $wpdb->get_results( 'SELECT blog_id, deleted FROM ' . $wpdb->blogs . ' ORDER BY blog_id', ARRAY_A );
		foreach ( $blogs as $blog ) {
			if ( 1 === (int) $blog['deleted'] || 1 === (int) $blog['blog_id'] ) {
				continue;
			}

			wpmu_delete_blog( $blog['blog_id'] );
		}
	}
}
