<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Commands;

use WP_CLI;
use WP_CLI_Command;
use WP_Site;
use WpMatomo\Installer;
use WpMatomo\Settings;
use WpMatomo\Uninstaller;
use WpMatomo\Updater;

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
