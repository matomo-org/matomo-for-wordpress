<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;

/**
 * aggregate data on the number fields
 *
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class SearchQueryConverter extends FilterConverter implements DataConverterInterface {

	public static function convert( array $wp_statistics_data ) {
		$data = self::filter( $wp_statistics_data, '?s=', 'str_url' );
		foreach ( $data as $id => $url ) {
			$matches = [];
			if ( preg_match( '/\?s=(.+)$/', $url['str_url'], $matches ) ) {
				$data[ $id ]['keyword'] = $matches[1];
			}
		}

		return self::aggregate_by_key( $data, 'keyword' );
	}
}
