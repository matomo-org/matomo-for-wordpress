<?php

declare (strict_types=1);
namespace Matomo\Dependencies\DI\Definition\Resolver;

use Matomo\Dependencies\DI\Definition\Definition;
use Matomo\Dependencies\DI\Definition\InstanceDefinition;
use Matomo\Dependencies\DI\DependencyException;
use Matomo\Dependencies\Psr\Container\NotFoundExceptionInterface;
/**
 * Injects dependencies on an existing instance.
 *
 * @template-implements DefinitionResolver<InstanceDefinition>
 *
 * @since 5.0
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class InstanceInjector extends ObjectCreator implements DefinitionResolver
{
    /**
     * Injects dependencies on an existing instance.
     *
     * @param InstanceDefinition $definition
     * @psalm-suppress ImplementedParamTypeMismatch
     */
    public function resolve(Definition $definition, array $parameters = []) : ?object
    {
        /** @psalm-suppress InvalidCatch */
        try {
            $this->injectMethodsAndProperties($definition->getInstance(), $definition->getObjectDefinition());
        } catch (NotFoundExceptionInterface $e) {
            $message = sprintf('Error while injecting dependencies into %s: %s', get_class($definition->getInstance()), $e->getMessage());
            throw new DependencyException($message, 0, $e);
        }
        return $definition;
    }
    public function isResolvable(Definition $definition, array $parameters = []) : bool
    {
        return \true;
    }
}
