<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\TokenParser;

use Matomo\Dependencies\Twig\Node\ConfigNode;
use Matomo\Dependencies\Twig\Node\Node;
use Matomo\Dependencies\Twig\Token;
/**
 * Extends a template by another one.
 *
 *  {% extends "base.html" %}
 *
 * @internal
 */
final class ExtendsTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $stream = $this->parser->getStream();
        $this->parser->setParent($this->parser->parseExpression(), \false);
        $stream->expect(Token::BLOCK_END_TYPE);
        return new ConfigNode($token->getLine());
    }
    public function getTag() : string
    {
        return 'extends';
    }
}
