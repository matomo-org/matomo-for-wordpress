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
use Matomo\Dependencies\Twig\Node\CoercesChildrenToStringInterface;
use Matomo\Dependencies\Twig\Node\Expression\ReturnBoolInterface;
class LessEqualBinary extends AbstractBinary implements ReturnBoolInterface, CoercesChildrenToStringInterface
{
    public function compile(Compiler $compiler) : void
    {
        if (\PHP_VERSION_ID >= 80000) {
            parent::compile($compiler);
            return;
        }
        $compiler->raw('(0 >= CoreExtension::compare(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw('))');
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('<=');
    }
    public function getStringCoercedChildNames() : array
    {
        return ['left', 'right'];
    }
}
