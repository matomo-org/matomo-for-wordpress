<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\GeoIp2;

use Piwik\Date;
use Piwik\Plugins\GeoIp2\GeoIP2AutoUpdater;

/**
 * Downloads last month's DB-IP databases, for when this month's are not published yet.
 */
class DbIpPreviousMonthUpdater extends GeoIP2AutoUpdater
{
    protected function getDbIpUrlWithLatestDate($url)
    {
        $previousMonth = Date::today()->subMonth(1)->toString('Y-m');
        return preg_replace('/-\d{4}-\d{2}\./', '-' . $previousMonth . '.', $url);
    }
}
