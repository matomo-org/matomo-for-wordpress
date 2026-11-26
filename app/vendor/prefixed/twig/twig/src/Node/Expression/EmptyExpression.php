<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Node\Expression;

use Matomo\Dependencies\Twig\Compiler;
/**
 * Represents an empty slot in an array.
 *
 * This is currently only used in destructuring contexts.
 *
 * @internal
 */
final class EmptyExpression extends AbstractExpression
{
    public function __construct(int $lineno)
    {
        parent::__construct([], [], $lineno);
    }
    public function compile(Compiler $compiler) : void
    {
    }
}
