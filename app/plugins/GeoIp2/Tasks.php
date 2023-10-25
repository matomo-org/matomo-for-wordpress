<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\GeoIp2;

use Piwik\Plugins\UserCountry\UserCountry;
use Piwik\SettingsPiwik;

class Tasks extends \Piwik\Plugin\Tasks
{
    public function schedule()
    {
        // add the auto updater task if GeoIP admin is enabled
        if (UserCountry::isGeoLocationAdminEnabled() && SettingsPiwik::isInternetEnabled() === true) {
            $this->scheduleTask(new GeoIP2AutoUpdater());
        }
    }
}
