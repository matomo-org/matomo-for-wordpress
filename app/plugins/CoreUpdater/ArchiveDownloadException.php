<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CoreUpdater;

use Exception;
/**
 * Error while downloading the archive.
 */
class ArchiveDownloadException extends \Piwik\Plugins\CoreUpdater\UpdaterException
{
    public function __construct(Exception $exception)
    {
        parent::__construct($exception, array());
    }
}
