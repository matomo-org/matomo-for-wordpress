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
    /**
     * @var bool whether an import is already running on this instance
     */
    private $isImporting = false;

    public function importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName = '', bool $_isDraftRestoreCall = false)
    {
        // note: $_isDraftRestoreCall says which of the two this is, but it cannot be believed on its own,
        // since an attacker could send the parameter to trigger the suspension. So to prevent this, the
        // flag is only honored in the nested call TagManager\API makes to itself while rolling back.

        $isRollback = $_isDraftRestoreCall && $this->isImporting;

        $wasImporting      = $this->isImporting;
        $this->isImporting = true;

        try {
            if (!$isRollback) {
                return parent::importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName, false);
            }

            return SecuredTemplateConstraints::suspendedFor(function () use ($exportedContainerVersion, $idSite, $idContainer, $backupName) {
                return parent::importContainerVersion($exportedContainerVersion, $idSite, $idContainer, $backupName, true);
            });
        } finally {
            $this->isImporting = $wasImporting;
        }
    }
}
