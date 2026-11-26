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

use Matomo\Dependencies\Twig\ExpressionParser\AbstractExpressionParser;
use Matomo\Dependencies\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Matomo\Dependencies\Twig\ExpressionParser\PrecedenceChange;
use Matomo\Dependencies\Twig\ExpressionParser\PrefixExpressionParserInterface;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\Unary\AbstractUnary;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
final class UnaryOperatorExpressionParser extends AbstractExpressionParser implements PrefixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function __construct(
        /** @var class-string<AbstractUnary> */
        private string $nodeClass,
        private string $name,
        private int $precedence,
        private ?PrecedenceChange $precedenceChange = null,
        private ?string $description = null,
        private array $aliases = [],
        private ?int $operandPrecedence = null
    )
    {
    }
    /**
     * @return AbstractUnary
     */
    public function parse(Parser $parser, Token $token) : AbstractExpression
    {
        return new $this->nodeClass($parser->parseExpression($this->operandPrecedence ?? $this->precedence), $token->getLine());
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getDescription() : string
    {
        return $this->description ?? '';
    }
    public function getPrecedence() : int
    {
        return $this->precedence;
    }
    public function getPrecedenceChange() : ?PrecedenceChange
    {
        return $this->precedenceChange;
    }
    public function getAliases() : array
    {
        return $this->aliases;
    }
}
