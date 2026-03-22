<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

use WpMatomo\WpStatistics\Importer;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class ImportWpStatistics {

	public function show() {
		$importer                                = new Importer();
		$matomo_wp_statistics_version            = $importer->get_wp_statistics_plugin_version();
		$matomo_is_compatible_with_wp_statistics = $importer->check_compatible_version( $matomo_wp_statistics_version );

		include dirname( __FILE__ ) . '/views/import_wp_statistics.php';
	}
}
