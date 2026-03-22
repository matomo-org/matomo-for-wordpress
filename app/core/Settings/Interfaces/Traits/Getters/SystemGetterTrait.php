<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Settings\Interfaces\Traits\Getters;

use Piwik\Piwik;
use Piwik\Settings\Plugin\SystemSetting;
/**
 * @template T of mixed
 *
 * @phpstan-require-implements \Piwik\Settings\Interfaces\SystemSettingInterface<T>
 */
trait SystemGetterTrait
{
    public static function getSystemSetting() : SystemSetting
    {
        return new SystemSetting(self::getSystemName(), self::getSystemDefaultValue(), self::getSystemType(), Piwik::getPluginNameOfMatomoClass(static::class));
    }
    /**
     * @return T
     */
    public static function getSystemValue()
    {
        return self::getSystemSetting()->getValue();
    }
    /**
     * @return T
     */
    protected static abstract function getSystemDefaultValue();
    protected static abstract function getSystemName() : string;
    protected static abstract function getSystemType() : string;
    /**
     * @deprecated Will be removed in 6.0 in favour of making getSystemName public
     */
    public static function getSystemSettingShortName() : string
    {
        return self::getSystemName();
    }
}
