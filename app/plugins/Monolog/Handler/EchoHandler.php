<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\Monolog\Handler;

use Matomo\Dependencies\Monolog\Formatter\FormatterInterface;
use Matomo\Dependencies\Monolog\Formatter\LineFormatter;
use Matomo\Dependencies\Monolog\Handler\AbstractProcessingHandler;
use Matomo\Dependencies\Monolog\LogRecord;
/**
 * Simply echos all messages.
 */
class EchoHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record) : void
    {
        if (isset($record['formatted'])) {
            $message = $record['formatted'];
        } else {
            $message = $record['level_name'] . ': ' . $record['message'];
        }
        echo $message . "\n";
    }
    protected function getDefaultFormatter() : FormatterInterface
    {
        // monolog 2 changed the default LineFormatter date format to ISO 8601 with microseconds.
        // Keep the previous "Y-m-d H:i:s" format so the (user facing) log output stays unchanged.
        return new LineFormatter(null, 'Y-m-d H:i:s');
    }
}
