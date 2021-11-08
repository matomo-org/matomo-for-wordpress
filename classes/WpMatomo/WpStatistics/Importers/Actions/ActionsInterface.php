<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use WpMatomo\WpStatistics\DateTime;

Interface ActionsInterface {

	public function import(DateTime $date_time);
}