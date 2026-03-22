<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Settings\Interfaces\Traits\Getters;

use Piwik\Piwik;
use Piwik\Settings\Measurable\MeasurableSetting;
use Piwik\Settings\Measurable\MeasurableProperty;
/**
 * @template T of mixed
 *
 * @phpstan-require-implements \Piwik\Settings\Interfaces\MeasurableSettingInterface<T>
 */
trait MeasurableGetterTrait
{
    public static function getMeasurableSetting(int $idSite, bool $isProperty = \false)
    {
        if ($isProperty) {
            return new MeasurableProperty(self::getMeasurableName(), self::getMeasurableDefaultValue(), self::getMeasurableType(), Piwik::getPluginNameOfMatomoClass(static::class), $idSite);
        }
        return new MeasurableSetting(self::getMeasurableName(), self::getMeasurableDefaultValue(), self::getMeasurableType(), Piwik::getPluginNameOfMatomoClass(static::class), $idSite);
    }
    /**
     * @return T
     */
    public static function getMeasurableValue(int $idSite, bool $isProperty = \false)
    {
        return self::getMeasurableSetting($idSite, $isProperty)->getValue();
    }
    /**
     * @return T
     */
    protected static abstract function getMeasurableDefaultValue();
    protected static abstract function getMeasurableName() : string;
    protected static abstract function getMeasurableType() : string;
    /**
     * @deprecated Will be removed in 6.0 in favour of making getMeasurableName public
     */
    public static function getMeasurableSettingShortName() : string
    {
        return self::getMeasurableName();
    }
}
