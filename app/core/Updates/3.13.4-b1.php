<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Updates;

use Piwik\Plugins\Installation\ServerFilesGenerator;
use Piwik\Updater;
use Piwik\Updates as PiwikUpdates;
use Piwik\SettingsServer;

class Updates_3_13_4_b1 extends PiwikUpdates
{
    public function doUpdate(Updater $updater)
    {
	    if (SettingsServer::isIIS()) {
		    // Fix issue with HeatmapSessionRecording on IIS (https://github.com/matomo-org/matomo/issues/15651)
		    ServerFilesGenerator::createFilesForSecurity();
	    }
    }
}
