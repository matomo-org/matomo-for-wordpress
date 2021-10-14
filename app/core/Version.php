<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik;

/**
 * Matomo version information.
 *
 * @api
 */
final class Version
{
    /**
     * The current Matomo version.
     * @var string
     */
    const VERSION = '4.5.0';

    const MAJOR_VERSION = 4;

    public function isStableVersion($version)
    {
        return (bool) preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version);
    }

    public function isVersionNumber($version)
    {
        return $this->isStableVersion($version) || $this->isNonStableVersion($version);
    }

    private function isNonStableVersion($version)
    {
        return (bool) preg_match('/^(\d+)\.(\d+)\.(\d+)-.{1,4}(\d+)$/', $version);
    }
}
