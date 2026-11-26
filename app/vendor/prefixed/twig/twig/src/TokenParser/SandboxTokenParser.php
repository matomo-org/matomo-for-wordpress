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
use Matomo\Dependencies\Twig\Node\IncludeNode;
use Matomo\Dependencies\Twig\Node\Node;
use Matomo\Dependencies\Twig\Node\SandboxNode;
use Matomo\Dependencies\Twig\Node\TextNode;
use Matomo\Dependencies\Twig\Token;
/**
 * Marks a section of a template as untrusted code that must be evaluated in the sandbox mode.
 *
 *    {% sandbox %}
 *        {% include 'user.html.twig' %}
 *    {% endsandbox %}
 *
 * @see https://twig.symfony.com/doc/api.html#sandbox-extension for details
 *
 * @internal
 */
final class SandboxTokenParser extends AbstractTokenParser
{
    public function parse(Token $token) : Node
    {
        $stream = $this->parser->getStream();
        trigger_deprecation('twig/twig', '3.15', \sprintf('The "sandbox" tag is deprecated in "%s" at line %d.', $stream->getSourceContext()->getName(), $token->getLine()));
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd'], \true);
        $stream->expect(Token::BLOCK_END_TYPE);
        // in a sandbox tag, only include tags are allowed
        if ($body instanceof IncludeNode) {
            $body->setAttribute('sandboxed', \true);
        } else {
            foreach ($body as $node) {
                if ($node instanceof TextNode && ctype_space($node->getAttribute('data'))) {
                    continue;
                }
                if (!$node instanceof IncludeNode) {
                    throw new SyntaxError('Only "include" tags are allowed within a "sandbox" section.', $node->getTemplateLine(), $stream->getSourceContext());
                }
                $node->setAttribute('sandboxed', \true);
            }
        }
        return new SandboxNode($body, $token->getLine());
    }
    public function decideBlockEnd(Token $token) : bool
    {
        return $token->test('endsandbox');
    }
    public function getTag() : string
    {
        return 'sandbox';
    }
}
