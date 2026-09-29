<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Plugins\TagManager\Model\Container as UpstreamContainer;

/**
 * Tag Manager's container model, with the secured templates' extra checks suspended while a
 * container version is created.
 *
 * createContainerVersion() does not copy rows. It exports the draft and imports it again through
 * the API, so every stored parameter is offered to its template a second time. For a user without
 * `unfiltered_html` that means a Custom HTML tag somebody else added -- or a Matomo URL stored
 * before this plugin had an opinion about it -- refuses a version the user did not modify. The
 * version row is inserted before the import and the import is not in a transaction, so what they
 * are left with is a half built version.
 *
 * Importing a container somebody uploaded is different (TagManager\API::importContainerVersion)
 * and is explicitly not covered, and neither is copying, which puts a script somewhere it was
 * not before.
 */
class Container extends UpstreamContainer
{
    public function createContainerVersion($idSite, $idContainer, $idContainerVersion, $name, $description)
    {
        return SecuredTemplateConstraints::suspendedFor(function () use ($idSite, $idContainer, $idContainerVersion, $name, $description) {
            return parent::createContainerVersion($idSite, $idContainer, $idContainerVersion, $name, $description);
        });
    }
}
