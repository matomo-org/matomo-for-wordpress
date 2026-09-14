<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Piwik;
use Piwik\Plugins\TagManager\Model\Variable as UpstreamVariable;
use Piwik\Validators\Exception as ValidatorException;

/**
 * Overrides Tag Manager's variable model, refusing a variable name that can be read as a
 * reference to another variable.
 *
 * Only a variable needs this. A "{{...}}" reference names a variable, so a tag's or a trigger's
 * name is never interpolated into anything.
 */
class Variable extends UpstreamVariable
{
    public function addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);

        return parent::addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);

        return parent::updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    /**
     * @param mixed $name
     * @throws ValidatorException
     */
    private function checkNameIsNotAVariableReference($name)
    {
        if (!SecuredTemplateConstraints::areRequired()) {
            return;
        }

        if (is_string($name) && preg_match('/\{\{|\}\}/', $name)) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerVariableNameBracesNotAllowed'));
        }
    }
}
