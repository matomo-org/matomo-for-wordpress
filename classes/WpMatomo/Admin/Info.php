<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo\Admin;

use WpMatomo\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class Info {

	public function show() {
		include( dirname( __FILE__ ) . '/views/info.php' );
	}

	public function show_multisite() {
		include( dirname( __FILE__ ) . '/views/info_multisite.php' );
	}


}
