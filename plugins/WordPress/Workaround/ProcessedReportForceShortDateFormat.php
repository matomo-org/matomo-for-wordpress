<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\WordPress\Workaround;

use Piwik\DataTable\Map;
use Piwik\Period;
use Piwik\Request;

class ProcessedReportForceShortDateFormat
{
    const CUSTOM_DATE_FORMAT_PARAMETER_NAME = 'forceShortDate';

    public function replacePeriodsUsingCustomDateFormatIfRequested($returnedValue) {
        if (!($returnedValue instanceof Map)) {
            return;
        }

        $isForcing = Request::fromRequest()->getBoolParameter(self::CUSTOM_DATE_FORMAT_PARAMETER_NAME, false);
        if (!$isForcing) {
            return;
        }

        $this->replacePeriodMetadata($returnedValue);

        // don't forward the custom date format to any child requests, it should
        // only be applied to the root request. this is because code that creates
        // nested API requests will not expect the date labels to change.
        unset($_GET[self::CUSTOM_DATE_FORMAT_PARAMETER_NAME]);
        unset($_POST[self::CUSTOM_DATE_FORMAT_PARAMETER_NAME]);
    }

    private function replacePeriodMetadata(Map $returnedValue)
    {
        if ($returnedValue->getFirstRow() instanceof Map) {
            foreach ($returnedValue->getDataTables() as $childTable) {
                $this->replacePeriodMetadata($childTable);
            }
            return;
        }

        if ($returnedValue->getKeyName() === 'date') {
            foreach ($returnedValue->getDataTables() as $table) {
                $originalPeriod = $table->getMetadata('period');
                if (!($originalPeriod instanceof Period)) {
                    continue;
                }

                $table->setMetadata('period', new ForceShortDateFormat($originalPeriod));
            }
        }
    }
}
