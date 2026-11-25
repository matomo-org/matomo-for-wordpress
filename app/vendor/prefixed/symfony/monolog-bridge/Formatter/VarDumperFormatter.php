<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Matomo\Dependencies\Symfony\Bridge\Monolog\Formatter;

use Matomo\Dependencies\Monolog\Formatter\FormatterInterface;
use Matomo\Dependencies\Monolog\LogRecord;
use Matomo\Dependencies\Symfony\Component\VarDumper\Cloner\VarCloner;
/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 *
 * @final since Symfony 6.1
 */
class VarDumperFormatter implements FormatterInterface
{
    use CompatibilityFormatter;
    private VarCloner $cloner;
    public function __construct(?VarCloner $cloner = null)
    {
        $this->cloner = $cloner ?? new VarCloner();
    }
    private function doFormat(array|LogRecord $record) : mixed
    {
        if ($record instanceof LogRecord) {
            $record = $record->toArray();
        }
        $record['context'] = $this->cloner->cloneVar($record['context']);
        $record['extra'] = $this->cloner->cloneVar($record['extra']);
        return $record;
    }
    public function formatBatch(array $records) : mixed
    {
        foreach ($records as $k => $record) {
            $record[$k] = $this->format($record);
        }
        return $records;
    }
}
