<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Heartbeat;

use Piwik\Plugin;
class Heartbeat extends Plugin
{
    public function isTrackerPlugin()
    {
        return true;
    }
}
