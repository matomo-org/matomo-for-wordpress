<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Config;

class DatabaseConfig extends \Piwik\Config\SectionConfig
{
    public static function getSectionName() : string
    {
        return 'database';
    }
}
