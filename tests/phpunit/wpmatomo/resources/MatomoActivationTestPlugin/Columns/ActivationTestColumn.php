<?php
/**
 * phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
 *
 * @package matomo
 */

namespace Piwik\Plugins\MatomoActivationTestPlugin\Columns;

use Piwik\Plugin\Dimension\VisitDimension;

class ActivationTestColumn extends VisitDimension {
	protected $columnName = 'matomo_activation_test';
	protected $columnType = 'INT NULL';
}
