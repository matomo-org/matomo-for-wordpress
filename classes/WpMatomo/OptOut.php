<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace WpMatomo;

use WP_Roles;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class OptOut {

	public function register_hooks() {
		add_shortcode( 'matomo_opt_out', array( $this, 'show_opt_out' ) );
	}

	public function show_opt_out( $atts ) {
		$a = shortcode_atts( array(
			'language'         => '',
			'background_color' => '',
			'font_color'       => '',
			'font_size'        => '',
			'font_family'      => '',
			'width'            => '600',
			'height'           => '200',
		), $atts );

		$url = plugins_url( 'app', MATOMO_ANALYTICS_FILE ) . '/index.php';

		$map    = array(
			'background_color' => 'backgroundColor',
			'font_color'       => 'fontColor',
			'font_size'        => 'fontSize',
			'font_family'      => 'fontFamily',
		);
		$params = array( 'module' => 'CoreAdminHome', 'action' => 'optOut' );
		if ( ! empty( $a['language'] ) ) {
			$params['language'] = $a['language'];
		}
		foreach ( $map as $param => $urlparam ) {
			if ( ! empty( $a[ $param ] ) ) {
				$params[ $urlparam ] = urlencode( $a[ $param ] );
			}
		}

		$url       = $url . '?' . http_build_query( $params );
		$sizes     = array( 'width', 'height' );
		$add_style = '';
		foreach ( $sizes as $size ) {
			if ( is_numeric( $a[ $size ] ) || preg_match( '/\d+px/', $a[ $size ] ) || preg_match( '/\d+%/', $a[ $size ] ) ) {
				if ( is_numeric( $a[ $size ] ) ) {
					$a[ $size ] = $a[ $size ] . 'px';
				}
				$add_style .= $size . ':' . $a[ $size ] . ';';
			}
		}

		return '<iframe style="border: 0; ' . $add_style . '" src="' . $url . '"></iframe>';
	}

}
