<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Plugins\TagManager\API as UpstreamAPI;

/**
 * Tag Manager's API, with the secured templates' extra checks suspended while a refused import is
 * rolled back.
 *
 * Only the restore is covered. An ordinary import is somebody's uploaded JSON and stays validated.
 */
class API extends UpstreamAPI
{
    public function importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName = '', bool $_isDraftRestoreCall = false)
    {
        if (!$_isDraftRestoreCall) {
            return parent::importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName, $_isDraftRestoreCall);
        }

        return SecuredTemplateConstraints::suspendedFor(function () use ($exportedContainerVersion, $idSite, $idContainer, $backupName, $_isDraftRestoreCall) {
            return parent::importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName, $_isDraftRestoreCall);
        });
    }
}
