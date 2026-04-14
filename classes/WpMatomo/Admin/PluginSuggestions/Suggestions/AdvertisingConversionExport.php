<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @package matomo
 */

namespace WpMatomo\Admin\PluginSuggestions\Suggestions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // if accessed directly
}

use Piwik\Common;
use Piwik\DataTable;
use Piwik\Tracker\Request;
use WpMatomo\Admin\PluginSuggestions\Suggestion;

class AdvertisingConversionExport extends Suggestion {

	const LAST_CLICK_ID_OCCURRENCE_OPTION_NAME = 'matomo_last_click_id_occurrence';

	const CLICK_IDS = [
		'msclkid',
		'fbclid',
		'gclid',
		'li_fat_id',
		'yclid',
	];

	public function should_trigger() {
		// check that the last detected click ID for advertising services occurred less than one month ago
		$last_click_id_occurrence = get_option( self::LAST_CLICK_ID_OCCURRENCE_OPTION_NAME );
		if (
			empty( $last_click_id_occurrence )
			|| ! is_numeric( $last_click_id_occurrence )
		) {
			return false;
		}

		$thirty_days_secs = 30 * 24 * 60 * 60;
		return $last_click_id_occurrence > time() - $thirty_days_secs;
	}

	public function init() {
		$this->plugin_slug        = 'AdvertisingConversionExport';
		$this->plugin_name        = 'Advertising Conversion Export';
		$this->plugin_desc_long   = __( 'You are doing SEA. Improve your ad campaigns with real conversion data!', 'matomo' );
		$this->plugin_desc_short  = __( 'Integrate your Matomo conversion data with top ad platforms', 'matomo' );
		$this->trigger_desc_short = __( 'Paid Traffic', 'matomo' );
		$this->trigger_desc_long  = __( 'Paid traffic detected', 'matomo' );
		$this->image_file         = 'advertising-conversion-export.webp';
	}

	public function register_hooks() {
		add_action( 'matomo_tracker_manipulate_request', [ $this, 'detect_click_id_occurrence' ] );
	}

	public function detect_click_id_occurrence( Request $request ) {
		if ( $this->is_plugin_installed( 'AdvertisingConversionExport' ) ) {
			return;
		}

		$url = Common::unsanitizeInputValue( $request->getParam( 'url' ) );

		$click_id_regex = array_map( 'preg_quote', self::CLICK_IDS );
		$click_id_regex = '(' . implode( '|', $click_id_regex ) . ')';
		$click_id_regex = '/[&?]' . $click_id_regex . '/i';

		if ( preg_match( $click_id_regex, $url ) ) {
			$existing_occurrence = get_option( self::LAST_CLICK_ID_OCCURRENCE_OPTION_NAME );
			if ( ! is_numeric( $existing_occurrence ) ) {
				$existing_occurrence = 0;
			}
			update_option( self::LAST_CLICK_ID_OCCURRENCE_OPTION_NAME, max( $existing_occurrence, $request->getCurrentTimestamp() ) );
		}
	}
}
