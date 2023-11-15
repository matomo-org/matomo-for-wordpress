<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CoreHome;

/**
 * CoreHome segment base class
 */
class Segment extends \Piwik\Plugin\Segment
{
    protected  function init()
    {
        $this->setCategory('General_Visit');
    }
}
