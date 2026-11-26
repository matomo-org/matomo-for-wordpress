<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Node\Expression\Unary;

use Matomo\Dependencies\Twig\Compiler;
use Matomo\Dependencies\Twig\Node\Expression\ReturnBoolInterface;
use Matomo\Dependencies\Twig\Node\Expression\Test\TrueTest;
use Matomo\Dependencies\Twig\Node\Node;
class NotUnary extends AbstractUnary implements ReturnBoolInterface
{
    public function __construct(Node $node, int $lineno)
    {
        parent::__construct(TrueTest::wrap($node), $lineno);
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('!');
    }
}
