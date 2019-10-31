<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TagManager\API;

use Piwik\Piwik;

class TagReference extends BaseReference
{
    public function __construct($referenceId, $referenceName)
    {
        $referenceTypeName = Piwik::translate('TagManager_Tag');
        parent::__construct($referenceId, $referenceName, 'tag', $referenceTypeName);
    }

}
