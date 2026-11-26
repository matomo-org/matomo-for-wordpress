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
use Matomo\Dependencies\Twig\Extension\EscaperExtension;
use Matomo\Dependencies\Twig\Node\AutoEscapeNode;
use Matomo\Dependencies\Twig\Node\BlockNode;
use Matomo\Dependencies\Twig\Node\BlockReferenceNode;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ConstantExpression;
use Matomo\Dependencies\Twig\Node\Expression\Filter\EscapeFilter;
use Matomo\Dependencies\Twig\Node\Expression\FilterExpression;
use Matomo\Dependencies\Twig\Node\Expression\OperatorEscapeInterface;
use Matomo\Dependencies\Twig\Node\ImportNode;
use Matomo\Dependencies\Twig\Node\ModuleNode;
use Matomo\Dependencies\Twig\Node\Node;
use Matomo\Dependencies\Twig\Node\Nodes;
use Matomo\Dependencies\Twig\Node\PrintNode;
use Matomo\Dependencies\Twig\NodeTraverser;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @internal
 */
final class EscaperNodeVisitor implements NodeVisitorInterface
{
    private $statusStack = [];
    private $blocks = [];
    private $safeAnalysis;
    private $traverser;
    private $defaultStrategy = \false;
    private $safeVars = [];
    private bool $usesEscaper = \false;
    public function __construct()
    {
        $this->safeAnalysis = new SafeAnalysisNodeVisitor();
    }
    public function enterNode(Node $node, Environment $env) : Node
    {
        if ($node instanceof ModuleNode) {
            if ($env->hasExtension(EscaperExtension::class) && ($defaultStrategy = $env->getExtension(EscaperExtension::class)->getDefaultStrategy($node->getTemplateName()))) {
                $this->defaultStrategy = $defaultStrategy;
            }
            $node->setAttribute('strategy', \is_string($this->defaultStrategy) ? $this->defaultStrategy : \false);
            $this->safeVars = [];
            $this->blocks = [];
            $this->usesEscaper = \false;
        } elseif ($node instanceof AutoEscapeNode) {
            $this->statusStack[] = $node->getAttribute('value');
        } elseif ($node instanceof BlockNode) {
            $this->statusStack[] = $this->blocks[$node->getAttribute('name')] ?? $this->needEscaping();
        } elseif ($node instanceof ImportNode) {
            $this->safeVars[] = $node->getNode('var')->getNode('var')->getAttribute('name');
        }
        return $node;
    }
    public function leaveNode(Node $node, Environment $env) : ?Node
    {
        if ($node instanceof ModuleNode) {
            $node->setAttribute('escaper', $this->usesEscaper);
            $this->defaultStrategy = \false;
            $this->safeVars = [];
            $this->blocks = [];
        } elseif ($node instanceof FilterExpression) {
            if ($node instanceof EscapeFilter) {
                $this->useTemplateEscaper($node);
            }
            return $this->preEscapeFilterNode($node, $env);
        } elseif ($node instanceof PrintNode && \false !== ($type = $this->needEscaping())) {
            $expression = $node->getNode('expr');
            if ($expression instanceof OperatorEscapeInterface) {
                $this->escapeConditional($expression, $env, $type);
            } else {
                $node->setNode('expr', $this->escapeExpression($expression, $env, $type));
            }
            return $node;
        }
        if ($node instanceof AutoEscapeNode || $node instanceof BlockNode) {
            array_pop($this->statusStack);
        } elseif ($node instanceof BlockReferenceNode) {
            $this->blocks[$node->getAttribute('name')] = $this->needEscaping();
        }
        return $node;
    }
    /**
     * @param AbstractExpression&OperatorEscapeInterface $expression
     */
    private function escapeConditional($expression, Environment $env, string $type) : void
    {
        foreach ($expression->getOperandNamesToEscape() as $name) {
            /** @var AbstractExpression $operand */
            $operand = $expression->getNode($name);
            if ($operand instanceof OperatorEscapeInterface) {
                $this->escapeConditional($operand, $env, $type);
            } else {
                $expression->setNode($name, $this->escapeExpression($operand, $env, $type));
            }
        }
    }
    private function escapeExpression(AbstractExpression $expression, Environment $env, string $type) : AbstractExpression
    {
        return $this->isSafeFor($type, $expression, $env) ? $expression : $this->getEscaperFilter($env, $type, $expression);
    }
    private function preEscapeFilterNode(FilterExpression $filter, Environment $env) : FilterExpression
    {
        if ($filter->hasAttribute('\Matomo\Dependencies\twig_callable')) {
            $type = $filter->getAttribute('\Matomo\Dependencies\twig_callable')->getPreEscape();
        } else {
            // legacy
            $name = $filter->getNode('filter', \false)->getAttribute('value');
            $type = $env->getFilter($name)->getPreEscape();
        }
        if (null === $type) {
            return $filter;
        }
        /** @var AbstractExpression $node */
        $node = $filter->getNode('node');
        if ($this->isSafeFor($type, $node, $env)) {
            return $filter;
        }
        $filter->setNode('node', $this->getEscaperFilter($env, $type, $node));
        return $filter;
    }
    private function isSafeFor(string $type, AbstractExpression $expression, Environment $env) : bool
    {
        $safe = $this->safeAnalysis->getSafe($expression);
        if (!$safe) {
            if (null === $this->traverser) {
                $this->traverser = new NodeTraverser($env, [$this->safeAnalysis]);
            }
            $this->safeAnalysis->setSafeVars($this->safeVars);
            $this->traverser->traverse($expression);
            $safe = $this->safeAnalysis->getSafe($expression);
        }
        return \in_array($type, $safe, \true) || \in_array('all', $safe, \true);
    }
    /**
     * @return string|false
     */
    private function needEscaping() : string|bool
    {
        if (\count($this->statusStack)) {
            return $this->statusStack[\count($this->statusStack) - 1];
        }
        return $this->defaultStrategy ?: \false;
    }
    private function getEscaperFilter(Environment $env, string $type, AbstractExpression $node) : FilterExpression
    {
        $line = $node->getTemplateLine();
        $filter = $env->getFilter('escape');
        $args = new Nodes([new ConstantExpression($type, $line), new ConstantExpression(null, $line), new ConstantExpression(\true, $line)]);
        $class = $filter->getNodeClass();
        $expression = new $class($node, $filter, $args, $line);
        if ($expression instanceof EscapeFilter) {
            $this->useTemplateEscaper($expression);
        }
        return $expression;
    }
    private function useTemplateEscaper(EscapeFilter $filter) : void
    {
        $filter->setAttribute('template_escaper', \true);
        $this->usesEscaper = \true;
    }
    public function getPriority() : int
    {
        return 0;
    }
}
