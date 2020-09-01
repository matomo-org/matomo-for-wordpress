<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo;

use Piwik\Piwik;
use Piwik\Plugins\PrivacyManager\DoNotTrackHeaderChecker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

class OptOut {

	private $language = null;

	public function register_hooks() {
		// keeping this one temporarily so people can switch to iframe as a workaround until there's a fix for below solution
		// just in case...
		add_shortcode( 'matomo_opt_out_iframe', array( $this, 'show_opt_out_iframe' ) );

		add_shortcode( 'matomo_opt_out', array( $this, 'show_opt_out_embedded' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' )  );
	}

	public function load_scripts() {
		if (!is_admin()) {
			wp_register_script( 'matomo_opt_out_js', plugins_url( 'assets/js/optout.js', MATOMO_ANALYTICS_FILE ), array(), null, true );
		}
	}
	
	private function translate($id)
	{
		return esc_html(Piwik::translate($id, array(), $this->language));
	}

	public function show_opt_out_embedded( $atts ) {
		$a = shortcode_atts(
			array(
				'language' => null,
			),
			$atts
		);
		if (!empty($a['language']) && strlen($a['language']) < 6) {
			$this->language = $a['language'];
		}

		try {
			Bootstrap::do_bootstrap();
		} catch (\Throwable $e ) {
			$logger = new Logger();
			$logger->log_exception('optout', $e);
			return '<p>An error occurred. Please check Matomo system report in WP-Admin.</p>';
		}

		$dnt_checker = new DoNotTrackHeaderChecker();
		$dnt_enabled = $dnt_checker->isDoNotTrackFound();

		if (!empty($dnt_enabled)) {
			return '<p>'. $this->translate('CoreAdminHome_OptOutDntFound').'</p>';
		}

		wp_enqueue_script( 'matomo_opt_out_js' );

		$track_visits = empty($_COOKIE['mtm_consent_removed']);

		$style_tracking_enabled = '';
		$style_tracking_disabled = '';
		$checkbox_attr = '';
		if ($track_visits) {
			$style_tracking_enabled = 'style="display:none;"';
			$checkbox_attr = 'checked="checked"';
		} else {
			$style_tracking_disabled = 'style="display:none;"';
		}

		$content = '<p id="matomo_opted_out_intro" ' . $style_tracking_enabled . '>' . $this->translate('CoreAdminHome_OptOutComplete') . ' '  . $this->translate('CoreAdminHome_OptOutCompleteBis') . '</p>';
		$content .= '<p id="matomo_opted_in_intro" ' .$style_tracking_disabled . '>' . $this->translate('CoreAdminHome_YouMayOptOut2') . ' ' . $this->translate('CoreAdminHome_YouMayOptOut3') . '</p>';

		$content .= '<form>
        <input type="checkbox" id="matomo_optout_checkbox" '.$checkbox_attr.'/>
        <label for="matomo_optout_checkbox"><strong>
        <span id="matomo_opted_in_label" '.$style_tracking_disabled.'>'.$this->translate('CoreAdminHome_YouAreNotOptedOut') .' ' . $this->translate('CoreAdminHome_UncheckToOptOut') . '</span>
		<span id="matomo_opted_out_label" '.$style_tracking_enabled.'>'.$this->translate('CoreAdminHome_YouAreOptedOut') .' ' . $this->translate('CoreAdminHome_CheckToOptIn') . '</span>
        </strong></label></form>';
		$content .= '<noscript><p><strong style="color: #ff0000;">This opt out feature requires JavaScript.</strong></p></noscript>';
		$content .= '<p id="matomo_outout_err_cookies" style="display: none;"><strong>' . $this->translate('CoreAdminHome_OptOutErrorNoCookies') . '</strong></p>';
		return $content;
	}

	public function show_opt_out_iframe( $atts ) {
		$a = shortcode_atts(
			array(
				'language'         => '',
				'background_color' => '',
				'font_color'       => '',
				'font_size'        => '',
				'font_family'      => '',
				'width'            => '600',
				'height'           => '200',
			),
			$atts
		);

		$url = plugins_url( 'app', MATOMO_ANALYTICS_FILE ) . '/index.php';

		$map    = array(
			'background_color' => 'backgroundColor',
			'font_color'       => 'fontColor',
			'font_size'        => 'fontSize',
			'font_family'      => 'fontFamily',
		);
		$params = array(
			'action' => 'matomo_optout',
		);
		if ( ! empty( $a['language'] ) ) {
			$params['language'] = $a['language'];
		}
		foreach ( $map as $param => $urlparam ) {
			if ( ! empty( $a[ $param ] ) ) {
				$params[ $urlparam ] = rawurlencode( $a[ $param ] );
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
