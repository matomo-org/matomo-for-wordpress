<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Tracker\Action;
/**
 * @package WpMatomo
 * @subpackage WpStatisticsImport
 */
class PagesUrlConverter extends NumberConverter implements DataConverterInterface {

	public static function convert( array $wp_statistics_data ) {
		$rows                   = self::aggregate_by_key( $wp_statistics_data, 'str_url' );
		$main_url_without_slash = site_url();
		$main_url_without_slash = rtrim( $main_url_without_slash, '/' );
		$data_tables            = [
			Action::TYPE_PAGE_URL => new DataTable(),
		];
		foreach ( $rows as $row ) {
			$whole_url = $main_url_without_slash . $row->getColumn( 'label' );

			$action_row = ArchivingHelper::getActionRow( 'dummyhost.com' . $row->getColumn( 'label' ), Action::TYPE_PAGE_URL, '', $data_tables );

			$row->deleteColumn( 'label' );

			$action_row->sumRow( $row, $copy_metadata = false );

			if ( $action_row->getColumn( 'label' ) !== DataTable::LABEL_SUMMARY_ROW ) {
				$action_row->setMetadata( 'url', $whole_url );
			}
		}
		return $data_tables[ Action::TYPE_PAGE_URL ];
	}
}
