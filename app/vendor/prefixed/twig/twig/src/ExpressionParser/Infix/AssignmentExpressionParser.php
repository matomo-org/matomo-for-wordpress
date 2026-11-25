<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\ExpressionParser\Infix;

use Matomo\Dependencies\Twig\Error\SyntaxError;
use Matomo\Dependencies\Twig\ExpressionParser\InfixAssociativity;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ArrayExpression;
use Matomo\Dependencies\Twig\Node\Expression\Binary\AbstractBinary;
use Matomo\Dependencies\Twig\Node\Expression\Binary\ObjectDestructuringSetBinary;
use Matomo\Dependencies\Twig\Node\Expression\Binary\SequenceDestructuringSetBinary;
use Matomo\Dependencies\Twig\Node\Expression\Binary\SetBinary;
use Matomo\Dependencies\Twig\Node\Expression\EmptyExpression;
use Matomo\Dependencies\Twig\Node\Expression\Variable\AssignContextVariable;
use Matomo\Dependencies\Twig\Node\Expression\Variable\ContextVariable;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
class AssignmentExpressionParser extends BinaryOperatorExpressionParser
{
    public function __construct(string $name)
    {
        parent::__construct(SetBinary::class, $name, 0, InfixAssociativity::Right);
    }
    /**
     * @return AbstractBinary
     */
    public function parse(Parser $parser, AbstractExpression $left, Token $token) : AbstractExpression
    {
        if (!$left instanceof ContextVariable && !$left instanceof ArrayExpression) {
            throw new SyntaxError(\sprintf('Cannot assign to "%s", only variables can be assigned.', $left::class), $token->getLine(), $parser->getStream()->getSourceContext());
        }
        $right = $parser->parseExpression(InfixAssociativity::Left === $this->getAssociativity() ? $this->getPrecedence() + 1 : $this->getPrecedence());
        $right = match ($this->getName()) {
            '=' => $right,
            default => throw new \LogicException(\sprintf('Unknown operator: %s.', $this->getName())),
        };
        if ($left instanceof ArrayExpression) {
            $pairs = $left->getKeyValuePairs();
            $isSequence = $left->isSequence();
            if (!$pairs || $isSequence && !array_filter($pairs, static fn(array $pair): bool => !$pair['value'] instanceof EmptyExpression)) {
                throw new SyntaxError('Cannot destructure to an empty list of variables.', $token->getLine(), $parser->getStream()->getSourceContext());
            }
            foreach ($pairs as $i => $pair) {
                if ($pair['value'] instanceof ContextVariable && !$pair['value'] instanceof AssignContextVariable) {
                    $left->setNode(2 * $i + 1, new AssignContextVariable($pair['value']->getAttribute('name'), $pair['value']->getTemplateLine()));
                }
            }
            if ($isSequence) {
                return new SequenceDestructuringSetBinary($left, $right, $token->getLine());
            }
            return new ObjectDestructuringSetBinary($left, $right, $token->getLine());
        }
        return new SetBinary($left, $right, $token->getLine());
    }
    public function getDescription() : string
    {
        return 'Assignment operator';
    }
}
