<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\TagManager\Activity;

use Piwik\Container\StaticContainer;

abstract class TagBaseActivity extends BaseActivity
{
    protected $entityType = 'containertag';

    private function getEntityDao()
    {
        return StaticContainer::get('Piwik\Plugins\TagManager\Dao\TagsDao');
    }

    protected function getEntityData($idSite, $idContainer, $idContainerVersion, $idEntity)
    {
        $entity = $this->getEntityDao()->getContainerTag($idSite, $idContainerVersion, $idEntity);

        if (!empty($entity['name'])) {
            $entityName = $entity['name'];
        } else {
            // entity might not be set when we are handling "deleted" activity
            $entityName = 'ID: ' . (int) $idEntity;
        }

        return array(
            'id' => $idEntity,
            'name' => $entityName
        );
    }
}
