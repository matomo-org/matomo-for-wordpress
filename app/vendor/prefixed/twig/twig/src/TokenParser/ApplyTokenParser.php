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

use Matomo\Dependencies\Twig\ExpressionParser\Infix\FilterExpressionParser;
use Matomo\Dependencies\Twig\Node\Expression\Variable\LocalVariable;
use Matomo\Dependencies\Twig\Node\Node;
use Matomo\Dependencies\Twig\Node\Nodes;
use Matomo\Dependencies\Twig\Node\PrintNode;
use Matomo\Dependencies\Twig\Node\SetNode;
use Matomo\Dependencies\Twig\Token;
/**
 * Applies filters on a section of a template.
 *
 *   {% apply upper %}
 *      This text becomes uppercase
 *   {% endapply %}
 *
 * @internal
 */
final class ApplyTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $lineno = $token->getLine();
        $ref = new LocalVariable(null, $lineno);
        $filter = $ref;
        $op = $this->parser->getEnvironment()->getExpressionParsers()->getByClass(FilterExpressionParser::class);
        while (\true) {
            $filter = $op->parse($this->parser, $filter, $this->parser->getCurrentToken());
            if (!$this->parser->getStream()->test(Token::OPERATOR_TYPE, '|')) {
                break;
            }
            $this->parser->getStream()->next();
        }
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideApplyEnd'], \true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);
        return new Nodes([new SetNode(\true, $ref, $body, $lineno), new PrintNode($filter, $lineno)], $lineno);
    }
    public function decideApplyEnd(Token $token) : bool
    {
        return $token->test('endapply');
    }
    public function getTag() : string
    {
        return 'apply';
    }
}
