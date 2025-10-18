<?php

namespace Piwik\Settings\Interfaces\Traits\Getters;

use Piwik\Config;
use Piwik\Settings\Interfaces\ConfigSettingInterface;
/**
 * @template T of mixed
 *
 * @phpstan-require-implements ConfigSettingInterface
 */
trait ConfigGetterTrait
{
    /**
     * @return T|null
     */
    public static function getConfigValue()
    {
        $config = Config::getInstance()->{self::getConfigSection()};
        if (is_null($config) || !array_key_exists(self::getConfigSettingName(), $config)) {
            return null;
        }
        return $config[self::getConfigSettingName()];
    }
    protected static abstract function getConfigSection() : string;
    protected static abstract function getConfigSettingName() : string;
}
