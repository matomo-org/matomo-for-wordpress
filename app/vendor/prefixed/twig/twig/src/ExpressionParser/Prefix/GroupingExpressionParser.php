<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\ExpressionParser\Prefix;

use Matomo\Dependencies\Twig\Error\SyntaxError;
use Matomo\Dependencies\Twig\ExpressionParser\AbstractExpressionParser;
use Matomo\Dependencies\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Matomo\Dependencies\Twig\ExpressionParser\PrefixExpressionParserInterface;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ListExpression;
use Matomo\Dependencies\Twig\Node\Expression\Variable\AssignContextVariable;
use Matomo\Dependencies\Twig\Node\Expression\Variable\ContextVariable;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
final class GroupingExpressionParser extends AbstractExpressionParser implements PrefixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function parse(Parser $parser, Token $token) : AbstractExpression
    {
        $stream = $parser->getStream();
        $expr = $parser->parseExpression($this->getPrecedence());
        if ($stream->nextIf(Token::PUNCTUATION_TYPE, ')')) {
            if (!$stream->test(Token::OPERATOR_TYPE, '=>')) {
                return $expr->setExplicitParentheses();
            }
            return new ListExpression([self::toAssignContextVariable($expr)], $token->getLine());
        }
        // determine if we are parsing an arrow function arguments
        if (!$stream->test(Token::PUNCTUATION_TYPE, ',')) {
            $stream->expect(Token::PUNCTUATION_TYPE, ')', 'An opened parenthesis is not properly closed');
        }
        $names = [$expr];
        while (\true) {
            if ($stream->nextIf(Token::PUNCTUATION_TYPE, ')')) {
                break;
            }
            $stream->expect(Token::PUNCTUATION_TYPE, ',');
            $token = $stream->expect(Token::NAME_TYPE);
            $names[] = new ContextVariable($token->getValue(), $token->getLine());
        }
        if (!$stream->test(Token::OPERATOR_TYPE, '=>')) {
            throw new SyntaxError('A list of variables must be followed by an arrow.', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }
        return new ListExpression(array_map(self::toAssignContextVariable(...), $names), $token->getLine());
    }
    private static function toAssignContextVariable(AbstractExpression $expr) : AssignContextVariable
    {
        if (!$expr instanceof ContextVariable) {
            throw new SyntaxError('A list must only contain variables.', $expr->getTemplateLine(), $expr->getSourceContext());
        }
        return $expr instanceof AssignContextVariable ? $expr : new AssignContextVariable($expr->getAttribute('name'), $expr->getTemplateLine());
    }
    public function getName() : string
    {
        return '(';
    }
    public function getDescription() : string
    {
        return 'Explicit group expression (a)';
    }
    public function getPrecedence() : int
    {
        return 0;
    }
}
