<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Runtime;

use Matomo\Dependencies\Twig\Extension\RuntimeExtensionInterface;
use Matomo\Dependencies\Twig\Sandbox\SandboxInterface;
final class SandboxBridgeRuntime implements RuntimeExtensionInterface
{
    public function __construct(private SandboxInterface $sandbox)
    {
    }
    /**
     * @param array<string, mixed> $context
     */
    public function render(string $name, array $context) : string
    {
        return $this->sandbox->render($name, $context);
    }
}
