<?php

declare (strict_types=1);
namespace Matomo\Dependencies\DI\Compiler;

use Matomo\Dependencies\DI\Factory\RequestedEntry;
/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RequestedEntryHolder implements RequestedEntry
{
    public function __construct(private string $name)
    {
    }
    public function getName() : string
    {
        return $this->name;
    }
}
