<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Piwik\Date;

interface ActionsInterface {

	public function import( Date $date );
}