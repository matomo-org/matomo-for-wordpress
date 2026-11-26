<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Process\Exception;

use Symfony\Component\Process\Messenger\RunProcessContext;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RunProcessFailedException extends \Symfony\Component\Process\Exception\RuntimeException
{
    public function __construct(\Symfony\Component\Process\Exception\ProcessFailedException $exception, public readonly RunProcessContext $context)
    {
        parent::__construct($exception->getMessage(), $exception->getCode());
    }
}
