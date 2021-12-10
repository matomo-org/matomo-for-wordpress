<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Date;

interface ActionsInterface {
	/**
	 * @param Date $date
	 *
	 * @return null
	 */
	public function importRecords( Date $date );
}
