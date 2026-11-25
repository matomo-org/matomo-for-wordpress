<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Symfony\Component\Process\Messenger;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RunProcessFailedException;
use Symfony\Component\Process\Process;
/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class RunProcessMessageHandler
{
    public function __invoke(\Symfony\Component\Process\Messenger\RunProcessMessage $message) : \Symfony\Component\Process\Messenger\RunProcessContext
    {
        $process = new Process($message->command, $message->cwd, $message->env, $message->input, $message->timeout);
        try {
            return new \Symfony\Component\Process\Messenger\RunProcessContext($message, $process->mustRun());
        } catch (ProcessFailedException $e) {
            throw new RunProcessFailedException($e, new \Symfony\Component\Process\Messenger\RunProcessContext($message, $e->getProcess()));
        }
    }
}
