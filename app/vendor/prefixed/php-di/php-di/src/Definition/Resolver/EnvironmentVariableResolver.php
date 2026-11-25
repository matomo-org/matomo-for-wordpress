<?php

declare (strict_types=1);
namespace Matomo\Dependencies\DI\Definition\Resolver;

use Matomo\Dependencies\DI\Definition\Definition;
use Matomo\Dependencies\DI\Definition\EnvironmentVariableDefinition;
use Matomo\Dependencies\DI\Definition\Exception\InvalidDefinition;
/**
 * Resolves a environment variable definition to a value.
 *
 * @template-implements DefinitionResolver<EnvironmentVariableDefinition>
 *
 * @author James Harris <james.harris@icecave.com.au>
 */
class EnvironmentVariableResolver implements DefinitionResolver
{
    /** @var callable */
    private $variableReader;
    public function __construct(private DefinitionResolver $definitionResolver, $variableReader = null)
    {
        $this->variableReader = $variableReader ?? [$this, 'getEnvVariable'];
    }
    /**
     * Resolve an environment variable definition to a value.
     *
     * @param EnvironmentVariableDefinition $definition
     */
    public function resolve(Definition $definition, array $parameters = []) : mixed
    {
        $value = call_user_func($this->variableReader, $definition->getVariableName());
        if (\false !== $value) {
            return $value;
        }
        if (!$definition->isOptional()) {
            throw new InvalidDefinition(sprintf("The environment variable '%s' has not been defined", $definition->getVariableName()));
        }
        $value = $definition->getDefaultValue();
        // Nested definition
        if ($value instanceof Definition) {
            return $this->definitionResolver->resolve($value);
        }
        return $value;
    }
    public function isResolvable(Definition $definition, array $parameters = []) : bool
    {
        return \true;
    }
    protected function getEnvVariable(string $variableName)
    {
        return $_ENV[$variableName] ?? $_SERVER[$variableName] ?? getenv($variableName);
    }
}
