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

use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\Unary\NotUnary;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
final class IsNotExpressionParser extends IsExpressionParser
{
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        return new NotUnary(parent::parse($parser, $expr, $token), $token->getLine());
    }
    public function getName() : string
    {
        return 'is not';
    }
}
