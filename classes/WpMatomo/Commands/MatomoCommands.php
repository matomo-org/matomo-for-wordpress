<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Commands;
require_once ABSPATH.'/wp-load.php';
require_once ABSPATH.'/wp-includes/ms-blogs.php';

use Piwik\Access;
use WP_CLI;
use WP_CLI_Command;
use WP_Site;
use WpMatomo\Installer;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;
use WpMatomo\Updater;
use WpMatomo\WpStatistics\Importer;
use WpMatomo\WpStatistics\Logger\WpCliLogger;
use WpMatomo\Bootstrap;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) ) {
	exit;
}

class MatomoCommands extends WP_CLI_Command {
	/**
	 * Uninstalls Matomo.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : To delete all data stored in all tables
	 *
	 * ## EXAMPLES
	 *
	 *     wp matomo uninstall --force
	 *
	 * @when after_wp_load
	 */
	public function uninstall( $args, $assoc_args ) {
		if ( ! empty( $assoc_args['force'] ) ) {
			$delete_all_data = true;
			WP_CLI::log( 'Deleting all data is forced.' );
		} else {
			$delete_all_data = false;
			WP_CLI::log( 'Deleting all data is NOT forced. To remove all data set the --force option.' );
		}

		$uninstaller = new Uninstaller();
		$uninstaller->uninstall( $delete_all_data );

		WP_CLI::success( 'Uninstalled Matomo Analytics' );
	}

	/**
	 * Import wp-statistics data
	 *
	 * ## OPTIONS
	 *
	 * [--site=<siteId>]
	 * : the site id to import
	 * [--loggin=<logging>]
	 * : Your log in
	 * [--password=<password>]
	 * : Your password
	 * ## EXAMPLES
	 *
	 *     wp matomo update --site 1
	 *
	 * @when after_wp_load
	 */
	public function importWpStatistics( $args, $assoc_args ) {
		$logger = new WpCliLogger();
		$logger->info( 'Starting wp-statistics import'  );
		try {
			Bootstrap::do_bootstrap();
			Access::getInstance()->setSuperUserAccess(true);
			$loggin = ! empty( $assoc_args['loggin'] ) ? $assoc_args['loggin'] : null;
			$password = ! empty( $assoc_args['password'] ) ? $assoc_args['password'] : null;
			$creds = array();
			$creds['user_login'] = $loggin;
			$creds['user_password'] =  $password;
			$creds['remember'] = true;
			$user = wp_signon( $creds, false );
			$importer = new Importer($logger);
			if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_sites' ) ) {
				$id_site = ! empty( $assoc_args['site'] ) ? $assoc_args['site'] : null;
				$logger->info( 'Function exists'  );
				foreach ( get_sites() as $site ) {
					/** @var WP_Site $site */
					if ( is_null( $id_site ) || ( $site->blog_id === $id_site ) ) {
						$logger->info( 'Switch to blog'  );
						switch_to_blog( $site->blog_id );
						// this way we make sure all blogs get updated eventually
						$logger->info( 'Blog ID' . $site->blog_id );
						$importer->import( $site->blog_id );
						restore_current_blog();
					}
				}
			} else {
				$id_site = ! empty( $assoc_args['site'] ) ? $assoc_args['site'] : 1;
				switch_to_blog( $id_site );
			//	set_error_handler([$this, 'myErrorHandler']);
				$importer->import( $id_site );
			}

			$logger->info( 'Matomo Analytics wp-statistics import finished' );
		} catch (\Exception $e) {
			$logger->error($e->getMessage());
		}

	}

	function myErrorHandler($errno, $errstr, $errfile, $errline)
	{
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			return false;
		}

		// $errstr may need to be escaped:
		$errstr = htmlspecialchars($errstr);

		switch ($errno) {
			case E_USER_ERROR:
				echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
				echo "  Fatal error on line $errline in file $errfile";
				echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
				echo "Aborting...<br />\n";
				exit(1);

			case E_USER_WARNING:
				echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
				break;

			case E_USER_NOTICE:
				echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
				break;

			default:
				echo "Unknown error type: [$errno] $errstr<br />\n";
				break;
		}

		/* Don't execute PHP internal error handler */
		return true;
	}
	/**
	 * Updates Matomo.
	 *
	 * ## OPTIONS
	 *
	 * [--force]
	 * : To force running the update
	 *
	 * ## EXAMPLES
	 *
	 *     wp matomo update --force
	 *
	 * @when after_wp_load
	 */
	public function update( $args, $assoc_args ) {
		if ( function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'get_sites' ) ) {
			foreach ( get_sites() as $site ) {
				/** @var WP_Site $site */
				switch_to_blog( $site->blog_id );
				// this way we make sure all blogs get updated eventually
				WP_CLI::log( 'Blog ID' . $site->blog_id );
				$this->do_update( ! empty( $assoc_args['force'] ) );
				restore_current_blog();
			}
		} else {
			$this->do_update( ! empty( $assoc_args['force'] ) );
		}

		WP_CLI::success( 'Matomo Analytics Updater finished' );
	}

	/**
	 * @param $assoc_args
	 */
	private function do_update( $force ) {
		$settings = new Settings();

		$installer = new Installer( $settings );
		if ( ! $installer->looks_like_it_is_installed() ) {
			WP_CLI::log( 'Skipping as looks like Matomo is not yet installed' );

			return;
		}

		$updater = new Updater( $settings );
		if ( $force ) {
			WP_CLI::log( 'Force running updates' );
			$updater->update();
		} else {
			WP_CLI::log( 'Running update if needed' );
			$updater->update_if_needed();
		}
	}
}

WP_CLI::add_command(
	'matomo',
	'\WpMatomo\Commands\MatomoCommands',
	[
		'shortdesc' => 'Manage your Matomo Analytics. Commands are recommended only to be used in development mode',
	]
);
