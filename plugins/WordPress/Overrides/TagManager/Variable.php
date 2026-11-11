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
use Piwik\Plugins\TagManager\Template\Variable\MatomoConfigurationVariable;
use Piwik\Plugins\WordPress\Overrides\TagManager\SecuredTemplateFactory;
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
        $this->validateMatomoConfigurationLookup($type, $lookupTable);

        return parent::addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);
        $variable = $this->getContainerVariable($idSite, $idContainerVersion, $idVariable);
        if (!empty($variable['type'])) {
            $this->validateMatomoConfigurationLookup($variable['type'], $lookupTable);
        }

        return parent::updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    /**
     * Lookup outputs replace a variable's parameters at runtime, so validate them with the same
     * secured fields used for the top-level Matomo Configuration parameters.
     *
     * @param string $type
     * @param array $lookupTable
     */
    private function validateMatomoConfigurationLookup($type, $lookupTable)
    {
        if (!SecuredTemplateConstraints::areRequired() || $type !== MatomoConfigurationVariable::ID) {
            return;
        }

        $template = (new SecuredTemplateFactory())->matomoConfigurationVariable(new MatomoConfigurationVariable());
        $parameters = [];
        foreach ($template->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        foreach ((array) $lookupTable as $entry) {
            if (empty($entry['out_value']) || !is_array($entry['out_value'])) {
                continue;
            }
            foreach ($entry['out_value'] as $name => $value) {
                if (isset($parameters[$name])) {
                    $field = $parameters[$name]->configureField();
                    if (!empty($field->availableValues)
                        && (!is_scalar($value) || !array_key_exists((string) $value, $field->availableValues))
                    ) {
                        throw new ValidatorException(Piwik::translate('WordPress_TagManagerUrlNotAllowed'));
                    }
                    $parameters[$name]->setValue($value);
                }
            }
        }
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
