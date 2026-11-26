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
namespace Matomo\Dependencies\Twig\Node\Expression\Binary;

use Matomo\Dependencies\Twig\Compiler;
use Matomo\Dependencies\Twig\Node\Expression\ReturnBoolInterface;
use Matomo\Dependencies\Twig\Node\Expression\Test\TrueTest;
use Matomo\Dependencies\Twig\Node\Node;
class OrBinary extends AbstractBinary implements ReturnBoolInterface
{
    public function __construct(Node $left, Node $right, int $lineno)
    {
        parent::__construct(TrueTest::wrap($left), TrueTest::wrap($right), $lineno);
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('||');
    }
}
