<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Test;

use PHPUnit\Framework\TestCase;
use Matomo\Dependencies\Twig\Compiler;
use Matomo\Dependencies\Twig\Environment;
use Matomo\Dependencies\Twig\Loader\ArrayLoader;
use Matomo\Dependencies\Twig\Node\Node;
abstract class NodeTestCase extends TestCase
{
    public abstract function getTests();
    /**
     * @dataProvider getTests
     */
    public function testCompile($node, $source, $environment = null, $isPattern = false)
    {
        $this->assertNodeCompilation($source, $node, $environment, $isPattern);
    }
    public function assertNodeCompilation($source, Node $node, Environment $environment = null, $isPattern = false)
    {
        $compiler = $this->getCompiler($environment);
        $compiler->compile($node);
        if ($isPattern) {
            $this->assertStringMatchesFormat($source, trim($compiler->getSource()));
        } else {
            $this->assertEquals($source, trim($compiler->getSource()));
        }
    }
    protected function getCompiler(Environment $environment = null)
    {
        return new Compiler($environment ?? $this->getEnvironment());
    }
    protected function getEnvironment()
    {
        return new Environment(new ArrayLoader([]));
    }
    protected function getVariableGetter($name, $line = false)
    {
        $line = $line > 0 ? "// line {$line}\n" : '';
        return sprintf('%s($context["%s"] ?? null)', $line, $name);
    }
    protected function getAttributeGetter()
    {
        return '\Matomo\Dependencies\twig_get_attribute($this->env, $this->source, ';
    }
}
