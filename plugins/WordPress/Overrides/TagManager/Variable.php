<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;
use Piwik\Plugins\TagManager\Model\Tag as TagModel;
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
        $this->checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, [$name]);

        return parent::addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);
        $this->checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, [$name], $idVariable);

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
     * @param int $idSite
     * @param int $idContainerVersion
     * @param mixed[] $names
     * @param int|null $idVariable
     * @throws ValidatorException
     */
    private function checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, $names, $idVariable = null)
    {
        if (!SecuredTemplateConstraints::areRequired()) {
            return;
        }

        if (isset($idVariable)) {
            $variable = $this->getContainerVariable($idSite, $idContainerVersion, $idVariable);
            if (!empty($variable['name'])) {
                $names[] = $variable['name'];
            }
        }

        $usedNames = $this->getNamesUsedByRestrictedParameters($idSite, $idContainerVersion);

        foreach ($names as $name) {
            if (is_string($name) && isset($usedNames[$this->normaliseName($name)])) {
                throw new ValidatorException(Piwik::translate('WordPress_TagManagerVariableUsedByRestrictedField'));
            }
        }
    }

    /**
     * @param int $idSite
     * @param int $idContainerVersion
     * @return array<string, true> normalised names of the variables that restricted parameters use, directly or through other variables
     */
    private function getNamesUsedByRestrictedParameters($idSite, $idContainerVersion)
    {
        $variablesByName = [];
        foreach ($this->getContainerVariables($idSite, $idContainerVersion) as $variable) {
            $variablesByName[$this->normaliseName($variable['name'])] = $variable;
        }

        $tags = StaticContainer::get(TagModel::class)->getContainerTags($idSite, $idContainerVersion);

        $pending = array_merge(
            $this->findNamesInRestrictedParameters($tags, $this->getRestrictedTagParametersByType()),
            $this->findNamesInRestrictedParameters($variablesByName, $this->getRestrictedVariableParametersByType())
        );

        $usedNames = [];
        while (!empty($pending)) {
            $name = array_pop($pending);
            if (isset($usedNames[$name])) {
                continue;
            }

            $usedNames[$name] = true;

            if (isset($variablesByName[$name])) {
                $variable = $variablesByName[$name];
                $pending = array_merge($pending, $this->findVariableNames([
                    isset($variable['parameters']) ? $variable['parameters'] : [],
                    isset($variable['default_value']) ? $variable['default_value'] : null,
                    isset($variable['lookup_table']) ? $variable['lookup_table'] : [],
                ]));
            }
        }

        return $usedNames;
    }

    /**
     * @param array[] $entities
     * @param array<string, string[]|true> $restrictedParametersByType
     * @return string[]
     */
    private function findNamesInRestrictedParameters($entities, $restrictedParametersByType)
    {
        $names = [];

        foreach ($entities as $entity) {
            $type = isset($entity['type']) ? $entity['type'] : '';
            if (!isset($restrictedParametersByType[$type]) || empty($entity['parameters']) || !is_array($entity['parameters'])) {
                continue;
            }

            $parameters = $entity['parameters'];
            if (true !== $restrictedParametersByType[$type]) {
                $parameters = array_intersect_key($parameters, array_flip($restrictedParametersByType[$type]));
            }

            $names = array_merge($names, $this->findVariableNames($parameters));
        }

        return $names;
    }

    /**
     * @return array<string, string[]|true>
     */
    private function getRestrictedTagParametersByType()
    {
        $byType = $this->getParametersByTemplateId(SecuredTemplateFactory::getRestrictedTagParameters());

        foreach (BlockedTemplates::getTagTypes() as $tagType) {
            $byType[$tagType] = true;
        }

        return $byType;
    }

    /**
     * @return array<string, string[]>
     */
    private function getRestrictedVariableParametersByType()
    {
        return $this->getParametersByTemplateId(SecuredTemplateFactory::getRestrictedVariableParameters());
    }

    /**
     * @param array<class-string, string[]> $parametersByClass
     * @return array<string, string[]>
     */
    private function getParametersByTemplateId($parametersByClass)
    {
        $byId = [];

        foreach ($parametersByClass as $className => $parameterNames) {
            if (class_exists($className)) {
                $byId[StaticContainer::get($className)->getId()] = $parameterNames;
            }
        }

        return $byId;
    }

    /**
     * @param mixed $value
     * @return string[] normalised names of the variables referenced anywhere in the value
     */
    private function findVariableNames($value)
    {
        $names = [];

        if (is_array($value)) {
            foreach ($value as $item) {
                $names = array_merge($names, $this->findVariableNames($item));
            }
        } elseif (is_string($value) && preg_match_all('/\{\{(?!\{)((?:(?!\{\{).)*?)\}\}/s', $value, $matches)) {
            foreach ($matches[1] as $name) {
                $names[] = $this->normaliseName($name);
            }
        }

        return $names;
    }

    /**
     * @param string $name
     * @return string
     */
    private function normaliseName($name)
    {
        return Common::mb_strtolower(trim($name));
    }
}
