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

use Matomo\Dependencies\Twig\ExpressionParser\AbstractExpressionParser;
use Matomo\Dependencies\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Matomo\Dependencies\Twig\ExpressionParser\InfixAssociativity;
use Matomo\Dependencies\Twig\ExpressionParser\InfixExpressionParserInterface;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ArrowFunctionExpression;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
final class ArrowExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        // As the expression of the arrow function is independent from the current precedence, we want a precedence of 0
        return new ArrowFunctionExpression($parser->parseExpression(), $expr, $token->getLine());
    }
    public function getName() : string
    {
        return '=>';
    }
    public function getDescription() : string
    {
        return 'Arrow function (x => expr)';
    }
    public function getPrecedence() : int
    {
        return 250;
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
