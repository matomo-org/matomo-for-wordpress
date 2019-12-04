<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Commands;

use WpMatomo\Settings;
use WpMatomo\Uninstaller;
use WP_CLI;
use WP_CLI_Command;
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
		$updater = new Updater( new Settings() );
		if ( ! empty( $assoc_args['force'] ) ) {
			WP_CLI::log( 'Force running updates' );
			$updater->update();
		} else {
			WP_CLI::log( 'Running update if needed' );
			$updater->update_if_needed();
		}

		WP_CLI::success( 'Matomo Analytics Updater finished' );
	}
}

WP_CLI::add_command(
	'matomo',
	'\WpMatomo\Commands\MatomoCommands',
	array(
		'shortdesc' => 'Manage your Matomo Analytics. Commands are recommended only to be used in development mode',
	)
);
