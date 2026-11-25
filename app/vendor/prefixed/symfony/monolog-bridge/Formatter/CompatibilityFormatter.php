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

use Matomo\Dependencies\Monolog\Logger;
use Matomo\Dependencies\Monolog\LogRecord;
if (Logger::API >= 3) {
    /**
     * The base class for compatibility between Monolog 3 LogRecord and Monolog 1/2 array records.
     *
     * @author Jordi Boggiano <j.boggiano@seld.be>
     *
     * @internal
     */
    trait CompatibilityFormatter
    {
        private abstract function doFormat(array|LogRecord $record) : mixed;
        public function format(LogRecord $record) : mixed
        {
            return $this->doFormat($record);
        }
    }
} else {
    /**
     * The base class for compatibility between Monolog 3 LogRecord and Monolog 1/2 array records.
     *
     * @author Jordi Boggiano <j.boggiano@seld.be>
     *
     * @internal
     */
    trait CompatibilityFormatter
    {
        private abstract function doFormat(array|LogRecord $record) : mixed;
        public function format(array $record) : mixed
        {
            return $this->doFormat($record);
        }
    }
}
