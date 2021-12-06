<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace WpMatomo\WpStatistics\Exceptions;

class MaxEndDateReached extends \RuntimeException {

	public function __construct() {
		parent::__construct( 'Max end date reached.' );
	}
}
