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

class Admin {
	/**
	 * @param Settings $settings
	 */
	public function __construct( $settings ) {
		new Menu( $settings );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles' ) );
	}

	public function load_styles() {
		wp_enqueue_style( 'matomo_admin_css', plugins_url( 'assets/css/admin-style.css', MATOMO_ANALYTICS_FILE ), false, '1.0.0' );
	}

}
