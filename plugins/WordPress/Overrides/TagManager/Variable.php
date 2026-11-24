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
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\TagManager\Model\Tag as TagModel;
use Piwik\Plugins\TagManager\Model\Variable as UpstreamVariable;
use Piwik\Validators\Exception as ValidatorException;

/**
 * Overrides Tag Manager's variable model, refusing a variable name that can be read as a
 * reference to another variable, and refusing to add, change or delete a variable that an
 * Administrator-only field uses, directly or through other variables.
 *
 * Only a variable needs this. A "{{...}}" reference names a variable, so a tag's or a trigger's
 * name is never interpolated into anything.
 */
class Variable extends UpstreamVariable
{
    /**
     * @var array{charset: string, collation: string}|false|null the character set and collation of the variable
     *                                                          name column
     */
    private static $nameCollation = null;

    public function addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);
        $this->checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, $name);

        return parent::addContainerVariable($idSite, $idContainerVersion, $type, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description = '')
    {
        $this->checkNameIsNotAVariableReference($name);
        $this->checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, $name, $idVariable);

        return parent::updateContainerVariable($idSite, $idContainerVersion, $idVariable, $name, $parameters, $defaultValue, $lookupTable, $description);
    }

    public function deleteContainerVariable($idSite, $idContainerVersion, $idVariable, $skipReferenceCheck = false)
    {
        if (!$skipReferenceCheck) {
            $this->checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, null, $idVariable);
        }

        parent::deleteContainerVariable($idSite, $idContainerVersion, $idVariable, $skipReferenceCheck);
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
     * @param mixed $name the name the variable is saved with, or null when the variable is deleted
     * @param int|null $idVariable the variable that is changed or deleted, or null for a new variable
     * @throws ValidatorException
     */
    private function checkVariableIsNotUsedByRestrictedParameters($idSite, $idContainerVersion, $name, $idVariable = null)
    {
        if (!SecuredTemplateConstraints::areRequired()) {
            return;
        }

        list($usedNames, $usedIdVariables) = $this->getVariablesUsedByRestrictedParameters(
            $idSite,
            $idContainerVersion
        );

        if (isset($idVariable) && isset($usedIdVariables[(int) $idVariable])) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerVariableUsedByRestrictedField'));
        }

        if (is_string($name) && $this->isNameInList($name, $usedNames)) {
            throw new ValidatorException(Piwik::translate('WordPress_TagManagerVariableUsedByRestrictedField'));
        }
    }

    /**
     * @param int $idSite
     * @param int $idContainerVersion
     * @return array{0: string[], 1: array<int, true>} the variable names that restricted parameters use, directly
     *                                                 or through other variables, and the IDs of the variables
     *                                                 that these names find
     */
    private function getVariablesUsedByRestrictedParameters($idSite, $idContainerVersion)
    {
        $tags = StaticContainer::get(TagModel::class)->getContainerTags($idSite, $idContainerVersion);

        $variables = $this->getContainerVariables($idSite, $idContainerVersion);

        $pending = array_merge(
            $this->findNamesInRestrictedParameters($tags, $this->getRestrictedTagParametersByType()),
            $this->findNamesInRestrictedParameters($variables, $this->getRestrictedVariableParametersByType())
        );

        $usedNames = [];
        $usedIdVariables = [];
        while (!empty($pending)) {
            $name = array_pop($pending);
            if (isset($usedNames[$name])) {
                continue;
            }

            $usedNames[$name] = (string) $name;

            $variable = $this->findVariableByName($idSite, $idContainerVersion, $name);
            if (empty($variable['idvariable']) || isset($usedIdVariables[(int) $variable['idvariable']])) {
                continue;
            }

            $usedIdVariables[(int) $variable['idvariable']] = true;

            $pending = array_merge($pending, $this->findVariableNames([
                isset($variable['parameters']) ? $variable['parameters'] : [],
                isset($variable['default_value']) ? $variable['default_value'] : null,
                isset($variable['lookup_table']) ? $variable['lookup_table'] : [],
            ]));
        }

        return [array_values($usedNames), $usedIdVariables];
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
     * @return string[] trimmed names of the variables referenced anywhere in the value
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
                $names[] = trim($name);
            }
        }

        return $names;
    }

    /**
     * Whether the name equals one of the names in the list, compared with the collation of the
     * variable name column.
     *
     * @param string $name
     * @param string[] $names
     * @return bool
     */
    private function isNameInList($name, $names)
    {
        if (empty($names)) {
            return false;
        }

        $nameCollation = self::getNameCollation();

        if (empty($nameCollation)) {
            $normalisedNames = array_map([$this, 'normaliseName'], $names);
            return in_array($this->normaliseName($name), $normalisedNames, true);
        }

        $placeholder = sprintf(
            'CONVERT(? USING %s) COLLATE %s',
            $nameCollation['charset'],
            $nameCollation['collation']
        );
        $sql = sprintf('SELECT %s IN (%s)', $placeholder, implode(', ', array_fill(0, count($names), $placeholder)));

        return 1 === (int) Db::fetchOne($sql, array_merge([$name], $names));
    }

    /**
     * @return array{charset: string, collation: string}|false the character set and collation of the variable
     *                                                          name column, or false if they are not known
     */
    private static function getNameCollation()
    {
        if (isset(self::$nameCollation)) {
            return self::$nameCollation;
        }

        self::$nameCollation = false;

        $column = Db::fetchRow(
            "SELECT CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS"
            . " WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'name'",
            [Common::prefixTable('tagmanager_variable')]
        );

        if (
            !empty($column['CHARACTER_SET_NAME'])
            && !empty($column['COLLATION_NAME'])
            && preg_match('/^[A-Za-z0-9_]+$/', $column['CHARACTER_SET_NAME'])
            && preg_match('/^[A-Za-z0-9_]+$/', $column['COLLATION_NAME'])
        ) {
            self::$nameCollation = [
                'charset' => $column['CHARACTER_SET_NAME'],
                'collation' => $column['COLLATION_NAME'],
            ];
        }

        return self::$nameCollation;
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
