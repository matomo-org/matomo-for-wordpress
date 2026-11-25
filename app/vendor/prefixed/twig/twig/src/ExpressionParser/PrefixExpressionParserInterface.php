<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\ExpressionParser;

use Matomo\Dependencies\Twig\Error\SyntaxError;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
interface PrefixExpressionParserInterface extends ExpressionParserInterface
{
    /**
     * @throws SyntaxError
     */
    public function parse(Parser $parser, Token $token) : AbstractExpression;
}
