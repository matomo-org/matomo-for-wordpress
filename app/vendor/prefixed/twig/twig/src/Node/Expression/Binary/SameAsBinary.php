<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Node\Expression\Binary;

use Matomo\Dependencies\Twig\Compiler;
use Matomo\Dependencies\Twig\Node\Expression\ReturnBoolInterface;
class SameAsBinary extends AbstractBinary implements ReturnBoolInterface
{
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('===');
    }
}
