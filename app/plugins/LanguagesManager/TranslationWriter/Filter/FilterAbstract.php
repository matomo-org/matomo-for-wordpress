<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\LanguagesManager\TranslationWriter\Filter;

abstract class FilterAbstract
{
    protected $filteredData = array();
    /**
     * Filter the given translations
     *
     * @param array $translations
     *
     * @return array   filtered translations
     */
    public abstract function filter($translations);
    /**
     * Returnes the data filtered out by the filter
     *
     * @return array
     */
    public function getFilteredData()
    {
        return $this->filteredData;
    }
}
