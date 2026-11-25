<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Symfony\Component\HttpFoundation\RequestMatcher;

use Matomo\Dependencies\Symfony\Component\HttpFoundation\Request;
use Matomo\Dependencies\Symfony\Component\HttpFoundation\RequestMatcherInterface;
/**
 * Checks the Request content is valid JSON.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IsJsonRequestMatcher implements RequestMatcherInterface
{
    public function matches(Request $request) : bool
    {
        return json_validate($request->getContent());
    }
}
