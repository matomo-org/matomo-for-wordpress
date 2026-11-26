<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\TokenParser;

use Matomo\Dependencies\Twig\Error\SyntaxError;
use Matomo\Dependencies\Twig\Node\BodyNode;
use Matomo\Dependencies\Twig\Node\Expression\ArrayExpression;
use Matomo\Dependencies\Twig\Node\Expression\ConstantExpression;
use Matomo\Dependencies\Twig\Node\Expression\TempNameExpression;
use Matomo\Dependencies\Twig\Node\Expression\Unary\NegUnary;
use Matomo\Dependencies\Twig\Node\Expression\Unary\PosUnary;
use Matomo\Dependencies\Twig\Node\Expression\Variable\LocalVariable;
use Matomo\Dependencies\Twig\Node\MacroDeclarationNode;
use Matomo\Dependencies\Twig\Node\MacroNode;
use Matomo\Dependencies\Twig\Node\Node;
use Matomo\Dependencies\Twig\Node\NodeDocumentation;
use Matomo\Dependencies\Twig\Token;
/**
 * Defines a macro.
 *
 *   {% macro input(name, value, type, size) %}
 *      <input type="{{ type|default('text') }}" name="{{ name }}" value="{{ value|e }}" size="{{ size|default(20) }}" />
 *   {% endmacro %}
 *
 * @internal
 */
final class MacroTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();
        $name = $stream->expect(Token::NAME_TYPE)->getValue();
        [$arguments, $variadicName] = $this->parseDefinition($name);
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->parser->pushLocalScope();
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        if ($token = $stream->nextIf(Token::NAME_TYPE)) {
            $value = $token->getValue();
            if ($value != $name) {
                throw new SyntaxError(\sprintf('Expected endmacro for macro "%s" (but "%s" given).', $name, $value), $stream->getCurrent()->getLine(), $stream->getSourceContext());
            }
        }
        $this->parser->popLocalScope();
        $stream->expect(Token::BLOCK_END_TYPE);
        $this->parser->setMacro($name, $macro = new MacroNode($name, new BodyNode([$body]), $arguments, $lineno, $variadicName));
        $this->parser->setDocumentationTarget($macro);
        return new MacroDeclarationNode($name, $lineno);
    }
    public function decideBlockEnd(Token $token) : bool
    {
        return $token->test('endmacro');
    }
    public function getTag() : string
    {
        return 'macro';
    }
    /**
     * @return array{ArrayExpression, string|null}
     */
    private function parseDefinition(string $macroName) : array
    {
        $arguments = new ArrayExpression([], $this->parser->getCurrentToken()->getLine());
        $variadicName = null;
        $stream = $this->parser->getStream();
        $stream->expect(Token::OPERATOR_TYPE, '(', 'A list of arguments must begin with an opening parenthesis');
        while (!$stream->test(Token::PUNCTUATION_TYPE, ')')) {
            if (\count($arguments)) {
                $stream->expect(Token::PUNCTUATION_TYPE, ',', 'Arguments must be separated by a comma');
                // if the comma above was a trailing comma, early exit the argument parse loop
                if ($stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    break;
                }
            }
            if ($stream->nextIf(Token::OPERATOR_TYPE, '...')) {
                $token = $stream->expect(Token::NAME_TYPE, null, 'A variadic argument must be a name');
                $variadicName = (new LocalVariable($token->getValue(), $token->getLine()))->getAttribute('name');
                if (str_starts_with($variadicName, TempNameExpression::RESERVED_NAME_PREFIX)) {
                    $variadicName = substr($variadicName, \strlen(TempNameExpression::RESERVED_NAME_PREFIX));
                }
                if ($stream->test(Token::OPERATOR_TYPE, '=')) {
                    throw new SyntaxError(\sprintf('The variadic argument "%s" in macro "%s" cannot have a default value.', $variadicName, $macroName), $token->getLine(), $stream->getSourceContext());
                }
                if ($stream->nextIf(Token::PUNCTUATION_TYPE, ',') && !$stream->test(Token::PUNCTUATION_TYPE, ')')) {
                    throw new SyntaxError(\sprintf('The variadic argument "%s" in macro "%s" must be the last one.', $variadicName, $macroName), $token->getLine(), $stream->getSourceContext());
                }
                break;
            }
            $token = $stream->expect(Token::NAME_TYPE, null, 'An argument must be a name');
            $name = new LocalVariable($token->getValue(), $token->getLine());
            NodeDocumentation::add($name, $token);
            if ($token = $stream->nextIf(Token::OPERATOR_TYPE, '=')) {
                $default = $this->parser->parseExpression();
            } else {
                $default = new ConstantExpression(null, $this->parser->getCurrentToken()->getLine());
                $default->setAttribute('is_implicit', \true);
            }
            if (!$this->checkConstantExpression($default)) {
                throw new SyntaxError('A default value for an argument must be a constant (a boolean, a string, a number, a sequence, or a mapping).', $token->getLine(), $stream->getSourceContext());
            }
            $arguments->addElement($default, $name);
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ')', 'A list of arguments must be closed by a parenthesis');
        return [$arguments, $variadicName];
    }
    // checks that the node only contains "constant" elements
    private function checkConstantExpression(Node $node) : bool
    {
        if (!($node instanceof ConstantExpression || $node instanceof ArrayExpression || $node instanceof NegUnary || $node instanceof PosUnary)) {
            return \false;
        }
        foreach ($node as $n) {
            if (!$this->checkConstantExpression($n)) {
                return \false;
            }
        }
        return \true;
    }
}
