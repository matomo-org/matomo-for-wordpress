<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WP_STATISTICS\MetaBox\referring;
use Piwik\Date;

class ReferrersImporter extends RecordImporter implements ActionsInterface {

	const PLUGIN_NAME = 'Referrers';

	public function import( Date $date ) {
		$limit = 10000;

		return referring::get( $limit );
	}
}