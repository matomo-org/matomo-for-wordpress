<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Twig\Extension;

use Matomo\Dependencies\Twig\NodeVisitor\SandboxNodeVisitor;
use Matomo\Dependencies\Twig\Sandbox\Sandbox;
use Matomo\Dependencies\Twig\Sandbox\SecurityChecker;
use Matomo\Dependencies\Twig\Sandbox\SecurityNotAllowedMethodError;
use Matomo\Dependencies\Twig\Sandbox\SecurityPolicyInterface;
use Matomo\Dependencies\Twig\Sandbox\SourcePolicyInterface;
use Matomo\Dependencies\Twig\Source;
use Matomo\Dependencies\Twig\TokenParser\SandboxTokenParser;
/**
 * This extension is the wiring behind "Twig\Sandbox\Sandbox" and should not
 * be used directly, use a "Sandbox" to render untrusted templates instead.
 *
 * @internal since Twig 3.29
 */
final class SandboxExtension extends AbstractExtension
{
    private SecurityChecker $checker;
    public function __construct(SecurityPolicyInterface $policy, $sandboxed = \false, ?SourcePolicyInterface $sourcePolicy = null)
    {
        if (null !== $sourcePolicy) {
            trigger_deprecation('twig/twig', '3.27.0', 'The "%s" interface is deprecated with no replacement, do not pass an instance to "%s".', SourcePolicyInterface::class, self::class);
        }
        $this->checker = new SecurityChecker($policy, (bool) $sandboxed, $sourcePolicy);
    }
    public function getTokenParsers() : array
    {
        return [new SandboxTokenParser()];
    }
    public function getNodeVisitors() : array
    {
        return [new SandboxNodeVisitor()];
    }
    /**
     * @internal
     */
    public function getChecker() : SecurityChecker
    {
        return $this->checker;
    }
    /**
     * @deprecated since Twig 3.29, use "Twig\Sandbox\Sandbox" to render untrusted templates instead
     */
    public function enableSandbox() : void
    {
        trigger_deprecation('twig/twig', '3.29', 'The "%s()" method is deprecated, use "%s" to render untrusted templates instead.', __METHOD__, Sandbox::class);
        $this->checker->setSandboxed(\true);
    }
    /**
     * @deprecated since Twig 3.29, use "Twig\Sandbox\Sandbox" to render untrusted templates instead
     */
    public function disableSandbox() : void
    {
        trigger_deprecation('twig/twig', '3.29', 'The "%s()" method is deprecated, use "%s" to render untrusted templates instead.', __METHOD__, Sandbox::class);
        $this->checker->setSandboxed(\false);
    }
    public function isSandboxed(?Source $source = null) : bool
    {
        return $this->checker->isSandboxed($source);
    }
    /**
     * @deprecated since Twig 3.29, use "Twig\Sandbox\Sandbox" to render untrusted templates instead
     */
    public function isSandboxedGlobally() : bool
    {
        trigger_deprecation('twig/twig', '3.29', 'The "%s()" method is deprecated, use "%s" to render untrusted templates instead.', __METHOD__, Sandbox::class);
        return $this->checker->isSandboxedGlobally();
    }
    public function setSecurityPolicy(SecurityPolicyInterface $policy) : void
    {
        $this->checker->setSecurityPolicy($policy);
    }
    public function getSecurityPolicy() : SecurityPolicyInterface
    {
        return $this->checker->getSecurityPolicy();
    }
    public function checkSecurity($tags, $filters, $functions, $tests = [], $source = null) : void
    {
        // BC: previous signature was checkSecurity($tags, $filters, $functions, ?Source $source = null);
        // detect a legacy call where the 4th positional argument was the Source.
        if ($tests instanceof Source || null === $tests && \func_num_args() < 5) {
            trigger_deprecation('twig/twig', '3.28', 'Passing a "Twig\\Source" as the 4th argument of "%s()" is deprecated; pass an array of tests instead.', __METHOD__);
            $source = $tests;
            $tests = [];
        }
        $this->checker->checkSecurity($tags, $filters, $functions, $tests, $source);
    }
    public function checkMethodAllowed($obj, $method, int $lineno = -1, ?Source $source = null) : void
    {
        $this->checker->checkMethodAllowed($obj, $method, $lineno, $source);
    }
    public function checkPropertyAllowed($obj, $property, int $lineno = -1, ?Source $source = null) : void
    {
        $this->checker->checkPropertyAllowed($obj, $property, $lineno, $source);
    }
    /**
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureToStringAllowed($obj, int $lineno = -1, ?Source $source = null)
    {
        return $this->checker->ensureToStringAllowed($obj, $lineno, $source);
    }
    /**
     * Materialises a spread operand and runs the policy on every element.
     *
     * @internal
     *
     * @throws SecurityNotAllowedMethodError
     */
    public function ensureSpreadAllowed(iterable $obj, int $lineno = -1, ?Source $source = null) : array
    {
        return $this->checker->ensureSpreadAllowed($obj, $lineno, $source);
    }
}
