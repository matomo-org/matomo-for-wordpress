<?php

namespace WpMatomo\WpStatistics\Importers\Actions;

use Psr\Log\LoggerInterface;

class RecordImporter {

	protected $logger = null;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}
}