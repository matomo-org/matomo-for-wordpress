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

use Piwik\DataTable;
use WpMatomo\Admin\PluginSuggestions\Suggestion;

class AdvertisingConversionExport extends Suggestion {

	const CLICK_IDS = [
		'msclkid',
		'fbclid',
		'gclid',
		'li_fat_id',
		'yclid',
	];

	public function should_trigger() {
		// check for advertising service click IDs in tracked URLs
		$data = $this->get_last_month_data( 'Actions.getPageUrls', 1000 );

		$click_id_regex = array_map( 'preg_quote', self::CLICK_IDS );
		$click_id_regex = '(' . implode( '|', $click_id_regex ) . ')';
		$click_id_regex = '/[&?]' . $click_id_regex . '/i';

		foreach ( $data->getRows() as $row ) {
			$label = $row->getColumn( 'label' );
			if ( empty( $label ) ) {
				continue;
			}

			if ( preg_match( $click_id_regex, $label ) ) {
				return true;
			}
		}

		return false;
	}

	public function init() {
		$this->plugin_slug        = 'AdvertisingConversionExport';
		$this->plugin_name        = 'Advertising Conversion Export';
		$this->plugin_desc_long   = __( 'You are doing SEA. Improve your ad campaigns with real conversion data!', 'matomo' );
		$this->plugin_desc_short  = __( 'Integrate your Matomo conversion data with top ad platforms', 'matomo' );
		$this->trigger_desc_short = __( 'Paid Traffic', 'matomo' );
		$this->trigger_desc_long  = __( 'Paid traffic detected', 'matomo' );
	}
}
