<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\WordPress\Overrides\TagManager;

use Piwik\Plugins\TagManager\Context\WebContext as UpstreamWebContext;

class WebContext extends UpstreamWebContext
{
    protected function generatePublicContainer($container, $release)
    {
        return $this->removeLookupOutputsThatAreNotScalar(parent::generatePublicContainer($container, $release));
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function removeLookupOutputsThatAreNotScalar($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $item) {
            if ($key === 'lookUpTable' && is_array($item)) {
                $item = array_values(array_filter($item, function ($lookUp) {
                    return is_array($lookUp) && (!isset($lookUp['outValue']) || is_scalar($lookUp['outValue']));
                }));
            }

            $value[$key] = $this->removeLookupOutputsThatAreNotScalar($item);
        }

        return $value;
    }
}
