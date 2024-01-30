<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Container;

use Matomo\Dependencies\DI\Definition\Exception\InvalidDefinition;
use Matomo\Dependencies\DI\Definition\Source\DefinitionSource;
use Matomo\Dependencies\DI\Definition\ValueDefinition;
use Piwik\Application\Kernel\GlobalSettingsProvider;
/**
 * Expose the INI config into PHP-DI.
 *
 * The INI config can be used by prefixing `ini.` before the setting we want to get:
 *
 *     $maintenanceMode = $container->get('ini.General.maintenance_mode');
 */
class IniConfigDefinitionSource implements DefinitionSource
{
    /**
     * @var GlobalSettingsProvider
     */
    private $config;
    /**
     * @var string
     */
    private $prefix;
    /**
     * @param GlobalSettingsProvider $config
     * @param string $prefix Prefix for the container entries.
     */
    public function __construct(GlobalSettingsProvider $config, $prefix = 'ini.')
    {
        $this->config = $config;
        $this->prefix = $prefix;
    }
    /**
     * {@inheritdoc}
     */
    public function getDefinition($name)
    {
        if (strpos($name, $this->prefix) !== 0) {
            return null;
        }
        list($sectionName, $configKey) = $this->parseEntryName($name);
        $section = $this->getSection($sectionName);
        if ($configKey === null) {
            $value = new ValueDefinition($section);
            $value->setName($name);
            return $value;
        }
        if (!array_key_exists($configKey, $section)) {
            return null;
        }
        $value = new ValueDefinition($section[$configKey]);
        $value->setName($name);
        return $value;
    }
    public function getDefinitions() : array
    {
        $result = [];
        foreach ($this->config as $section) {
            $value = new ValueDefinition($this->getSection($section));
            $value->setName($section);
            $result[$section] = $value;
        }
        return $result;
    }
    private function parseEntryName($name)
    {
        $parts = explode('.', $name, 3);
        array_shift($parts);
        if (!isset($parts[1])) {
            $parts[1] = null;
        }
        return $parts;
    }
    private function getSection($sectionName)
    {
        $section = $this->config->getSection($sectionName);
        if (!is_array($section)) {
            throw new InvalidDefinition(sprintf('IniFileChain did not return an array for the config section %s', $section));
        }
        return $section;
    }
}
