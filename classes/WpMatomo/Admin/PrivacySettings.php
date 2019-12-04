<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class PrivacySettings implements AdminSettingsInterface {
	const EXAMPLE_MINIMAL = '[matomo_opt_out]';
	const EXAMPLE_FULL    = '[matomo_opt_out language=de background_color=red font_color=fff font_size=34 font_family=Arial width=500px height=100px]';

	public function get_title() {
		return esc_html__( 'Privacy & GDPR', 'matomo' );
	}

	public function show_settings() {
		include dirname( __FILE__ ) . '/views/privacy_gdpr.php';
	}
}
