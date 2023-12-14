<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreUpdater\ReleaseChannel;

use Piwik\Piwik;
use Piwik\Plugins\CoreUpdater\ReleaseChannel;

class LatestBeta extends ReleaseChannel
{
    public function getId()
    {
        return 'latest_beta';
    }

    public function getName()
    {
        return Piwik::translate('CoreUpdater_LatestBetaRelease');
    }

    public function doesPreferStable()
    {
        return false;
    }

    public function getOrder()
    {
        return 11;
    }
}
