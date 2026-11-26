<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\NodeVisitor;

use Matomo\Dependencies\Twig\Environment;
use Matomo\Dependencies\Twig\Node\Expression\BlockReferenceExpression;
use Matomo\Dependencies\Twig\Node\Expression\ConstantExpression;
use Matomo\Dependencies\Twig\Node\Expression\FilterExpression;
use Matomo\Dependencies\Twig\Node\Expression\FunctionExpression;
use Matomo\Dependencies\Twig\Node\Expression\GetAttrExpression;
use Matomo\Dependencies\Twig\Node\Expression\MacroReferenceExpression;
use Matomo\Dependencies\Twig\Node\Expression\MethodCallExpression;
use Matomo\Dependencies\Twig\Node\Expression\OperatorEscapeInterface;
use Matomo\Dependencies\Twig\Node\Expression\ParentExpression;
use Matomo\Dependencies\Twig\Node\Expression\Variable\ContextVariable;
use Matomo\Dependencies\Twig\Node\Node;
/**
 * @internal
 */
final class SafeAnalysisNodeVisitor implements NodeVisitorInterface
{
    /**
     * @var \WeakMap<Node, array>
     */
    private \WeakMap $data;
    private $safeVars = [];
    public function __construct()
    {
        $this->data = new \WeakMap();
    }
    public function setSafeVars(array $safeVars) : void
    {
        $this->safeVars = $safeVars;
    }
    /**
     * @return array
     */
    public function getSafe(Node $node)
    {
        $safe = $this->data[$node] ?? [];
        if (\in_array('html_attr', $safe, \true)) {
            $safe[] = 'html';
            $safe[] = 'html_attr_relaxed';
        }
        if (\in_array('html_attr_relaxed', $safe, \true)) {
            $safe[] = 'html';
        }
        return $safe;
    }
    private function setSafe(Node $node, array $safe) : void
    {
        $this->data[$node] = $safe;
    }
    public function enterNode(Node $node, Environment $env) : Node
    {
        return $node;
    }
    public function leaveNode(Node $node, Environment $env) : ?Node
    {
        if ($node instanceof ConstantExpression) {
            // constants are marked safe for all
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof BlockReferenceExpression) {
            // blocks are safe by definition
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof ParentExpression) {
            // parent block is safe by definition
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof OperatorEscapeInterface) {
            // intersect safeness of operands
            $operands = $node->getOperandNamesToEscape();
            if (2 < \count($operands)) {
                throw new \LogicException(\sprintf('Operators with more than 2 operands are not supported yet, got %d.', \count($operands)));
            } elseif (2 === \count($operands)) {
                $safe = $this->intersectSafe($this->getSafe($node->getNode($operands[0])), $this->getSafe($node->getNode($operands[1])));
                $this->setSafe($node, $safe);
            }
        } elseif ($node instanceof FilterExpression) {
            // filter expression is safe when the filter is safe
            if ($node->hasAttribute('\Matomo\Dependencies\twig_callable')) {
                $filter = $node->getAttribute('\Matomo\Dependencies\twig_callable');
            } else {
                // legacy
                $filter = $env->getFilter($node->getAttribute('name'));
            }
            if ($filter) {
                $safe = $filter->getSafe($node->getNode('arguments'));
                if (null === $safe) {
                    trigger_deprecation('twig/twig', '3.16', 'The "%s::getSafe()" method should not return "null" anymore, return "[]" instead.', $filter::class);
                    $safe = [];
                }
                if (!$safe) {
                    $safe = $this->intersectSafe($this->getSafe($node->getNode('node')), $filter->getPreservesSafety());
                }
                $this->setSafe($node, $safe);
            }
        } elseif ($node instanceof FunctionExpression) {
            // function expression is safe when the function is safe
            if ($node->hasAttribute('\Matomo\Dependencies\twig_callable')) {
                $function = $node->getAttribute('\Matomo\Dependencies\twig_callable');
            } else {
                // legacy
                $function = $env->getFunction($node->getAttribute('name'));
            }
            if ($function) {
                $safe = $function->getSafe($node->getNode('arguments'));
                if (null === $safe) {
                    trigger_deprecation('twig/twig', '3.16', 'The "%s::getSafe()" method should not return "null" anymore, return "[]" instead.', $function::class);
                    $safe = [];
                }
                $this->setSafe($node, $safe);
            }
        } elseif ($node instanceof MethodCallExpression || $node instanceof MacroReferenceExpression) {
            // all macro calls are safe
            $this->setSafe($node, ['all']);
        } elseif ($node instanceof GetAttrExpression && $node->getNode('node') instanceof ContextVariable) {
            $name = $node->getNode('node')->getAttribute('name');
            if (\in_array($name, $this->safeVars, \true)) {
                $this->setSafe($node, ['all']);
            }
        }
        return $node;
    }
    private function intersectSafe(array $a, array $b) : array
    {
        if (!$a || !$b) {
            return [];
        }
        if (\in_array('all', $a, \true)) {
            return $b;
        }
        if (\in_array('all', $b, \true)) {
            return $a;
        }
        return array_intersect($a, $b);
    }
    public function getPriority() : int
    {
        return 0;
    }
}
