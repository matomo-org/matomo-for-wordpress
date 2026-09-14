<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Exception;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\TagManager\Model\Tag as UpstreamTag;
use Piwik\Plugins\TagManager\Template\Tag\TagsProvider;
use Piwik\Validators\Exception as ValidatorException;

/**
 * Tag Manager's tag model, with:
 *
 * - the tags BlockedTemplates lists refused to a user without `unfiltered_html`, and
 * - the secured templates' extra checks suspended while a tag's stored parameters are written back
 *   unchanged.
 *
 * Adding, changing and resuming are refused. Resuming counts because a paused tag is left out of
 * the generated container, so turning one back on puts a script on the site that was not being
 * served a moment earlier. A narrowed tag can be resumed if what it holds is something the user
 * would have been allowed to write, which is the same question an update of it answers.
 *
 * Pausing and deleting are left alone. Neither can serve anything that was not already being
 * served.
 *
 * updateParameters() re-validates every parameter of the tag, not just the ones being rewritten.
 *
 * Nothing here comes from the request. The parameters are the ones already in the table with one
 * variable name swapped for another, and Overrides\TagManager\Variable keeps that name from being
 * anything that could be read as a reference of its own. The only other caller is
 * UpdateHelper\NewTagParameterMigrator, which is a schema migration over stored rows; there is no
 * API method behind this. Blocked tags are not checked there for the same reason: the row is
 * already stored, and refusing would leave the rename that provoked it half applied.
 */
class Tag extends UpstreamTag
{
    public function addContainerTag($idSite, $idContainerVersion, $type, $name, $parameters, $fireTriggerIds, $blockTriggerIds, $fireLimit, $fireDelay, $priority, $startDate, $endDate, $description = '', $status = '')
    {
        BlockedTemplates::checkTagTypeIsAllowed($type);

        return parent::addContainerTag($idSite, $idContainerVersion, $type, $name, $parameters, $fireTriggerIds, $blockTriggerIds, $fireLimit, $fireDelay, $priority, $startDate, $endDate, $description, $status);
    }

    public function updateContainerTag($idSite, $idContainerVersion, $idTag, $name, $parameters, $fireTriggerIds, $blockTriggerIds, $fireLimit, $fireDelay, $priority, $startDate, $endDate, $description = '')
    {
        $tag = $this->getContainerTag($idSite, $idContainerVersion, $idTag);

        if (!empty($tag['type'])) {
            // the type cannot be changed by an update, so this is the one the tag was added with
            BlockedTemplates::checkTagTypeIsAllowed($tag['type']);
        }

        return parent::updateContainerTag($idSite, $idContainerVersion, $idTag, $name, $parameters, $fireTriggerIds, $blockTriggerIds, $fireLimit, $fireDelay, $priority, $startDate, $endDate, $description);
    }

    public function resumeContainerTag($idSite, $idContainerVersion, $idTag)
    {
        $tag = $this->getContainerTag($idSite, $idContainerVersion, $idTag);

        if (!empty($tag['type'])) {
            $this->checkTagCanBeResumed($tag);
        }

        return parent::resumeContainerTag($idSite, $idContainerVersion, $idTag);
    }

    public function updateParameters($idSite, $idContainerVersion, $idTag, $parameters)
    {
        return SecuredTemplateConstraints::suspendedFor(function () use ($idSite, $idContainerVersion, $idTag, $parameters) {
            return parent::updateParameters($idSite, $idContainerVersion, $idTag, $parameters);
        });
    }

    /**
     * Asks of a stored tag the same question updateContainerTag() asks of one being written: is
     * this a tag the current user could have put there themselves? Only its type is checked against
     * the blocked list, since those are refused outright, but a narrowed template is
     * decided on what it actually holds. For example, a Custom Image tag pointing at this site is one the user
     * is allowed to write, so there is no reason to block them from turning it back on.
     *
     * @param array $tag a stored tag, as getContainerTag() returns it
     * @throws ValidatorException if the current user could not have added this tag themselves
     */
    private function checkTagCanBeResumed(array $tag)
    {
        if (!SecuredTemplateConstraints::areRequired()) {
            return;
        }

        if (in_array($tag['type'], BlockedTemplates::getTagTypes(), true)) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerTagCannotBeResumed'));
        }

        if ($this->areStoredParametersAcceptable($tag)) {
            return;
        }

        // refused with the extra checks off as well means the stored parameters are unacceptable to
        // their own template whoever is asking. that is not a permission problem, and resuming has
        // never reported it, so it is not this override's job to start reporting either
        $refusedRegardless = SecuredTemplateConstraints::suspendedFor(function () use ($tag) {
            return !$this->areStoredParametersAcceptable($tag);
        });

        if (!$refusedRegardless) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerTagCannotBeResumed'));
        }
    }

    /**
     * @param array $tag a stored tag, as getContainerTag() returns it
     * @return bool whether every stored parameter still passes the tag's validations
     */
    private function areStoredParametersAcceptable(array $tag)
    {
        $template = StaticContainer::get(TagsProvider::class)->getTag($tag['type']);

        if (empty($template)) {
            // a tag whose template has gone is left out of the generated container, so turning it
            // back on does nothing and there is no reason to block it
            return true;
        }

        $parameters = !empty($tag['parameters']) && is_array($tag['parameters']) ? $tag['parameters'] : [];

        try {
            foreach ($template->getParameters() as $parameter) {
                $name = $parameter->getName();

                $parameter->setValue(
                    array_key_exists($name, $parameters) ? $parameters[$name] : $parameter->getDefaultValue()
                );
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
