<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Plugins\TagManager\Model\Tag as UpstreamTag;

/**
 * Tag Manager's tag model, with the secured templates' extra checks suspended while a tag's stored
 * parameters are written back unchanged.
 *
 * updateParameters() re-validates every parameter of the tag, not just the ones.
 *
 * Nothing here comes from the request. The parameters are the ones already in the table with one
 * variable name swapped for another, and Overrides\TagManager\Variable keeps that name from being
 * anything that could be read as a reference of its own. The only other caller is
 * UpdateHelper\NewTagParameterMigrator, which is a schema migration over stored rows; there is no
 * API method behind this.
 */
class Tag extends UpstreamTag
{
    public function updateParameters($idSite, $idContainerVersion, $idTag, $parameters)
    {
        return SecuredTemplateConstraints::suspendedFor(function () use ($idSite, $idContainerVersion, $idTag, $parameters) {
            return parent::updateParameters($idSite, $idContainerVersion, $idTag, $parameters);
        });
    }
}
