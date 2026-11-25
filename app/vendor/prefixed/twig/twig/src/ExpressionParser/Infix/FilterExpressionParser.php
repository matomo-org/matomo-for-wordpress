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

use Matomo\Dependencies\Twig\Attribute\FirstClassTwigCallableReady;
use Matomo\Dependencies\Twig\ExpressionParser\AbstractExpressionParser;
use Matomo\Dependencies\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Matomo\Dependencies\Twig\ExpressionParser\InfixAssociativity;
use Matomo\Dependencies\Twig\ExpressionParser\InfixExpressionParserInterface;
use Matomo\Dependencies\Twig\ExpressionParser\PrecedenceChange;
use Matomo\Dependencies\Twig\Node\EmptyNode;
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ConstantExpression;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
/**
 * @internal
 */
final class FilterExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;
    private $readyNodes = [];
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        $stream = $parser->getStream();
        $token = $stream->expect(Token::NAME_TYPE);
        $line = $token->getLine();
        if (!$stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = new EmptyNode();
        } else {
            $arguments = $this->parseNamedArguments($parser);
        }
        $filter = $parser->getFilter($token->getValue(), $line);
        $ready = \true;
        if (!isset($this->readyNodes[$class = $filter->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }
        if (!($ready = $this->readyNodes[$class])) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigFilter" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($expr, $ready ? $filter : new ConstantExpression($filter->getName(), $line), $arguments, $line);
    }
    public function getName() : string
    {
        return '|';
    }
    public function getDescription() : string
    {
        return 'Twig filter call';
    }
    public function getPrecedence() : int
    {
        return 512;
    }
    public function getPrecedenceChange() : ?PrecedenceChange
    {
        return new PrecedenceChange('twig/twig', '3.21', 300);
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
