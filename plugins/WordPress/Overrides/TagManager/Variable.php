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
        $this->checkLookupOutputsAreScalar($lookupTable);

        return parent::addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);
        $this->checkLookupOutputsAreScalar($lookupTable);

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

    /**
     * @param mixed $lookupTable
     * @throws ValidatorException
     */
    private function checkLookupOutputsAreScalar($lookupTable)
    {
        if (!SecuredTemplateConstraints::areRequired() || !is_array($lookupTable)) {
            return;
        }

        foreach ($lookupTable as $entry) {
            if (is_array($entry) && isset($entry['out_value']) && !is_scalar($entry['out_value'])) {
                throw new ValidatorException(Piwik::translate('WordPress_TagManagerLookupValueNotAllowed'));
            }
        }
    }
}
