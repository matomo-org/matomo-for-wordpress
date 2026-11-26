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
use Matomo\Dependencies\Twig\Node\Expression\AbstractExpression;
use Matomo\Dependencies\Twig\Node\Expression\ArrayExpression;
use Matomo\Dependencies\Twig\Node\Expression\MacroReferenceExpression;
use Matomo\Dependencies\Twig\Node\Expression\NameExpression;
use Matomo\Dependencies\Twig\Node\Nodes;
use Matomo\Dependencies\Twig\Parser;
use Matomo\Dependencies\Twig\Token;
use Matomo\Dependencies\Twig\TwigTest;
/**
 * @internal
 */
class IsExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;
    private $readyNodes = [];
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        $stream = $parser->getStream();
        $test = $parser->getTest($token->getLine());
        $arguments = null;
        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = $this->parseNamedArguments($parser);
        } elseif ($test->hasOneMandatoryArgument()) {
            $arguments = new Nodes([0 => $parser->parseExpression($this->getPrecedence())]);
        }
        if ('defined' === $test->getName()) {
            if ($expr instanceof NameExpression && null !== ($alias = $parser->getImportedSymbol('function', $expr->getAttribute('name')))) {
                $expr = new MacroReferenceExpression($alias['node']->getNode('var'), $alias['name'], new ArrayExpression([], $expr->getTemplateLine()), $expr->getTemplateLine());
                $expr->setHasCallParentheses(\false);
            }
            if ($expr instanceof MacroReferenceExpression && $expr->hasCallParentheses()) {
                trigger_deprecation('twig/twig', '3.29', 'Using parentheses when testing a macro with the "defined" test is deprecated and will throw a SyntaxError in Twig 4.0; remove the parentheses after the macro name in "%s" at line %d.', $stream->getSourceContext()->getName(), $expr->getTemplateLine());
            }
        }
        $ready = $test instanceof TwigTest;
        if (!isset($this->readyNodes[$class = $test->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }
        if (!($ready = $this->readyNodes[$class])) {
            trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigTest" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($expr, $ready ? $test : $test->getName(), $arguments, $stream->getCurrent()->getLine());
    }
    public function getPrecedence() : int
    {
        return 100;
    }
    public function getName() : string
    {
        return 'is';
    }
    public function getDescription() : string
    {
        return 'Twig tests';
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
