<?php

namespace WpMatomo\WpStatistics\DataConverters;

use Piwik\DataTable;
use Piwik\Plugins\Actions\ArchivingHelper;
use Piwik\Tracker\Action;

class PagesUrlConverter extends NumberConverter implements DataConverterInterface {

	public static function convert( array $wpStatisticsData ) {
		$rows = self::aggregateByKey( $wpStatisticsData, 'str_url' );
		$mainUrlWithoutSlash = site_url();
		$mainUrlWithoutSlash = rtrim($mainUrlWithoutSlash, '/');
		$dataTables = [
			Action::TYPE_PAGE_URL => new DataTable()
		];
		foreach($rows as $row) {
			$wholeUrl = $mainUrlWithoutSlash . $row->getColumn('label');
			
			$actionRow = ArchivingHelper::getActionRow('dummyhost.com' . $row->getColumn('label'), Action::TYPE_PAGE_URL, '', $dataTables);

			$row->deleteColumn('label');

			$actionRow->sumRow($row, $copyMetadata = false);

			if ($actionRow->getColumn('label') != DataTable::LABEL_SUMMARY_ROW) {
				$actionRow->setMetadata('url', $wholeUrl);
			}

			//$this->pageUrlsByPagePath[$wholeUrl] = $actionRow;
		}
		return $dataTables[Action::TYPE_PAGE_URL];
	}
}
